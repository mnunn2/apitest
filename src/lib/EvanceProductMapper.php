<?php

namespace Apiclient;

class EvanceProductMapper
{

    private $product;
    private $map = array(
        // todo mike: check if "Item SKU" or "salsify:id" should be used for SKU
        // salsify name => common name (as opposed to data name)
        'Item SKU' => 'sku',
        'SKU Barcode' => 'barcode',
        'Item Description' => 'title',
        'en-GB_ShortDescription' => 'description',
        'SKU Width' => 'packagedWidth',
        'SKU Height' => 'packagedHeight',
        'SKU Length' => 'packagedDepth',
    );

    public function __construct(array $salsifyProduct)
    {
        $this->product = $salsifyProduct;
        $this->mapProduct();
    }

    private function mapProduct()
    {
        foreach ($this->product as $oldName => $value) {
            foreach ($this->map as $mapName => $newName) {
                if ($oldName === $mapName) {
                    $this->product[$newName] = $this->product[$oldName];
                    unset($this->product[$oldName]);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getProduct()
    {
        return $this->product;
    }
}
