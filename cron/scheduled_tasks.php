<?php

require '../vendor/autoload.php';
use \Slim\App;

// Instantiate new Slim app to use DI and settings for DB connection
$settings = require '../src/settings.php';
$app = new App($settings);
require '../src/dependencies.php';

$recordLimit = 4;

$webhookTable = $container['webhookTable'];
$evanceProductTable = $container['evanceProductTable'];

$records = $webhookTable->fetchFirstNRecords($recordLimit);

foreach ($records as $record) {
    $salsifyRecord = json_decode($record['payload'], true);
    echo "processing " . $salsifyRecord["alert"]["id"];
    $products = $salsifyRecord["products"] ;
    echo " with " . count($products) . " products\n";

    foreach ($products as $product) {
        $productId = $product["salsify:id"];
        $evanceProductTable->saveData($productId, json_encode($product));
    }
}

