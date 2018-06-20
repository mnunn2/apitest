<?php

namespace apiTests;

use Evance\ApiClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

/**
 * May not be needed see ProductApiTests
 * @package apiTests
 */
final class ProductMediaApiTests extends TestCase
{
    private $client;
    private $token;
    private $genericProduct;
    private $productData;
    private $genericMedia;
    private $headers = ['content-type' => 'application/json'];
    private $sku;

    /**
     * Run prior to each method.
     */
    public function setUp()
    {
        $this->client = new ApiClient();
        $this->client->loadAuthConfig('../client-credentials-salsify-app.json');
        $this->genericProduct = file_get_contents("genericProduct.json");
        $this->token = $this->client->fetchAccessTokenWithJwt();
        $this->genericMedia = file_get_contents("genericMedia.json");
        $this->headers = [
            'content-type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Authorization' => 'Bearer ' . $this->client->getAccessToken()['access_token']
        ];
        $this->productData = json_decode($this->genericProduct, true);
        $this->sku = $this->productData["product"]["sku"];
    }

    public function testClientCanBeCreated()
    {
        $this->assertInstanceOf(ApiClient::class, $this->client);
    }

    public function testReturnedToken()
    {
        $this->assertInternalType('string', $this->token["access_token"]);
        $this->assertEquals('Bearer', $this->token["token_type"]);
        print_r("\n" . $this->token["access_token"] . "\n");
    }

    /**
     * Ensure the test product doesn't exist in the Db
     * Required for subsequent tests
     */
    public function testSearchProductByInvalidSku()
    {
        $url = "/products.json?skus={$this->sku}";
        $verb = 'GET';

        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $this->expectExceptionCode(422);
        $res = $this->sendRequest($url, $verb);
    }

    /**
     * @depends testSearchProductByInvalidSku
     * @return int
     */
    public function testInsertProduct()
    {
        $url = "/products.json";
        $verb = 'POST';

        $res = $this->sendRequest($url, $verb, $this->genericProduct);
        $createData = $res['body'];
        $this->assertArrayHasKey("product", $createData);
        $createId = $createData["product"]["id"];
        $this->assertInternalType("int", $createId);
        print_r("product created with id of " . $createId . "\n");
        return  $createId;
    }

    /**
     * @depends testInsertProduct
     * @param int $createId
     * @return int
     */
    public function testGetProductById($createId)
    {
        $url = "/products/{$createId}.json";
        $verb = 'GET';

        $res = $this->sendRequest($url, $verb);
        $findData = $res['body'];
        $findId = $findData["product"]["id"];
        $this->assertSame($createId, $findId);
        print_r("product " . $createId . " exists in db" . "\n");
        return $createId;
    }

    /**
     * @depends testGetProductById
     * @param int $createId
     * @return int
     */
    public function testUpdateProduct($createId)
    {
        $url = "/products/{$createId}.json";
        $verb = 'PUT';

        $this->productData["product"]["description"] = "new description";
        $res = $this->sendRequest($url, $verb, json_encode($this->productData));
        $updatedData = $res['body'];
        $this->assertTrue($updatedData["product"]["description"] === "new description", "update invalid");
        print_r("product " . $createId . " updated" . "\n");
        return $createId;
    }

    /**
     * @depends testUpdateProduct
     * @param int $createId
     */
    public function testDeleteProductById($createId)
    {
        $url = "/products/{$createId}.json";
        $verb = 'DELETE';

        $this->sendRequest($url, $verb);

        // make sure it's deleted
        print_r("check " . $createId . " deleted" . "\n");
        $checkUrl = "/products/{$createId}.json";
        $checkVerb = 'Get';
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $this->sendRequest($checkUrl, $checkVerb);
    }

    /**
     * POST /products/{$productId}/media.json
     */
    public function testAddMediaByProductId()
    {
        $productId = 3033;
        $url = "/products/{$productId}/media.json";
        $res = $this->sendRequest($url, 'POST', $this->genericMedia);
        $this->assertTrue(property_exists($res['body'], 'media'));
        $this->assertEquals(201, $res['code']);
    }

    /**
     * GET /products/{$productId}/media.json
     * expects a list of media object belonging to product Id
     */
    public function testGetAllMediaByProductId()
    {
        $productId = 30333;
        $url = "/products/{$productId}/media.json";
        $res = $this->sendRequest($url, 'GET', '');
        $this->assertTrue(property_exists($res['body'], 'media'));
        $this->assertEquals(200, $res['code']);
        //print_r($res['body']);
    }

    /**
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function testGetAllMediaByInvalidProductId()
    {
        $invalidProductId = 9999999;
        $url = "/products/{$invalidProductId}/media.json";
        $res = $this->sendRequest($url, 'GET', '');
        $this->assertTrue(property_exists($res['body'], 'media'));
        $this->assertEquals(200, $res['code']);
        //print_r($res['body']);
    }

    /**
     * GET /products/{$productId}/media/{mediaId}.json
     * expects a list of media object belonging to product Id
     */
    public function testGetMediaByMediaId()
    {
        $productId = 3033;
        $mediaId = 2168;
        $url = "/products/{$productId}/media/{$mediaId}.json";
        $res = $this->sendRequest($url, 'GET', '');
        $this->assertTrue(property_exists($res['body'], 'media'));
        $this->assertEquals(200, $res['code']);
        //print_r($res['body']);
    }

    private function sendRequest($url, $verb, $body = '')
    {
        $uri = $this->client->getResourceUri($url);
        $request = new Request($verb, $uri, $this->headers, $body);
        $http = new HttpClient(['verify' => false]);
        $response = $http->send($request);
        $data['body'] = json_decode((string) $response->getBody(), true);
        $data['code'] = $response->getStatusCode();
        return $data;
    }
}
