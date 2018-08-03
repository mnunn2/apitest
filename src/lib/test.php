<?php

namespace Apiclient;

use Evance\ApiClient;
use Evance\Resource\ProductTranslations;

require '../../vendor/autoload.php';

const CREDENTIALS = '../../client-credentials-salsify-app.json';

// setup apiclient
$client = new ApiClient();
$translationsApi = new ProductTranslations($client);
$client->loadAuthConfig(CREDENTIALS);
$client->fetchAccessTokenWithJwt();

$productId = 1;

$translations = json_decode(file_get_contents('translations.json'));
$validLocales = fetchValidLocales($productId, $translationsApi);

foreach ($translations as $locale => $translationPayload) {
    $locale = mb_strtolower($locale);
    $translationData = new \stdClass();
    if (array_key_exists($locale, $validLocales)) {
        $translationMapper = new TranslationMap($translationData, $translationPayload);
        $translationMapper->assignLeft(false);
        if (!empty((array) $translationData)) {
            $translationData = ['translations' => (array)$translationData];
            $translationData['translations']['language'] = $validLocales[$locale]['language'];
            $translationData['translations']['country'] = $validLocales[$locale]['country'];
            print_r($translationData);

            // insert or update translations
            if ($validLocales[$locale]['id']) {
                //update with translation is
                try {
                    $result = $translationsApi->update($productId, $validLocales[$locale]['id'], $translationData);
                    echo 'updated \n';
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    echo $e->getMessage();
                }
            } else {
                // insert translation
                try {
                    $result = $translationsApi->add($productId, $translationData);
                    echo 'inserted \n';
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    echo $e->getMessage();
                }
            }
        }
    }
}

/**
 * Returns an array of valid locales with a translation id
 * if it exists for a given prodcutId
 * @param $translationsApi
 * @return array
 */
function fetchValidLocales($prodcutId, $translationsApi)
{
    // get list of valid locales
    $localesData = null;
    $validLocales = [];
    try {
        $localesData = $translationsApi->getLocales($prodcutId);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo "translation already exists\n";// . $e->getMessage();
    }
    if ($localesData) {
        // $localeData['id'] refers to the local eg en-GB
        foreach ($localesData['locales'] as $localeData) {
            $validLocales[$localeData['id']] = ['id' => null];
            $validLocales[$localeData['id']] += ['language' => $localeData['language']];
            $validLocales[$localeData['id']] += ['country' => $localeData['country']];
        }
    }

    // check if translations exist
    $xlations = null;
    $translationId = null;
    try {
        $xlations = $translationsApi->get($prodcutId);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo $e->getMessage();
    }
    if ($xlations) {
        foreach ($xlations['translations'] as $xlation) {
            $xlationLocale = $xlation['language'] . "-" . mb_strtolower($xlation['country']);
            if (array_key_exists($xlationLocale, $validLocales)) {
                $validLocales[$xlationLocale]['id'] = $xlation['id'];
            }
        }
    }
    return $validLocales;
}
