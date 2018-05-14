<?php

require_once '../vendor/autoload.php';

use Evance\ApiClient;

$client = new ApiClient();
$client->loadAuthConfig('../client-credentials-salsify-app.json');
$token = $client->fetchAccessTokenWithJwt();

$products = new \Evance\Resource\Products($client);
//var_dump($products->get(3053));
$params = ["skus" => "EX-PP-001, EX-PP-002, 683"];
try {
    //$product = $products->get(1696);
    $response = $products->search($params);
} catch (Exception $e) {
    echo $e->getMessage();
}

