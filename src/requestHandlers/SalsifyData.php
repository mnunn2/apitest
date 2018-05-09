<?php

namespace Apiclient;

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;
use Monolog\Logger;

class SalsifyData
{
    private $logger;
    private $salsifyProductData;
    /**
     * SalsifyHeaders constructor.
     * @param Logger $logger
     * @param SalsifyProductData $salsifyProductData
     *
     */
    public function __construct(Logger $logger, SalsifyProductData $salsifyProductData)
    {
        $this->logger = $logger;
        $this->salsifyProductData = $salsifyProductData;
    }

    public function __invoke(Req $request, Resp $response, $args)
    {
        $jsonString = $this->salsifyProductData->getData();
        $newResponse = $response->withHeader('Content-type', 'application/json');
        $newResponse->getBody()->write($jsonString);

        return $response;
    }
}

