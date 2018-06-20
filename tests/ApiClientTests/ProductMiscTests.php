<?php

namespace apiTests;

use Evance\ApiClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

/**
 * A product is created from genericProduct.json by setup() and deleted by
 * tearDown() for each method run. Used for tests where a known product is required.
 * ProductCrudTests should pass first.
 */
final class ProductApiMiscTests extends TestCase
{
    private $client;
    private $token;
    private $genericProduct;
    private $productData;
    private $genericMedia;
    private $headers = ['content-type' => 'application/json'];
    private $sku;
    private $productId;

    /**
     * Run prior to each method.
     * Creates test product.
     */
    public function setUp()
    {
        $this->client = new ApiClient();
        $this->client->loadAuthConfig('../client-credentials-salsify-app.json');
        $this->token = $this->client->fetchAccessTokenWithJwt();
        $this->headers = [
            'content-type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Authorization' => 'Bearer ' . $this->client->getAccessToken()['access_token']
        ];
        $this->genericProduct = file_get_contents("genericProduct.json");
        $this->productData = json_decode($this->genericProduct, true);
        $this->sku = $this->productData["product"]["sku"];
        // Insert new test Product into db
        $url = "/products.json";
        $res = $this->sendRequest($url, 'POST', $this->genericProduct);
        $this->productId = $res['body']['product']['id'];
    }

    public function tearDown()
    {
        $url = "/products/{$this->productId}.json";
        $this->sendRequest($url, 'DELETE');
    }


    /**
     * Ensure the test product doesn't exist in the Db
     * Required for subsequent tests
     */
    public function testSearchProductByInvalidSku()
    {
        $url = "/products.json?skus=INVALID-AZEXIS-SLU-1234";
        $verb = 'GET';

        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $this->expectExceptionCode(422);
        $res = $this->sendRequest($url, $verb);
    }

    /**
     * POST /products/{$productId}/media.json
     * expects a list of media object belonging to product Id
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
