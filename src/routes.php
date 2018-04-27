<?php

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;
use \Apiclient\SalsifyHeaders;

$app->get('/salsifywebhook', function (Req $request, Resp $response, $args) {

    //todo: add log prefix
    $rawHeaders = $request->getHeaders();
    $salsifyHeaders = new SalsifyHeaders($rawHeaders, $this->logger);

    if (!$salsifyHeaders->areValid()) {
        $this->logger->error("salsify-webhook '/slsifywebhook' validation error");
        $response->getBody()->write("Invalid Headers\n");
    } else {
        $this->logger->info("salsify-webhook '/slsifywebhook' route ok");
        $response->getBody()->write("Hello\n");
    }

    return $response;
});
