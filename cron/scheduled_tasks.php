<?php

require '../vendor/autoload.php';
use \Slim\App;
use \Apiclient\ProductMap;
use \Apiclient\MediaMap;
use \Apiclient\TranslationMap;
use Evance\ApiClient;
use Evance\Resource\Products;
use Evance\Resource\ProductMedia;
use Evance\Resource\ProductTranslations;

// Instantiate new Slim app to use DI and settings for DB connection
$settings = require '../src/settings.php';
$app = new App($settings);
require '../src/dependencies.php';

const SALSIFY_RECORD_LIMIT = 10;
const EVANCE_RECORD_LIMIT = 25;
const CREDENTIALS = '../client-credentials-salsify-app.json';

/**
 * Initialise table classes from DI container
 * @var $webhookTable \Apiclient\WebhookTable
 * @var $evanceProductTable \Apiclient\EvanceProductTable
 */
$webhookTable = $container['webhookTable'];
$evanceProductTable = $container['evanceProductTable'];

// process pending items in salsifyJSON table
$salsifyRecords = $webhookTable->fetchFirstNRecordsPending(SALSIFY_RECORD_LIMIT);
foreach ($salsifyRecords as $record) {
    $rawData = json_decode($record['payload'], true);
    $productApi = $rawData["products"] ;

    foreach ($productApi as $product) {
        $media = [];
        $translations = [];

        // extract the translations and media from the product data
        foreach ($product as $key => $val) {
            if (preg_match("/^([a-z][a-z]-[A-Z][A-Z])_(.+)/", $key, $match)) {
                $payloadLocales[$match[1]] = null;
                if (array_key_exists($match[1], $translations)) {
                    $translations[$match[1]][$match[2]] = $val;
                } else {
                    $translations[$match[1]] = [$match[2] => $val];
                }
            }

            if (preg_match("/^(JPG)/", $key, $match)) {
                $media += [$match[1] => $val];
            }
        }
        //print_r($media);

        // save the various json data to the db
        $translationJson = (!empty($translations)) ? json_encode($translations) : null;
        $mediaJson = (!empty($media)) ? json_encode($media) : null;
        $evanceProductTable->saveData(
            $product["Item SKU"],
            json_encode($product),
            $translationJson,
            $mediaJson
        );
    }
    //echo "processed " . $record["id"] . " " . $record["request_id"];
    //echo " with " . count($productApi) . " products\n";
    $webhookTable->updateStatus($record["id"], "processed");
}

// setup apiclient
$client = new ApiClient();
$productApi = new Products($client);
$client->loadAuthConfig(CREDENTIALS);
$token = $client->fetchAccessTokenWithJwt();

// process pending items in evanceProduct table
$evanceRecords = $evanceProductTable->fetchFirstNRecordsPending(EVANCE_RECORD_LIMIT);
foreach ($evanceRecords as $record) {
    // create product and map properties from db
    $productData = new stdClass();
    $productPayload = json_decode($record["productJSON"], true);
    $productMapper = new ProductMap($productData, $productPayload);
    $productMapper->assignLeft(false);
    $productData = ['product' => (array) $productData];
    $productResult = null;
    $productId = null;

    // check if product already exists, not found returns a 404 hence try catch
    $sku = $productData['product']["sku"];
    $params = ["skus" => $sku];
    try {
        $productResult = $productApi->search($params);
    } catch (GuzzleHttp\Exception\ClientException $e) {
        //echo "Product not found\n";// . $e->getMessage();
    }

    // insert or update product
    if (empty($productResult["products"])) {
        try {
            $createData = $productApi->add($productData);
            $productId = $createData["product"]["id"];
        } catch (GuzzleHttp\Exception\ClientException $e) {
            print_r("sku $sku insert failed $e\n");
            continue;
        }
    } else {
        $productId = $productResult["products"][0]["id"];
        try {
            $productApi->update($productId, $productData);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            print_r("psku $sku update failed\n");
            continue;
        }
    }


    // create media from db and map properties
    $mediaData = new stdClass();
    $mediaPayload = json_decode($record["mediaJSON"], true);
    if ($mediaPayload) {
        $mediaMapper = new MediaMap($mediaData, $mediaPayload);
        $mediaMapper->assignLeft(false);
        $mediaData = ['media' => (array)$mediaData];
        $mediaResult = null;

        // insert media
        $mediaApi = new ProductMedia($client);
        try {
            $mediaResult = $mediaApi->add($productId, $mediaData);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $e->getMessage();
        }
    }

    // create translations from db and map properties
    $translations = json_decode($record["translationJSON"], true);
    if ($translations) {
        $translationsApi = new ProductTranslations($client);
        $validLocales = fetchValidLocales($productId, $translationsApi);

        foreach ($translations as $locale => $translationPayload) {
            $locale = mb_strtolower($locale);
            $translationData = new \stdClass();
            if (array_key_exists($locale, $validLocales)) {
                $translationMapper = new TranslationMap($translationData, $translationPayload);
                $translationMapper->assignLeft(false);
                if (!empty((array) $translationData)) {
                    $translationData = ['translation' => (array)$translationData];
                    $translationData['translation']['language'] = $validLocales[$locale]['language'];
                    $translationData['translation']['country'] = $validLocales[$locale]['country'];
                    //print_r($translationData);

                    // insert or update translations
                    if ($validLocales[$locale]['id']) {
                        //update with translation is
                        try {
                            $translationsApi->update($productId, $validLocales[$locale]['id'], $translationData);
                            //echo "\ntranslation updated \n";
                        } catch (\GuzzleHttp\Exception\ClientException $e) {
                            //echo $e->getMessage();
                        }
                    } else {
                        // insert translation
                        try {
                            $translationsApi->add($productId, $translationData);
                            //echo "\n translation inserted \n";
                        } catch (\GuzzleHttp\Exception\ClientException $e) {
                            //echo $e->getMessage();
                        }
                    }
                }
            }
        }
    }

    $evanceProductTable->updateStatus($record["id"], "processed");

    print_r("sku $sku processed\n");
}

/**
 * Returns an array of valid locales with a translation id
 * if it exists for a given prodcutId
 * @param $translationsApi
 * @return array
 */
function fetchValidLocales($prodcutId, $translationsApi)
{
    // get list of valid locales
    $localesData = null;
    $validLocales = [];
    try {
        $localesData = $translationsApi->getLocales($prodcutId);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo "translation already exists\n";// . $e->getMessage();
    }
    if ($localesData) {
        // $localeData['id'] refers to the local eg en-GB
        foreach ($localesData['locales'] as $localeData) {
            $validLocales[$localeData['id']] = ['id' => null];
            $validLocales[$localeData['id']] += ['language' => $localeData['language']];
            $validLocales[$localeData['id']] += ['country' => $localeData['country']];
        }
    }

    // check if translations exist
    $xlations = null;
    $translationId = null;
    try {
        $xlations = $translationsApi->get($prodcutId);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        //echo $e->getMessage();
    }
    if ($xlations) {
        foreach ($xlations['translations'] as $xlation) {
            $xlationLocale = $xlation['language'] . "-" . mb_strtolower($xlation['country']);
            if (array_key_exists($xlationLocale, $validLocales)) {
                $validLocales[$xlationLocale]['id'] = $xlation['id'];
            }
        }
    }
    return $validLocales;
}
