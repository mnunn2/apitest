<?php

namespace Evance\Resource;

use Evance\ApiClient;
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
    private $products;
    private $productData;

    public function setUp()
    {
        $this->client = new ApiClient();
        $this->products = new Products($this->client);
        $this->client->loadAuthConfig('../client-credentials-salsify-app.json');
        $this->productData = json_decode(file_get_contents("genericProduct.json"), true);
        $this->token = $this->client->fetchAccessTokenWithJwt();
    }

    public function testProductsCanBeCreated()
    {
        $this->assertInstanceOf(Products::class, $this->products);
    }

    public function testReturnedToken()
    {
        $this->assertInternalType('string', $this->token["access_token"]);
        $this->assertEquals('Bearer', $this->token["token_type"]);
        print_r("\n" . $this->token["access_token"] . "\n");
    }

    public function testGetProductById()
    {
        //$id = 3053;
        $id = 11;
        $products = $this->products->get($id);
        $this->assertInternalType('array', $products);
        $this->assertEquals($id, $products["product"]["id"]);
        print_r($products["product"]["id"] . "\n");
    }

    public function providerTestSearchProdcutsBy()
    {
        return array(
            array('id', 'ids', ["ids" => "1695, 3053, 3033", "orderBy" => "created:desc"]),
            array('sku', 'skus', ["skus" => "EX-PP-001, EX-PP-002, TENQ01S", "orderBy" => "created:desc"]),
            array('partNumber', 'partNumbers', ["partNumbers" => "123, 456, 789", "orderBy" => "created:desc"]),
            array('barcode', 'barcodes', ["barcodes" => "01-123, 02-456, 03-789", "orderBy" => "created:desc"]),
            array('quickfind', 'quickfinds', ["quickfinds" => "3602, 3608, 3615", "orderBy" => "created:desc"]),
        );
    }

    /**
     * @param $criteria
     * @param $criterias
     * @param $params
     *
     * @dataProvider providerTestSearchProdcutsBy
     */
    public function testSearchProductsByCriteria($criteria, $criterias, $params)
    {
        $response = $this->products->search($params);
        $this->assertInternalType('array', $response);
        $searchCriteria = array_map('trim', explode(",", $params[$criterias]));
        $responseCriteria = [];
        $time = strtotime("now");
        foreach ($response["products"] as $product) {
            array_push($responseCriteria, $product[$criteria]);
            print_r($product[$criteria] . " " . $product["created"] . "\n");
            $newTime = strtotime($product["created"]);
            $this->assertGreaterThan($newTime, $time);
            $time = $newTime;
        }
        // requires valid products with productTypeId of 1 or 3 to pass matching test data above
        $this->assertCount(count($searchCriteria), $response["products"]);
        $this->assertTrue(sort($searchCriteria) == sort($responseCriteria));
        //var_dump($response);
    }

    public function testSearchPagination()
    {
        $x = 2;
        $params = [];
        $params += ["ids" => "1695, 3053, 3033"];
        $params += ["perPage" => $x];
        $params += ["page" => null];
        for ($i = 1; $i <= $x; $i++) {
            $params["page"] = $i;
            $response = $this->products->search($params);
            print_r("\npage " . $response["page"] . "\n");
            print_r("total product items " . $response["total"] . "\n");
            print_r("total pages " . $response["totalPages"] . "\n");
            print_r("next page " . $response["nextPage"] . "\n");
            print_r("is first page " . $response["isFirstPage"] . "\n");
            print_r("is last page " . $response["isLastPage"] . "\n");
            print_r("previous page " . $response["previousPage"] . "\n");
            print_r("products on this page ");
            foreach ($response["products"] as $product) {
                print_r($product["id"] . " ");
            }
            print_r(" \n\n");
        }
    }

    public function testInsertAndUpdateProduct()
    {
        // insert the product into db
        $createData = $this->products->add($this->productData);
        $this->assertArrayHasKey("product", $createData);
        $createId = $createData["product"]["id"];
        $this->assertInternalType("int", $createId);
        print_r("product created with id of " . $createId . "\n");

        // ensure the product now exists in the DB
        $findData = $this->products->get($createId);
        $findId = $findData["product"]["id"];
        $this->assertSame($createId, $findId);
        print_r("product " . $createId . " exists in db" . "\n");

        // update product
        $this->productData["product"]["description"] = "new description";
        // assume we have the productId from a sku search so we know the product exists to be updated
        $this->products->update($createId, $this->productData);
        $updatedData = $this->products->get($createId);
        // N.B the salsify "Item Description is mapped to Evance product "title" in ProductMap
        $this->assertTrue($updatedData["product"]["description"] === "new description", "update invalid");
        print_r("product " . $createId . " updated" . "\n");

        // delete the product
        $this->products->delete($createId);

        // make sure it's deleted
        print_r("check " . $createId . " deleted" . "\n");
        $this->expectException(\GuzzleHttp\Exception\ClientException::class);
        $this->products->get($createId);
    }
}