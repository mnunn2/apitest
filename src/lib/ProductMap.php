<?php

namespace Apiclient;

use Evance\Utils\ObjectMap;
use Evance\Utils\PropertyMap;

class ProductMap extends ObjectMap
{

    public function __construct(&$data, &$payload)
    {
        parent::__construct();
        //NOTE: some fields are omitted to prevent JSON data from setting/modifying internal use variables
        $this->add(new PropertyMap($data, 'sku', $payload, 'Item SKU'));
        $this->add(new PropertyMap($data, 'title', $payload, 'Item Description'));
        $this->add(new PropertyMap($data, 'description', $payload, 'en-GB_ShortDescriptionWebsite'));
        $this->add(new PropertyMap($data, 'barcode', $payload, 'SKU Barcode'));
    }
}
