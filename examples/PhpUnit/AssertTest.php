<?php

use SwaggerAssertions\PhpUnit\AssertsTrait;
use SwaggerAssertions\SchemaManager;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * PHPUnit integration example.
 */
class AssertTest extends \PHPUnit\Framework\TestCase
{
    use AssertsTrait;

    /**
     * @var SchemaManager
     */
    protected static $schemaManager;

    /**
     * @var ClientInterface
     */
    protected $guzzleHttpClient;

    public static function setUpBeforeClass()
    {
        self::$schemaManager = SchemaManager::fromUri('http://petstore.swagger.io/v2/swagger.json');
    }

    protected function setUp()
    {
        $this->guzzleHttpClient = new Client(['headers' => ['User-Agent' => 'https://github.com/merzdan/SwaggerAssertions']]);
    }

    public function testFetchPetBodyMatchDefinition()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://petstore.swagger.io/v2/pet/findByStatus');
        $request->withAddedHeader('Accept', 'application/json');

        $response     = $this->guzzleHttpClient->send($request);
        $responseBody = json_decode($response->getBody());

        $this->assertResponseBodyMatch($responseBody, self::$schemaManager, '/v2/pet/findByStatus', 'get', 200);
    }
}
