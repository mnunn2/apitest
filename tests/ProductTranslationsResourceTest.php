<?php

namespace Evance\Resource;

use Evance\ApiClient;
use PHPUnit\Framework\TestCase;

final class ProductTranslationsResourceTest extends TestCase
{
    private $client;
    private $token;
    private $products;
    private $productData;
    private $productId;

    public function setUp()
    {
        $this->client = new ApiClient();
        $this->products = new Products($this->client);
        $this->client->loadAuthConfig('../client-credentials-salsify-app.json');
        $this->productData = json_decode(file_get_contents("genericProduct.json"), true);
        $this->token = $this->client->fetchAccessTokenWithJwt();
        $data = $this->products->add($this->productData);
        $this->productId = $data["product"]["id"];
    }

    protected function tearDown()
    {
        $this->products->delete($this->productId);
    }

    public function testProductExists()
    {
        // ensure the product now exists in the DB
        $res = $this->products->get($this->productId);
        $findId = $res["product"]["id"];
        $this->assertSame($this->productId, $findId);
    }

    /**
     *  set up ends, translations tests begin:
     */

    public function testProductTranslationsCanBeCreated()
    {
        $translations = new ProductTranslations($this->client);
        $this->assertInstanceOf(ProductTranslations::class, $translations);
    }

    public function testTranslationsCRUD()
    {
        $translationsId = null;
        $translationsData = json_decode(file_get_contents("genericTranslations.json"), true);
        $translations = new ProductTranslations($this->client);

        //** test add translations */
        // todo mike; check why product id is returned as int and translations id as string
        $response = $translations->add($this->productId, $translationsData);
        $this->assertArrayHasKey("translation", $response);
        $translationsId = $response["translation"]["id"];
        $this->assertRegExp("/^[0-9]+$/", $translationsId);
        $response = null;

        //** test get translations by productId */
        $response = $translations->get($this->productId);
        $this->assertArrayHasKey("translations", $response);
        $translationsId = $response["translations"][0]["id"];
        $this->assertRegExp("/^[0-9]+$/", $translationsId);
        $response = null;

        //** test update translations */
        $response = $translations->update($this->productId, $translationsId, $translationsData);
        $this->assertArrayHasKey("translation", $response);
        $translationsId = $response["translation"]["id"];
        $this->assertRegExp("/^[0-9]+$/", $translationsId);
        $response = null;

        //** test get translations by translationsId */
        $response = $translations->getById($this->productId, $translationsId);
        $this->assertArrayHasKey("translation", $response);
        $translationsId = $response["translation"]["id"];
        $this->assertRegExp("/^[0-9]+$/", $translationsId);
        $response = null;

        //** test delete translations by translationsId */
        $response = $translations->delete($this->productId, $translationsId);
        $this->assertArrayHasKey("success", $response);
        $response = null;

        //** check translations is deleted */
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $translations->get($this->productId);
    }
}
