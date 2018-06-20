<?php

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;
use \Apiclient\FetchLatestSalsifyPayload;
use \Apiclient\SalsifyWebhook;

$app->post('/salsifywebhook[/{test}]', SalsifyWebhook::class);

$app->get('/fetchLatestSalsifyPayload', FetchLatestSalsifyPayload::class);

$app->post('/dumpData', function (Req $request, Resp $response) {

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

$app->get('/info', function (Req $request, Resp $response) {

    $response->getBody()->write(phpinfo());

    return $response;
});
