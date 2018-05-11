<?php

namespace Apiclient;

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;
use Monolog\Logger;

class FetchLatestSalsifyPayload
{
    private $logger;
    private $salsifyProductData;
    /**
     * SalsifyHeaders constructor.
     * @param Logger $logger
     * @param WebhookTable $salsifyProductData
     *
     */
    public function __construct(Logger $logger, WebhookTable $salsifyProductData)
    {
        $this->logger = $logger;
        $this->salsifyProductData = $salsifyProductData;
    }

    public function __invoke(Req $request, Resp $response, $args)
    {
        $latestPayload = $this->salsifyProductData->fetchLatestPayload();

        $newResponse = $response->withHeader('Content-type', 'application/json');
        $newResponse->getBody()->write($latestPayload);

        return $newResponse;
    }
}

