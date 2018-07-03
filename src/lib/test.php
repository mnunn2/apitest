<?php

$json = json_decode(file_get_contents('test.json'));
$products = $json->products;
//$locales = new \Ds\Set();
// $locales = ['en-GB', 'fr-FR', 'de-DE'];
$translations = [];
//$translations[$locale] = [];
foreach ($products[0] as $key => $val) {
    if (preg_match("/^([a-z][a-z]-[A-Z][A-Z])_(.+)/", $key, $match)) {
        $payloadLocales[$match[1]] = null;
        if (array_key_exists($match[1], $translations)) {
            $translations[$match[1]][$match[2]] = $val;
        } else {
            $translations[$match[1]] = [$match[2] => $val];
        }
    }
}

print_r($translations);
