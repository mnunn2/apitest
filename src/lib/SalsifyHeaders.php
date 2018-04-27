<?php

namespace Apiclient;


class SalsifyHeaders
{
    private $logger;
    private $prefix = "SalsifyHeaders: ";
    private $rawHeaders;
    private $salsifySentHeaders;
    private $salsifySpecedHeaders = array(
        "HTTP_X_SALSIFY_TIMESTAMP" => "",
        "HTTP_X_SALSIFY_CERT_URL" => "",
        "HTTP_X_SALSIFY_SIGNATURE_V1" => "",
        "HTTP_X_SALSIFY_REQUEST_ID" => "",
        "HTTP_X_SALSIFY_ORGANIZATION_ID" => ""
    );



    public function __construct($rawHeaders, $logger)
    {
        $this->rawHeaders = $rawHeaders;
        $this->logger = $logger;
    }

    /**
     * @return boolean
     */
    public function areValid()
    {

        if (!$this->matchNames()) {
            $this->logger->error($this->prefix . "Header Names mismatch ");
            return false;
        }
        return true;
    }

    /**
     * @return boolean
     */
    public function matchNames()
    {
        $pattern = '/^HTTP_X_SALSIFY.*/';
        foreach ($this->rawHeaders as $name => $values) {
            if (preg_match($pattern, $name) === 1) {
                $this->salsifySentHeaders[$name] = $values[0];
            }
        }
        $difference = array_diff_key($this->salsifySpecedHeaders, $this->salsifySentHeaders);
        if (!empty($difference)) {
            return false;
        }
        return true;
    }
}