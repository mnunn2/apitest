<?php

namespace Apiclient;

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;
use Monolog\Logger;

class SalsifyWebhook
{
    private $logger;
    private $salsifyHeaders;
    private $webhookTable;

    /**
     * SalsifyHeaders constructor.
     * @param Logger $logger
     * @param SalsifyHeaders $salsifyHeaders
     * @param WebhookTable $webhookTable
     *
     *
     */
    public function __construct(Logger $logger, SalsifyHeaders $salsifyHeaders, WebhookTable $webhookTable)
    {
        $this->logger = $logger;
        $this->salsifyHeaders = $salsifyHeaders;
        $this->webhookTable = $webhookTable;
    }

    public function __invoke(Req $request, Resp $response, $args = [])
    {
        //todo: add log prefix
        if (array_key_exists('test', $args)) {
            // for use with the test data set
            $this->salsifyHeaders->setWebhookURL(
                'http://client-client-test.a3c1.starter-us-west-1.openshiftapps.com/dumpData'
            );
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
            $this->logger->info("salsify-webhook '/slsifywebhook' ok, id = " .
                $rawHeaders["HTTP_X_SALSIFY_REQUEST_ID"][0]);

            //todo test if db save was successful
            $this->webhookTable->saveData($rawHeaders["HTTP_X_SALSIFY_REQUEST_ID"][0], $requestBody);

            $response->getBody()->write("{ 'response':'ok' }");
        }
        return $response;
    }
}
