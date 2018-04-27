<?php

use \Monolog\Logger;
use \Monolog\Formatter\LineFormatter;
use \Monolog\Handler\StreamHandler;
use \Monolog\Processor\UidProcessor;

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