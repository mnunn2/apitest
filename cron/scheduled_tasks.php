<?php

require '../vendor/autoload.php';
use \Slim\App;
use \Apiclient\EvanceProductMapper;
use Evance\ApiClient;
use Evance\Resource\Products;

// Instantiate new Slim app to use DI and settings for DB connection
$settings = require '../src/settings.php';
$app = new App($settings);
require '../src/dependencies.php';

// define("SALSIFY_RECORD_LIMIT", 4);
const SALSIFY_RECORD_LIMIT = 4;
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
    $products = $rawData["products"] ;

    foreach ($products as $product) {
        $evanceProductData = (new EvanceProductMapper($product))->getProduct();
        $evanceProduct["product"] = $evanceProductData;
        $evanceProductTable->saveData($evanceProduct["product"]["sku"], json_encode($evanceProduct));
    }
    echo "processed " . $record["id"] . " " . $record["request_id"];
    echo " with " . count($products) . " products\n";
    $webhookTable->updateStatus($record["id"], "processed");
}

// setup apiclient
$client = new ApiClient();
$products = new Products($client);
$client->loadAuthConfig(CREDENTIALS);
$token = $client->fetchAccessTokenWithJwt();

// process pending items in evanceProduct table
$evanceRecords = $evanceProductTable->fetchFirstNRecordsPending(EVANCE_RECORD_LIMIT);
foreach ($evanceRecords as $record) {
    $productData = json_decode($record["productJSON"], true);
    $sku = $productData["product"]["sku"];
    $params = ["skus" => $sku];
    $result = $products->search($params);
    if (empty($result["product"])) {
        $createData = $products->add($productData);
        $createId = $createData["product"]["id"];
        print_r("product ID inserted " . $createId . " ");
    } else {
        $foundId = $result["product"][0]["id"];
        $products->update($foundId, $productData);
        print_r("product ID updated " . $foundId . " ");
    }
    $evanceProductTable->updateStatus($record["id"], "processed");

    print_r($record["sku"] . " $sku\n");
    //var_dump($product["Item SKU"]);
}
