<?php

namespace Evance\Resource;

use Evance\ApiClient;
use PHPUnit\Framework\TestCase;

final class ProductSpecificationResourceTest extends TestCase
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
     *  set up ends specification tests begin:
     */

    public function testProductSpecificationCanBeCreated()
    {
        $spec = new ProductSpecification($this->client);
        $this->assertInstanceOf(ProductSpecification::class, $spec);
    }

    public function testSpecificationCRD()
    {
        $specId = null;
        $specData = json_decode(file_get_contents("genericSpec.json"), true);
        $spec = new ProductSpecification($this->client);

        //** test add spec */
        $response = $spec->add($this->productId, $specData);
        $this->assertArrayHasKey("specifications", $response);
        $specId = $response["specifications"][0]["id"];
        $this->assertInternalType("int", $specId);
        $valueId = $response["specifications"][0]["value"]["id"];
        $this->assertInternalType("int", $specId);
        $response = null;

        //** test get specification by productId */
        $response = $spec->get($this->productId);
        $this->assertArrayHasKey("specifications", $response);
        $specId = $response["specifications"][0]["id"];
        $this->assertInternalType("int", $specId);
        $response = null;

        //** test delete value by value Id */
        $response = $spec->delete($this->productId, $valueId);
        $this->assertArrayHasKey("success", $response);
        $response = null;

        //** check specification is deleted */
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $spec->get($this->productId);
    }
}
