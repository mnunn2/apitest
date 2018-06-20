<?php

namespace apiTests;

use Evance\ApiClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

/**
 * Used for tests where a known product is required. ProductCrudTests should pass first.
 * A product is created from genericProduct.json by setup() and deleted by
 * tearDown() for each method run.
 */
final class ProductMedia extends TestCase
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
        $this->genericMedia = file_get_contents("genericMedia.json");
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

    public function testMediaByProductId()
    {
        // Add a media object to a product by productId
        $url = "/products/{$this->productId}/media.json";
        $verb = 'POST';

        // create  a media object and get mediaId
        $res = $this->sendRequest($url, $verb, $this->genericMedia);
        $this->assertEquals(201, $res['code']);
        $this->assertArrayHasKey('media', $res['body']);
        $mediaId = (int) $res['body']['media']['id'];
        $res = null;

        // create 2 more media objects
        $this->sendRequest($url, $verb, $this->genericMedia);
        $this->sendRequest($url, $verb, $this->genericMedia);

        // Get a list of media objects by productId
        $url = "/products/{$this->productId}/media.json";
        $verb = 'GET';

        $res = $this->sendRequest($url, $verb);
        $this->assertEquals(200, $res['code']);
        $this->assertTrue(count($res['body']['media']) === 3);
        $res = null;

        // GET media by mediaId
        $url = "/products/{$this->productId}/media/{$mediaId}.json";
        $verb = 'GET';

        $res = $this->sendRequest($url, $verb);
        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('media', $res['body']);
        $res = null;
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
