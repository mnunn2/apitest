<?php

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;
use \Apiclient\SalsifyHeaders;

$app->post('/salsifywebhook', function (Req $request, Resp $response, $args) {
    //todo: add log prefix
    $rawHeaders = $request->getHeaders();
    $requestBody = $request->getBody()->getContents();
    $salsifyHeaders = new SalsifyHeaders($rawHeaders, $this->logger, $requestBody);

    if (!$salsifyHeaders->areValid()) {
        $this->logger->error("salsify-webhook '/slsifywebhook' validation error");
        $response->getBody()->write("{ 'response':'validation failed'}");
    } else {
        $this->logger->info("salsify-webhook '/slsifywebhook' route ok");
        $outFile = fopen("../cache/body.json", "w") or die("Unable to open file!");
        fwrite($outFile, $requestBody );
        fclose($outFile);
        $response->getBody()->write("{ 'response':'ok' }");
    }
    return $response;
});

$app->get('/cachedJson', function (Req $request, Resp $response, $args) {

    $jsonString = file_get_contents("../cache/body.json");
    //$newString = json_encode($jsonString, JSON_PRETTY_PRINT);
    $newResponse = $response->withHeader('Content-type', 'application/json');
    $newResponse->getBody()->write($jsonString);

    return $newResponse;
});

$app->post('/dumpData', function (Req $request, Resp $response, $args) {

    $headers = $request->getHeaders();
    $file = fopen("headers.txt", "w") or die("Unable to open file!");
    fwrite($file, "Headers:\n");
    foreach ($headers as $name => $values) {
        fwrite($file, $name . ": " . implode(", ", $values) . "\n");
    }
    fwrite($file, "Body:\n");
    fwrite($file, $request->getBody()->getContents());
    fclose($file);
    $response->getBody()->write("{ 'success':'ok' }");

    return $response;
});


