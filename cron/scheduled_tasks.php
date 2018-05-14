<?php

require '../vendor/autoload.php';
use \Slim\App;
use \Apiclient\EvanceProductMapper;

// Instantiate new Slim app to use DI and settings for DB connection
$settings = require '../src/settings.php';
$app = new App($settings);
require '../src/dependencies.php';

define("RECORD_LIMIT", 4);

/**
 * Initialise table classes from DI container
 * @var $webhookTable \Apiclient\WebhookTable
 * @var $evanceProductTable \Apiclient\EvanceProductTable
 */
$webhookTable = $container['webhookTable'];
$evanceProductTable = $container['evanceProductTable'];

$records = $webhookTable->fetchFirstNRecordsPending(RECORD_LIMIT);

foreach ($records as $record) {
    $salsifyRecord = json_decode($record['payload'], true);
    $products = $salsifyRecord["products"] ;

    foreach ($products as $product) {
        $evanceProduct = (new EvanceProductMapper($product))->getProduct();
        $evanceProductTable->saveData($evanceProduct["evance_product_id"], json_encode($evanceProduct));
    }
    echo "processed " . $record["id"] . " " . $record["request_id"];
    echo " with " . count($products) . " products\n";
    $webhookTable->updateStatus($record["id"], "processed");
}

