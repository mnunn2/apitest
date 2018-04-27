<?php


use Evance\ApiClient;
use Evance\Service\Shipping\Service;
use PHPUnit\Framework\TestCase;

/**
 * Class ContactServiceTest
 * Tests Api Client Product Resource apiclient/src/Evance/Products
 * Also tests creation of ApiClient with valid credentials.
 *
 */
final class ServiceTest extends TestCase
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

    public function testIsActive() {
        $Service = new Service($this->client);
        $this->assertInstanceOf(Service::class, $Service);
        $productArray = $Service->isActive();
        //$this->assertInternalType('array', $productArray);
        //$this->assertEquals($id, $productArray["product"]["id"]);
        var_dump($productArray);
    }
}