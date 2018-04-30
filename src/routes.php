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
        $response->getBody()->write("Invalid Headers\n");
    } else {
        $this->logger->info("salsify-webhook '/slsifywebhook' route ok");
        $response->getBody()->write("{ 'success':'ok' }");
    }
    return $response;
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


