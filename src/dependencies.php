<?php

use \Monolog\Logger;
use \Monolog\Formatter\LineFormatter;
use \Monolog\Handler\StreamHandler;
use \Monolog\Processor\UidProcessor;
use \Apiclient\SalsifyHeaders;

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
    $pdo = new PDO('mysql:host=' . $settings['host'] . ';dbname=' . $settings['dbname'],
        $settings['user'], $settings['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['salsifyHeaders'] = function($c) {
    $logger = $c->get('logger');
    $db = $c->get('db');
    return new SalsifyHeaders($logger, $db);
};