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

    public function saveData($sku, $productJson)
    {
        if ($this->fetchBySku($sku)) {
            $stmt = $this->db->prepare("UPDATE evanceProduct SET status = ?, productJSON = ? WHERE sku = ?");
            $stmt->execute(array("pending", $productJson, $sku));
        } else {
            $stmt = $this->db->prepare("INSERT INTO evanceProduct (status, sku, productJSON) VALUES(?, ?, ?)");
            $stmt->execute(array("pending", $sku, $productJson));
        }
        return true;
    }
    // todo mike: refactor common code to parent class
    public function fetchFirstNRecordsPending($numberOfRecords)
    {
        $stmt = $this->db->prepare("SELECT * FROM evanceProduct WHERE status = ? ORDER BY id LIMIT ?");
        $stmt->execute(["pending", $numberOfRecords]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

    public function fetchBySku($productId)
    {
        $stmt = $this->db->prepare("SELECT id FROM evanceProduct WHERE sku = ?");
        $stmt->execute([$productId]);
        $id = $stmt->fetchColumn();
        if (!is_int($id)) {
            return false;
        }
        return true;
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE evanceProduct SET status = ? WHERE id = ?");
        $stmt->execute(array($status, $id));
        return true;
    }
}
