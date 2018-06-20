<?php

namespace Evance\Resource;

use Evance\ApiClient;
use PHPUnit\Framework\TestCase;

final class ProductMediaResourceTest extends TestCase
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
     *  set up ends media tests begin:
     */

    public function testProductMediaCanBeCreated()
    {
        $media = new ProductMedia($this->client);
        $this->assertInstanceOf(ProductMedia::class, $media);
    }

    public function testMediaCRUD()
    {
        $mediaId = null;
        $mediaData = json_decode(file_get_contents("genericMedia.json"), true);
        $media = new ProductMedia($this->client);

        //** test add media */
        // todo mike; check why product id is returned as int and media id as string
        $response = $media->add($this->productId, $mediaData);
        $this->assertArrayHasKey("media", $response);
        $mediaId = $response["media"]["id"];
        $this->assertRegExp("/^[0-9]+$/", $mediaId);
        $response = null;

        //** test get media by productId */
        $response = $media->get($this->productId);
        $this->assertArrayHasKey("media", $response);
        $mediaId = $response["media"][0]["id"];
        $this->assertRegExp("/^[0-9]+$/", $mediaId);
        $response = null;

        //** test update media */
        $response = $media->update($this->productId, $mediaId, $mediaData);
        $this->assertArrayHasKey("media", $response);
        $mediaId = $response["media"]["id"];
        $this->assertRegExp("/^[0-9]+$/", $mediaId);
        $response = null;

        //** test get media by mediaId */
        $response = $media->getById($this->productId, $mediaId);
        $this->assertArrayHasKey("media", $response);
        $mediaId = $response["media"]["id"];
        $this->assertRegExp("/^[0-9]+$/", $mediaId);
        $response = null;

        //** test delete media by mediaId */
        $response = $media->delete($this->productId, $mediaId);
        $this->assertArrayHasKey("success", $response);
        $response = null;

        //** check media is deleted */
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $media->get($this->productId);
    }
}
