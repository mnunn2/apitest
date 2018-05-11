<?php

namespace Apiclient;

use Monolog\Logger;

class EvanceProductTable
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

    public function saveData($productId, $productJson)
    {
        if ($this->fetchByProductId($productId)) {
            $stmt = $this->db->prepare( "UPDATE evanceProduct SET status = ?, productJSON = ? WHERE product_id = ?");
            $stmt->execute(array("pending", $productJson, $productId));
        } else {
            $stmt = $this->db->prepare("INSERT INTO evanceProduct (status, product_id, productJSON) VALUES(?, ?, ?)");
            $stmt->execute(array("pending", $productId, $productJson));
        }
        return true;
    }

    public function fetchFirstNRecords($numberOfRecords)
    {
        $stmt = $this->db->prepare("SELECT * FROM evanceProduct ORDER BY id LIMIT ?");
        $stmt->execute([$numberOfRecords]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

    public function fetchByProductId($productId)
    {
        $stmt = $this->db->prepare("SELECT id FROM evanceProduct WHERE product_id = ?");
        $stmt->execute([$productId]);
        $id = $stmt->fetchColumn();
        if (!is_int($id)) {
            return false;
        }
        return true;

    }
}