<?php

use SwaggerAssertions\PhpUnit\AssertsTrait;
use SwaggerAssertions\SchemaManager;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * PHPUnit integration example.
 */
class LocalFileTest extends \PHPUnit\Framework\TestCase
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
        $filePath = __DIR__ . '/../fixtures/pet_store.json';

        // Use file:// for local files
        self::$schemaManager = new SchemaManager(json_decode(file_get_contents($filePath)));
    }

    protected function setUp()
    {
        $this->guzzleHttpClient = new Client(['headers' => ['User-Agent' => 'https://github.com/Maks3w/SwaggerAssertions']]);
    }

    public function testFetchPetBodyMatchDefinition()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://petstore.swagger.io/v2/pet/findByStatus', [
            GuzzleHttp\RequestOptions::JSON
        ]);
        $request->withAddedHeader('Content-Type', 'application/json');

        $response = $this->guzzleHttpClient->send($request);
        $responseBody = json_decode($response->getBody());

        $this->assertResponseBodyMatch($responseBody, self::$schemaManager, '/v2/pet/findByStatus', 'get', 200);
    }
}
