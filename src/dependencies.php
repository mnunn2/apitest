<?php

use \Monolog\Logger;
use \Monolog\Formatter\LineFormatter;
use \Monolog\Handler\StreamHandler;
use \Monolog\Processor\UidProcessor;
use \Apiclient\SalsifyHeaders;
use \Apiclient\FetchLatestSalsifyPayload;
use \Apiclient\SalsifyWebhook;
use \Apiclient\WebhookTable;
use \Apiclient\EvanceProductTable;

$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Logger($settings['name']);

    // Line formatter without empty brackets in the end
    $formatter = new LineFormatter(null, null, false, true);

    $logger->pushProcessor(new UidProcessor());
    $streamHandler = new StreamHandler($settings['path'], $settings['level']);
    $streamHandler->setFormatter($formatter);

    $logger->pushHandler($streamHandler);

    return $logger;
};

$container['db'] = function ($c) {
    $settings = $c->get('settings')['db'];
    $dsn = "mysql:host=" . $settings["host"] . ";dbname=" . $settings["dbname"];

    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $settings['user'], $settings['pass'], $opt);

    return $pdo;
};

$container['salsifyHeaders'] = function($c) {
    $logger = $c->get('logger');
    return new SalsifyHeaders($logger);
};

$container['webhookTable'] = function($c) {
    $logger = $c->get('logger');
    $db = $c->get('db');
    return new WebhookTable($logger, $db);
};

$container['evanceProductTable'] = function($c) {
    $logger = $c->get('logger');
    $db = $c->get('db');
    return new EvanceProductTable($logger, $db);
};

// NB the full class name is required because it is being called directly
// by class name from the route
$container['Apiclient\FetchLatestSalsifyPayload'] = function($c) {
    $logger = $c->get('logger');
    $data = $c->get('webhookTable');
    return new FetchLatestSalsifyPayload($logger, $data);
};

$container['Apiclient\SalsifyWebhook'] = function($c) {
    $logger = $c->get('logger');
    $headers = $c->get('salsifyHeaders');
    $productData = $c->get('webhookTable');
    return new SalsifyWebhook($logger, $headers, $productData);
};
