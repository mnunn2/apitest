<?php

namespace apiTests;

use Evance\ApiClient;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

/**
 * Setup is run prior to each method which results in an auth request each time.
 * The methods are chained using the (@)depends annotation passing the return value
 * to the next method.
 */
final class ProductCrudTests extends TestCase
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

    /**
     * Ensure the test product SKU doesn't exist in the Db
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

        $newDescription = "This is an updated description";
        $this->productData["product"]["description"] = $newDescription;
        $res = $this->sendRequest($url, $verb, json_encode($this->productData));
        $updatedData = $res['body'];
        $this->assertTrue($updatedData["product"]["description"] === $newDescription, "update invalid");
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
