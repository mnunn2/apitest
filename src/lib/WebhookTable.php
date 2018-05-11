<?php

namespace Apiclient;

use Monolog\Logger;

class WebhookTable
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
        $stmt = $this->db->prepare("INSERT INTO salsifyJSON (status, request_id, type, payload) VALUES(?, ?, ?, ?)");
        $stmt->execute(array("pending", $requestId, "product", $payload));
        return true;
    }

    public function fetchFirstNRecords($numberOfRecords)
    {
        $stmt = $this->db->prepare("SELECT * FROM salsifyJSON ORDER BY id LIMIT ?");
        $stmt->execute([$numberOfRecords]);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

    public function fetchLatestPayload()
    {
        $stmt = $this->db->query("SELECT payload FROM salsifyJSON ORDER BY id DESC LIMIT 1");
        $data = $stmt->fetchColumn();
        return $data;
    }

    public function updateStatus($status)
    {
        $stmt = $this->db->query("SELECT payload FROM salsifyJSON ORDER BY id DESC LIMIT 1");
        $data = $stmt->fetchColumn();
        return $data;
    }
}