<?php

namespace apiTests;

use Evance\ApiClient;
use PHPUnit\Framework\TestCase;

final class ApiAuthenticationTests extends TestCase
{
    private $client;
    private $token;

    /**
     * Run prior to each method.
     */
    public function setUp()
    {
        $this->client = new ApiClient();
        $this->client->loadAuthConfig('../client-credentials-salsify-app.json');
        $this->token = $this->client->fetchAccessTokenWithJwt();
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

}
