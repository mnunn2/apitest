<?php
/**
 * Created by PhpStorm.
 * User: Mike Nunn
 * Date: 04/05/2018
 * Time: 15:24
 */

namespace Apiclient;

use Monolog\Logger;

class SalsifyProductData
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

    public function saveData($requestId, $payload)
    {
        $stmt = $this->db->prepare("INSERT INTO fred (request_id, type, payload) VALUES(?, ?, ?)");
        $stmt->execute(array($requestId, "product", $payload));
        return true;
    }

    public function getData()
    {
        $stmt = $this->db->query("SELECT payload FROM fred ORDER BY id DESC LIMIT 1");
        $returnVal = $stmt->fetchColumn();
        return $returnVal;
    }
}