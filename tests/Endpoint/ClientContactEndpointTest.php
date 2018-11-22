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
use FH\HarvestApiClient\Model\Contact\ClientContact;
use Http\Client\Common\Exception\ClientErrorException;
use \FH\HarvestApiClient\Model\Client\Client as HarvestClient;
use FH\HarvestApiClient\Model\contact\ClientContactCollection;

require_once 'TestClientFactory.php';
require_once 'TestSerializerFactory.php';

/**
 * @author Lars Janssen <lars.janssen@freshheads.com>
 */
class ClientContactEndpointTest extends TestCase
{
    /**
     * @var HttpMockClient
     */
    private $mockHttpClient;

    /**
     * @var ClientContactEndpoint
     */
    private $endpoint;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    protected function setUp()
    {
        $this->mockHttpClient = new HttpMockClient();
        $this->endpoint = new ClientContactEndpoint(
            TestClientFactory::create($this->mockHttpClient),
            TestSerializerFactory::create()
        );
        $this->messageFactory = new MessageFactory\GuzzleMessageFactory();
    }

    public function testListCallsListUrl(): void
    {
        $this->addJsonResponseFromFile('contact/list.json');
        $this->endpoint->list();

        $request = $this->mockHttpClient->getLastRequest();
        $this->assertStringEndsWith('/contacts', (string) $request->getUri());
    }

    public function testListContainsAnArrayOfClientContacts(): void
    {
        $this->addJsonResponseFromFile('contact/list.json');
        $clientContacts = $this->endpoint->list();

        $this->assertContainsOnlyInstancesOf(ClientContact::class, iterator_to_array($clientContacts));
    }

    public function testListReturnsClientContactCollection(): void
    {
        $this->addJsonResponseFromFile('contact/list.json');
        $collection = $this->endpoint->list();

        $this->assertInstanceOf(ClientContactCollection::class, $collection);
        $this->assertEquals(1, $collection->getPage());
        $this->assertEquals(1, $collection->getTotalPages());
        $this->assertEquals(2, $collection->getTotalEntries());
    }

    public function testRetrieveReturnsAClientContact(): void
    {
        $this->addJsonResponseFromFile('contact/123.json');
        $clientContact = $this->endpoint->retrieve(12345);

        $this->assertEquals(4706479, $clientContact->getId());
    }

    public function testUnknownClientContactThrowsAnException(): void
    {
        $this->expectException(ClientErrorException::class);
        $this->expectExceptionCode(404);

        $this->addJsonResponse('', 404);

        $this->endpoint->retrieve(12345999);
    }

    public function testCreateSerializesTheClientContactInTheRequest(): void
    {
        $this->addJsonResponseFromFile('contact/123.json');

        $client = new HarvestClient();
        $client->setId(5735774);
        $client->setName("ABC Corp");

        $clientContact = new ClientContact();
        $clientContact
            ->setId(4706479)
            ->setClientId(1234)
            ->setClient($client)
            ->setTitle("Owner")
            ->setFirstName("Jane")
            ->setLastName('Doe')
            ->setEmail("janedoe@example.com")
            ->setPhoneOffice("(203) 697-8885")
            ->setPhoneMobile("(203) 697-8886")
            ->setFax("(203) 697-8887")
            ->setCreatedAt(\DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', '2017-06-26T21:20:07Z')))
            ->setUpdatedAt(\DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', '2017-06-26T21:20:07Z')));

        $this->endpoint->create($clientContact);

        $request = $this->mockHttpClient->getLastRequest();

        $jsonBody = json_decode($request->getBody()->getContents());

        $this->assertEquals($clientContact->getId(), $jsonBody->id);
        $this->assertEquals($clientContact->getClientId(), $jsonBody->client_id);
        $this->assertEquals($clientContact->getTitle(), $jsonBody->title);
        $this->assertEquals($clientContact->getFirstName(), $jsonBody->first_name);
        $this->assertEquals($clientContact->getLastName(), $jsonBody->last_name);
        $this->assertEquals($clientContact->getEmail(), $jsonBody->email);
        $this->assertEquals($clientContact->getPhoneOffice(), $jsonBody->phone_office);
        $this->assertEquals($clientContact->getPhoneMobile(), $jsonBody->phone_mobile);
        $this->assertEquals($clientContact->getFax(), $jsonBody->fax);
        $this->assertEquals($clientContact->getCreatedAt()->getTimestamp(), \DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', $jsonBody->created_at))->getTimestamp());
        $this->assertEquals($clientContact->getUpdatedAt()->getTimestamp(), \DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', $jsonBody->updated_at))->getTimestamp());

        $this->assertEquals($clientContact->getClient()->getId(), $jsonBody->client->id);
        $this->assertEquals($clientContact->getClient()->getName(), $jsonBody->client->name);
    }

    public function testUpdateSerializesTheClientContactInTheRequest(): void
    {
        $client = new HarvestClient();
        $client->setId(5735774);
        $client->setName("ABC Corp");

        $this->addJsonResponseFromFile('contact/123.json');

        $clientContact = new ClientContact();
        $clientContact
            ->setId(4706479)
            ->setClientId(1234)
            ->setClient($client)
            ->setTitle("Owner")
            ->setFirstName("Jane")
            ->setLastName('Doe')
            ->setEmail("janedoe@example.com")
            ->setPhoneOffice("(203) 697-8885")
            ->setPhoneMobile("(203) 697-8886")
            ->setFax("(203) 697-8887")
            ->setCreatedAt(\DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', '2017-06-26T21:20:07Z')))
            ->setUpdatedAt(\DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', '2017-06-26T21:20:07Z')));

        $this->endpoint->update($clientContact);

        $request = $this->mockHttpClient->getLastRequest();

        $jsonBody = json_decode($request->getBody()->getContents());

        $this->assertEquals($clientContact->getId(), $jsonBody->id);
        $this->assertEquals($clientContact->getClientId(), $jsonBody->client_id);
        $this->assertEquals($clientContact->getTitle(), $jsonBody->title);
        $this->assertEquals($clientContact->getFirstName(), $jsonBody->first_name);
        $this->assertEquals($clientContact->getLastName(), $jsonBody->last_name);
        $this->assertEquals($clientContact->getEmail(), $jsonBody->email);
        $this->assertEquals($clientContact->getPhoneOffice(), $jsonBody->phone_office);
        $this->assertEquals($clientContact->getPhoneMobile(), $jsonBody->phone_mobile);
        $this->assertEquals($clientContact->getFax(), $jsonBody->fax);
        $this->assertEquals($clientContact->getCreatedAt()->getTimestamp(), \DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', $jsonBody->created_at))->getTimestamp());
        $this->assertEquals($clientContact->getUpdatedAt()->getTimestamp(), \DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d\TH:i:sO', $jsonBody->updated_at))->getTimestamp());

        $this->assertEquals($clientContact->getClient()->getId(), $jsonBody->client->id);
        $this->assertEquals($clientContact->getClient()->getName(), $jsonBody->client->name);
    }

    public function testDeleteExecutesADeleteRequestWithTheGivenId(): void
    {
        $this->endpoint->delete(12345);

        $request = $this->mockHttpClient->getLastRequest();

        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertStringEndsWith('/contacts/12345', (string) $request->getUri());
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
