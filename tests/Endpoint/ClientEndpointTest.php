<?php

/*
 * This file is part of the Freshheads Harvest API Client library.
 *
 * (c) Freshheads B.V. <info@freshheads.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FH\HarvestApiClient\Endpoint;

use PHPUnit\Framework\TestCase;
use Http\Message\MessageFactory;
use Http\Mock\Client as HttpMockClient;
use FH\HarvestApiClient\Model\Client\Client;
use FH\HarvestApiClient\Model\Client\ClientCollection;
use Http\Client\Common\Exception\ClientErrorException;

require_once 'TestClientFactory.php';
require_once 'TestSerializerFactory.php';

/**
 * @author Joris van de Sande <joris.van.de.sande@freshheads.com>
 */
class ClientEndpointTest extends TestCase
{
    /**
     * @var HttpMockClient
     */
    private $mockHttpClient;

    /**
     * @var ClientEndpoint
     */
    private $endpoint;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    protected function setUp()
    {
        $this->mockHttpClient = new HttpMockClient();
        $this->endpoint = new ClientEndpoint(
            TestClientFactory::create($this->mockHttpClient),
            TestSerializerFactory::create()
        );
        $this->messageFactory = new MessageFactory\GuzzleMessageFactory();
    }

    public function testListCallsListUrl(): void
    {
        $this->addJsonResponseFromFile('client/list.json');
        $this->endpoint->list();

        $request = $this->mockHttpClient->getLastRequest();
        $this->assertStringEndsWith('/clients', (string) $request->getUri());
    }

    public function testListContainsAnArrayOfClients(): void
    {
        $this->addJsonResponseFromFile('client/list.json');
        $clients = $this->endpoint->list();

        $this->assertContainsOnlyInstancesOf(Client::class, iterator_to_array($clients));
    }

    public function testListReturnsClientCollection(): void
    {
        $this->addJsonResponseFromFile('client/list.json');
        $collection = $this->endpoint->list();

        $this->assertInstanceOf(ClientCollection::class, $collection);
        $this->assertEquals(1, $collection->getPage());
        $this->assertEquals(1, $collection->getTotalPages());
        $this->assertEquals(2, $collection->getTotalEntries());
    }

    public function testRetrieveReturnsAClient(): void
    {
        $this->addJsonResponseFromFile('client/12345.json');
        $client = $this->endpoint->retrieve(12345);

        $this->assertEquals(12345, $client->getId());
    }

    public function testUnknownClientThrowsAnException(): void
    {
        $this->expectException(ClientErrorException::class);
        $this->expectExceptionCode(404);

        $this->addJsonResponse('', 404);

        $this->endpoint->retrieve(12345999);
    }

    public function testCreateSerializesTheClientInTheRequest(): void
    {
        $this->addJsonResponseFromFile('client/12345.json');
        
        $client = new Client();
        $client
            ->setId(12345)
            ->setName('123 Industries')
            ->setIsActive(true)
            ->setAddress("123 Main St.\r\nAnytown, LA 71223")
            ->setCreatedAt(\DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', '2017-06-26T21:02:12Z')))
            ->setUpdatedAt(\DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', '2017-06-26T21:34:11Z')))
            ->setCurrency('EUR');

        $this->endpoint->create($client);

        $request = $this->mockHttpClient->getLastRequest();

        $jsonBody = json_decode($request->getBody()->getContents());

        $this->assertEquals($client->getId(), $jsonBody->id);
        $this->assertEquals($client->getName(), $jsonBody->name);
        $this->assertEquals($client->getIsActive(), $jsonBody->is_active);
        $this->assertEquals($client->getAddress(), $jsonBody->address);
        $this->assertEquals($client->getCreatedAt()->getTimestamp(), \DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', $jsonBody->created_at))->getTimestamp());
        $this->assertEquals($client->getUpdatedAt()->getTimestamp(), \DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', $jsonBody->updated_at))->getTimestamp());
        $this->assertEquals($client->getCurrency(), $jsonBody->currency);
    }

    public function testUpdateSerializesTheClientInTheRequest(): void
    {
        $this->addJsonResponseFromFile('client/12345.json');
        $client = new Client();
        $client
            ->setId(12345)
            ->setName('123 Industries')
            ->setCurrency('EUR');

         $this->endpoint->update($client);

        $request = $this->mockHttpClient->getLastRequest();

        $jsonBody = json_decode($request->getBody()->getContents());

        $this->assertEquals($client->getId(), $jsonBody->id);
        $this->assertEquals($client->getName(), $jsonBody->name);
        $this->assertEquals($client->getCurrency(), $jsonBody->currency);
    }

    public function testDeleteExecutesADeleteRequestWithTheGivenId(): void
    {
        $this->endpoint->delete(12345);

        $request = $this->mockHttpClient->getLastRequest();

        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertStringEndsWith('/clients/12345', (string) $request->getUri());
    }

    private function addJsonResponseFromFile(string $filename, int $statusCode = 200): void
    {
        $body = file_get_contents(__DIR__ . '/' . $filename);

        $this->addJsonResponse($body, $statusCode);
    }

    private function addJsonResponse(string $body, int $statusCode = 200): void
    {
        $response = $this->messageFactory->createResponse(
            $statusCode,
            null,
            [
                'Content-Type' => 'application/json'
            ],
            $body
        );

        $this->mockHttpClient->addResponse($response);
    }
}
