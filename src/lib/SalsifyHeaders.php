<?php

namespace Apiclient;

use Monolog\Logger;

class SalsifyHeaders
{
    private $httpResCode = 0;
    private $requestBody;
    private $salsifyCert;
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
     * @param $rawHeaders
     * @param Logger $logger
     * @param $requestBody
     */
    public function __construct($rawHeaders, Logger $logger, $requestBody)
    {
        $this->requestBody = $requestBody;
        $this->rawHeaders = $rawHeaders;
        $this->logger = $logger;
    }

    /**
     * @return boolean
     */
    public function areValid()
    {
        $prefix = "Class: SalsifyHeaders ";

        if (!$this->matchNames()) {
            $this->logger->error($prefix . "Header Names mismatch ");
            return false;
        }

        if (!$this->validCertUrl()) {
            $this->logger->error($prefix . "Invalid Cert URL ");
            return false;
        }

        if (!$this->fetchCert()) {
            $this->logger->error($prefix . "Curl response HTTP: $this->httpResCode");
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
    private function fetchCert()
    {
        $ch = curl_init($this->salsifySentHeaders["HTTP_X_SALSIFY_CERT_URL"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ( !$this->salsifyCert = curl_exec($ch)) {
            curl_close($ch);
            return false;
        }

        $this->httpResCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($this->httpResCode !== 200) {
            curl_close($ch);
            return false;
        }

        curl_close($ch);
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
            'http://client-client-test.a3c1.starter-us-west-1.openshiftapps.com/dumpData' . "." .
            $this->requestBody;

        $publicKey = openssl_pkey_get_public($this->salsifyCert);
        $signature = base64_decode($this->salsifySentHeaders["HTTP_X_SALSIFY_SIGNATURE_V1"], $strict = true);
        $isValid = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($isValid === 0) {
            return false;
        }
        return true;

    }
}