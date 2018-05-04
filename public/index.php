<?php

date_default_timezone_set("UTC");
error_reporting(E_ALL);
ini_set("display_errors", 1);

require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

require __DIR__ . '/../src/dependencies.php';
//require __DIR__ . '/../src/SlimMiddleware.php';
require __DIR__ . '/../src/routes.php';

$app->run();

