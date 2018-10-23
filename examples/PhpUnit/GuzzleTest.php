<?php

use SwaggerAssertions\PhpUnit\GuzzleAssertsTrait;
use SwaggerAssertions\SchemaManager;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * PHPUnit-Guzzle integration example.
 */
class GuzzleTest extends \PHPUnit\Framework\TestCase
{
    use GuzzleAssertsTrait;

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
        $this->guzzleHttpClient = new Client(['headers' => ['User-Agent' => 'https://github.com/Maks3w/SwaggerAssertions']]);
    }

    public function testFetchPetMatchDefinition()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://petstore.swagger.io/v2/pet/findByStatus', [
            GuzzleHttp\RequestOptions::JSON
        ]);
        $request->withAddedHeader('Content-Type', 'application/json');

        $response = $this->guzzleHttpClient->send($request);

        $this->assertResponseAndRequestMatch($response, $request, self::$schemaManager);
    }

    public function testOnlyResponse()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://petstore.swagger.io/v2/pet/findByStatus', [
            GuzzleHttp\RequestOptions::JSON
        ]);
        $request->withAddedHeader('Content-Type', 'application/json');

        $response = $this->guzzleHttpClient->send($request);

        $this->assertResponseMatch($response, self::$schemaManager, '/v2/pet/findByStatus', 'get');
    }
}
