<?php


$map = array(
    'salsify:id' => 'evance_Product_id',
    'salsify:parent_id' => 'evance_Parent_id',
);

$product = array(
    'salsify:id' => '100010',
    'salsify:parent_id' => 'parent-001',
    'salsify:stuff' => 'this is a description',
    'salsify:morestuff' => 'test-001',
);

foreach ($product as $oldName => $value) {
    foreach ($map as $mapName => $newName) {
        if ($oldName === $mapName) {
            $product[$newName] = $product[$oldName];
            unset($product[$oldName]);
        }
    }
}

print_r($product);


