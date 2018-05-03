<?php

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;

$app->post('/salsifywebhook[/{test}]', function (Req $request, Resp $response, $args = []) {
    //todo: add log prefix
    if (array_key_exists('test', $args)) {
        // for use with the test data set
        $this->salsifyHeaders->setWebhookURL('http://client-client-test.a3c1.starter-us-west-1.openshiftapps.com/dumpData');
    } else {
        $this->salsifyHeaders->setWebhookURL((string)$request->getUri());
    }
    $requestBody = $request->getBody()->getContents();
    $this->salsifyHeaders->setRequestBody($requestBody);
    $rawHeaders = $request->getHeaders();
    $this->salsifyHeaders->setRawHeaders($rawHeaders);

    if (!$this->salsifyHeaders->areValid()) {
        $this->logger->error("salsify-webhook '/slsifywebhook' validation error");
        $response->getBody()->write("{ 'response':'validation failed'}");
    } else {
        $this->logger->info("salsify-webhook '/slsifywebhook' ok, id = " . $rawHeaders["HTTP_X_SALSIFY_REQUEST_ID"][0]);
        $outFile = fopen("../cache/body.json", "w") or die("Unable to open file!");
        fwrite($outFile, $requestBody);
        fclose($outFile);
        $response->getBody()->write("{ 'response':'ok' }");
    }
    return $response;
});

$app->get('/cachedJson', function (Req $request, Resp $response) {

    $jsonString = file_get_contents("../cache/body.json");
    $newResponse = $response->withHeader('Content-type', 'application/json');
    $newResponse->getBody()->write($jsonString);

    return $newResponse;
});

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

