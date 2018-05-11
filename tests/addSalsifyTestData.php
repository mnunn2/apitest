<?php

require '../vendor/autoload.php';
use \Slim\App;

// Instantiate new Slim app to use DI and settings for DB connection
$settings = require '../src/settings.php';
$app = new App($settings);
require '../src/dependencies.php';

// create record with multiple products in payload from file
$webhookTable = $container['webhookTable'];

$requestId = "test-data-12345678";
$jsonData = file_get_contents('salsifyTestData.json');

$webhookTable->saveData($requestId, $jsonData);


