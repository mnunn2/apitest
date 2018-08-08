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
const EVANCE_RECORD_LIMIT = 500;
const CREDENTIALS = '../client-credentials-salsify-app.json';

$webhookTable = $container['webhookTable'];
$evanceProductTable = $container['evanceProductTable'];

processSalsifyJson($webhookTable, $evanceProductTable);

$client = new ApiClient();
$client->loadAuthConfig(CREDENTIALS);
$token = $client->fetchAccessTokenWithJwt();

// process pending items in evanceProduct table
$evanceRecords = $evanceProductTable->fetchFirstNRecordsPending(EVANCE_RECORD_LIMIT);
foreach ($evanceRecords as $record) {
    $processMsg = '';

    $productId = processProduct($record, $client, $evanceProductTable, $processMsg);

    if (!$productId) {
        continue;
    }

    $mediaId = processMedia($record, $client, $productId, $processMsg);

    $translationId = processTranslations($record, $client, $productId, $processMsg);

    $evanceProductTable->updateStatus($record["id"], "processed");

    print_r("sku $processMsg\n");

}

/**
 * Truncate short description
 * @param $description
 * @return string
 */
function trimShortDesc($description)
{
    $truncated = substr($description, 0, 251) . '...';
    //print_r(strlen($truncated . " "));
    return $truncated;
}

/**
 * Process product record and post to api
 * @param $record
 * @param $client
 * @var $evanceProductTable \Apiclient\EvanceProductTable
 * @param $processMsg
 * @return int;
 */
function processProduct($record, $client, $evanceProductTable, &$processMsg)
{
    // create product and map properties from db
    $productApi = new Products($client);
    $productData = new stdClass();
    $productPayload = json_decode($record["productJSON"], true);
    $productMapper = new ProductMap($productData, $productPayload);
    $productMapper->assignLeft(false);
    $productData = ['product' => (array) $productData];
    $productResult = null;
    $productId = null;

    // trim short description
    if (array_key_exists('description', $productData['product'])) {
        $shortDesc = $productData['product']["description"];
        $shortDesc = trimShortDesc($shortDesc);
        $productData['product']["description"] = $shortDesc;
    }

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
            $processMsg = $sku . " product $productId inserted,";
        } catch (GuzzleHttp\Exception\ClientException $e) {
            // decode and encode removes \n - can now be logged
            $msg = json_encode(json_decode($e->getMessage()));
            print_r("sku $sku product insert failed $msg\n");
            $evanceProductTable->updateStatus($record["id"], "failed");
        }
    } else {
        $productId = $productResult["products"][0]["id"];
        try {
            $productApi->update($productId, $productData);
            $processMsg = $sku . " product $productId updated,";
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $evanceProductTable->updateStatus($record["id"], "failed");
            $msg = json_encode(json_decode($e->getMessage()));
            print_r("sku $sku product update failed $msg\n");
        }
    }
    return $productId;
}

/**
 * @param $record
 * @param $client
 * @param $productId
 * @param $processMsg
 * @return mixed
 */
function processMedia($record, $client, $productId, &$processMsg)
{
    // create media from db and map properties
    $mediaId = null;
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
            $mediaId = $mediaResult['media']['id'];
            $processMsg .=  " media $mediaId inserted, ";
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $processMsg .= " media error " . str_replace("\n", "", $e->getMessage());
        }
    }
    return $mediaId;
}

/**
 * @param $record
 * @param $client
 * @param $productId
 * @param $processMsg
 * @return mixed
 */
function processTranslations($record, $client, $productId, &$processMsg)
{
    // create translations from db and map properties
    $id = null;
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

                    // trim short description
                    if (array_key_exists('description', $translationData['translation'])) {
                        $shortDesc = $translationData['translation']["description"];
                        $shortDesc = trimShortDesc($shortDesc);
                        $translationData['translation']["description"] = $shortDesc;
                    }

                    // insert or update translations
                    if ($validLocales[$locale]['id']) {
                        try {
                            $res = $translationsApi->update($productId, $validLocales[$locale]['id'], $translationData);
                            $id = $res['translation']['id'];
                            $processMsg .=  " translation $id updated, ";
                        } catch (\GuzzleHttp\Exception\ClientException $e) {
                            $processMsg .= " translation error " . str_replace("\n", "", $e->getMessage());
                        }
                    } else {
                        try {
                            $res = $translationsApi->add($productId, $translationData);
                            $id = $res['translation']['id'];
                            $processMsg .=  " translation $id inserted, ";
                            //print_r($res);
                        } catch (\GuzzleHttp\Exception\ClientException $e) {
                            $processMsg .= " translation error " . str_replace("\n", "", $e->getMessage());
                        }
                    }
                }
            }
        }
    }
    return $id;
}

/**
 * Process salsify webhook data and split into individual products with
 * associated media and translations, then save to evanceProduct table
 * @var $webhookTable \Apiclient\WebhookTable
 * @var $evanceProductTable \Apiclient\EvanceProductTable
 */
function processSalsifyJson($webhookTable, $evanceProductTable)
{
// process pending items in salsifyJSON table
    $salsifyRecords = $webhookTable->fetchFirstNRecordsPending(SALSIFY_RECORD_LIMIT);
    foreach ($salsifyRecords as $record) {
        $rawData = json_decode($record['payload'], true);
        $productApi = $rawData["products"];

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
}

/**
 * Returns an array of valid locales with a translation id
 * if it exists for a given productId
 * @param $productId
 * @var $translationsApi \Evance\Resource\ProductTranslations;
 * @return array
 */
function fetchValidLocales($productId, $translationsApi)
{
    // get list of valid locales
    $localesData = null;
    $validLocales = [];
    try {
        $localesData = $translationsApi->getLocales($productId);
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
        $xlations = $translationsApi->get($productId);
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
