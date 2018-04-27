<?php


use Evance\ApiClient;
use Evance\Resource\Products;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductResourceTest
 * Tests Api Client Product Resource apiclient/src/Evance/Products
 * Also tests creation of ApiClient with valid credentials.
 *
 */
final class ProductResourceTest extends TestCase
{
    private $client;
    private $token;

    public function setUp() {
        $this->client = new ApiClient();
        $this->client->loadAuthConfig('../client-credentials-salsify-app.json');
        $this->token = $this->client->fetchAccessTokenWithJwt();
    }

    public function testClientCanBeCreated()
    {
        $this->assertInstanceOf(ApiClient::class, $this->client);
    }

    public function testReturnedToken() {
        $this->assertInternalType('string', $this->token["access_token"]);
        $this->assertEquals('Bearer', $this->token["token_type"]);
    }

    public function testGetProduct() {
        $id = 3053;
        $products = new Products($this->client);
        $this->assertInstanceOf(Products::class, $products);
        $productArray = $products->get($id);
        $this->assertInternalType('array', $productArray);
        $this->assertEquals($id, $productArray["product"]["id"]);
        var_dump($productArray["product"]["allowEnquiries"]);
    }
}