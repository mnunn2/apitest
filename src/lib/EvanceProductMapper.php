<?php

namespace Apiclient;


class EvanceProductMapper
{

    private $product;
    private $map = array(
        'salsify:id' => 'evance_product_id',
        'salsify:parent_id' => 'evance_Parent_id',
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