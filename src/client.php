<?php

require_once '../vendor/autoload.php';

use Evance\ApiClient;

$client = new ApiClient();
$client->loadAuthConfig('../client-credentials-salsify-app.json');
$token = $client->fetchAccessTokenWithJwt();

$products = new \Evance\Resource\Products($client);
var_dump($products->get(3053));

