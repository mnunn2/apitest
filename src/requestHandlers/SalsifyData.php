<?php

namespace Apiclient;

use \Psr\Http\Message\ServerRequestInterface as Req;
use \Psr\Http\Message\ResponseInterface as Resp;
use Monolog\Logger;

class SalsifyData
{
    private $logger;
    private $db;
    /**
     * SalsifyHeaders constructor.
     * @param Logger $logger
     * @param \PDO $db
     *
     */
    public function __construct(Logger $logger, \PDO $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function __invoke(Req $request, Resp $response, $args)
    {
        var_dump($request);
        $jsonString = file_get_contents(__DIR__ . "/../../cache/body.json");
        $newResponse = $response->withHeader('Content-type', 'application/json');
        $newResponse->getBody()->write($jsonString);

        return $newResponse;
    }
}

