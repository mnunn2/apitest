<?php


use Evance\ApiClient;
use Evance\Service\Product;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductResourceTest
 * Tests Api Client Product Resource apiclient/src/Evance/Products
 * Also tests creation of ApiClient with valid credentials.
 *
 */
final class ProductServiceTest extends TestCase
{
    private $client;
    private $token

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

    public function testIsStocked() {
        $product = new Product($this->client, array("foo" => "bar"));
        $this->assertInstanceOf(Product::class, $product);
        $product->fetchById(3053);
        //var_dump($product->isStocked());
        //var_dump($product->foo);
        var_dump($product->title); // title now a dynamic property after fetch
        //$this->assertEquals($id, $productArray["product"]["id"]);
        //var_dump($product);
    }
}