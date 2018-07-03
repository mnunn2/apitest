<?php

require '../vendor/autoload.php';
use \Slim\App;
use \Apiclient\ProductMap;
use \Apiclient\MediaMap;
use Evance\ApiClient;
use Evance\Resource\Products;
use Evance\Resource\ProductMedia;

// Instantiate new Slim app to use DI and settings for DB connection
$settings = require '../src/settings.php';
$app = new App($settings);
require '../src/dependencies.php';

// define("SALSIFY_RECORD_LIMIT", 4);
const SALSIFY_RECORD_LIMIT = 5;
const EVANCE_RECORD_LIMIT = 100;
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
        print_r($media);

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
    echo "processed " . $record["id"] . " " . $record["request_id"];
    echo " with " . count($productApi) . " products\n";
    $webhookTable->updateStatus($record["id"], "processed");
}
//exit;

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
        echo "Product not found\n";// . $e->getMessage();
    }
    // insert or update product
    if (empty($productResult["product"])) {
        $createData = $productApi->add($productData);
        $productId = $createData["product"]["id"];
        print_r("product ID inserted " . $productId . " ");
    } else {
        $productId = $productResult["product"][0]["id"];
        $productApi->update($productId, $productData);
        print_r("product ID updated " . $productId . " ");
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
            echo "media already exists\n";// . $e->getMessage();
        }
    }



    $evanceProductTable->updateStatus($record["id"], "processed");

    print_r($record["sku"] . " $sku\n");
    //var_dump($product["Item SKU"]);
}
