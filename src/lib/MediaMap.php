<?php

namespace Apiclient;

use Evance\Utils\ObjectMap;
use Evance\Utils\PropertyMap;

class MediaMap extends ObjectMap
{

    public function __construct(&$data, &$payload)
    {
        parent::__construct();
        //NOTE: some fields are omitted to prevent JSON data from setting/modifying internal use variables
        $this->add(new PropertyMap($data, 'image', $payload, 'JPG'));
    }
}
