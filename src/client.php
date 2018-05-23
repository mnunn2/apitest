<?php

require_once '../vendor/autoload.php';

use Evance\ApiClient;

$client = new ApiClient();
$client->loadAuthConfig('../client-credentials-salsify-app.json');
$token = $client->fetchAccessTokenWithJwt();

$rawData = json_decode(file_get_contents(dirname(__FILE__) . '/../cache/beaphar.json'));
$productData["product"] = $rawData;

$resource = new \Evance\Resource\Products($client);

//var_dump($products->get(3053));
$params = ["skus" => "EX-PP-001"];
try {
    //$response = $resource->search($params);
    $response = $resource->add($productData);
    print_r($response);
} catch (Exception $e) {
    echo $e->getMessage();
}

