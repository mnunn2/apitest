<?php

namespace Apiclient;

use Monolog\Logger;

class SalsifyHeaders
{
    private $webhookURL;
    private $cacheTimout = 3600; //in seconds
    private $certFile = "../cache/salsifyPubCert.pem";
    private $requestBody;
    private $publicKey;
    private $logger;
    private $rawHeaders;
    private $salsifySentHeaders;
    private $salsifySpecedHeaders = array(
        "HTTP_X_SALSIFY_TIMESTAMP" => "",
        "HTTP_X_SALSIFY_CERT_URL" => "",
        "HTTP_X_SALSIFY_SIGNATURE_V1" => "",
        "HTTP_X_SALSIFY_REQUEST_ID" => "",
        "HTTP_X_SALSIFY_ORGANIZATION_ID" => ""
    );

    /**
     * SalsifyHeaders constructor.
     * @param Logger $logger
     * @param \PDO $db
     *
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return boolean
     */
    public function areValid()
    {
        $prefix = "Class: SalsifyHeaders Method: areValid() ";

        if (!$this->matchNames()) {
            $this->logger->error($prefix . "Header Names mismatch ");
            return false;
        }

        if (!$this->validCertUrl()) {
            $this->logger->error($prefix . "Invalid Cert URL ");
            return false;
        }

        if (!$this->setPublicKey()) {
            $this->logger->error($prefix . "Cant setPublic Key");
            return false;
        }
        if (!$this->validSigature()) {
            $this->logger->error($prefix . "Signature invalid");
            return false;
        }
        return true;
    }

    /**
     * @return boolean
     */
    private function hasCertInCache()
    {
        $prefix = "Method hasCertInCache() ";
        if (!file_exists($this->certFile)) {
            $this->logger->error($prefix . "cant open cache file " . $this->certFile);
            return false;
        }
        $fileTime = filemtime($this->certFile);
        if ((time() - $fileTime) > $this->cacheTimout) {
            return false;
        }
        return true;
    }

    /**
     * @return boolean
     */
    private function fetchCertFromUrl()
    {
        $prefix = "Method fetchCertFromUrl() ";
        $ch = curl_init($this->salsifySentHeaders["HTTP_X_SALSIFY_CERT_URL"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $cert = curl_exec($ch);
        if (!$cert) {
            curl_close($ch);
            $this->logger->error($prefix . "Curl Request failed");
            return false;
        }

        $httpResCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpResCode !== 200) {
            curl_close($ch);
            $this->logger->error($prefix . "Http code:" . $httpResCode);
            return false;
        }
        curl_close($ch);
        $this->logger->debug($prefix . "Fetched cert from url after cache timeout");
        $file = fopen($this->certFile, "w") or die("Unable to open file!");
        fwrite($file, $cert);
        fclose($file);
        return true;
    }

    /**
     * @return boolean
     */
    private function matchNames()
    {
        $pattern = '/^HTTP_X_SALSIFY.*/';
        foreach ($this->rawHeaders as $name => $values) {
            if (preg_match($pattern, $name) === 1) {
                $this->salsifySentHeaders[$name] = $values[0];
            }
        }
        //todo check sent headers array not empty
        $difference = array_diff_key($this->salsifySpecedHeaders, $this->salsifySentHeaders);
        if (!empty($difference)) {
            return false;
        }
        return true;
    }

    /**
     * @return boolean
     */
    private function setPublicKey()
    {
        $prefix = "Method: setPublicKey() ";
        if (!$this->hasCertInCache()) {
            if (!$this->fetchCertFromUrl()) {
                return false;
            }
        }
        $this->publicKey = openssl_pkey_get_public(file_get_contents($this->certFile));
        if (!$this->publicKey) {
            $this->logger->error($prefix . "Failed to create key from cert");
            return false;
        }
        return true;
    }

    /**
     * @return boolean
     */
    private function validCertUrl()
    {
        $certUrl = parse_url($this->salsifySentHeaders["HTTP_X_SALSIFY_CERT_URL"]);
        if ($certUrl['scheme'] !== 'https' || $certUrl['host'] !== 'webhooks-auth.salsify.com') {
            return false;
        }
        return true;
    }

    private function validSigature()
    {
        $data = $this->salsifySentHeaders["HTTP_X_SALSIFY_TIMESTAMP"] . "." .
            $this->salsifySentHeaders["HTTP_X_SALSIFY_REQUEST_ID"] . "." .
            $this->salsifySentHeaders["HTTP_X_SALSIFY_ORGANIZATION_ID"] . "." .
            $this->webhookURL . "." .
            $this->requestBody;

        $signature = base64_decode($this->salsifySentHeaders["HTTP_X_SALSIFY_SIGNATURE_V1"], $strict = true);
        $isValid = openssl_verify($data, $signature, $this->publicKey, OPENSSL_ALGO_SHA256);
        if ($isValid === 0) {
            return false;
        }
        return true;

    }

    /**
     * @param $rawHeaders
     */
    public function setRawHeaders($rawHeaders)
    {
        $this->rawHeaders = $rawHeaders;
    }

    /**
     * @param $requestBody
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;
    }

    /**
     * @param $requestUri
     */
    public function setWebhookURL($requestUri)
    {
        $this->webhookURL = $requestUri;
    }
}