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
use FH\HarvestApiClient\Model\Invoice\Invoice;
use Http\Client\Common\Exception\ClientErrorException;
use FH\HarvestApiClient\Model\Invoice\InvoiceCollection;

require_once 'TestClientFactory.php';
require_once 'TestSerializerFactory.php';

/**
 * @author Joris van de Sande <joris.van.de.sande@freshheads.com>
 */
class InvoiceEndpointTest extends TestCase
{
    /**
     * @var HttpMockClient
     */
    private $mockHttpClient;

    /**
     * @var InvoiceEndpoint
     */
    private $endpoint;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    protected function setUp()
    {
        $this->mockHttpClient = new HttpMockClient();
        $this->endpoint = new InvoiceEndpoint(
            TestClientFactory::create($this->mockHttpClient),
            TestSerializerFactory::create()
        );
        $this->messageFactory = new MessageFactory\GuzzleMessageFactory();
    }

    public function testListCallsListUrl(): void
    {
        $this->addJsonResponseFromFile('invoice/list.json');
        $this->endpoint->list();

        $request = $this->mockHttpClient->getLastRequest();
        $this->assertStringEndsWith('/invoices', (string) $request->getUri());
    }

    public function testListContainsAnArrayOfInvoices(): void
    {
        $this->addJsonResponseFromFile('invoice/list.json');
        $invoices = $this->endpoint->list();

        $this->assertContainsOnlyInstancesOf(Invoice::class, iterator_to_array($invoices));
    }

    public function testListReturnsInvoiceCollection(): void
    {
        $this->addJsonResponseFromFile('invoice/list.json');
        $collection = $this->endpoint->list();

        $this->assertInstanceOf(InvoiceCollection::class, $collection);
        $this->assertEquals(1, $collection->getPage());
        $this->assertEquals(1, $collection->getTotalPages());
        $this->assertEquals(2, $collection->getTotalEntries());
    }

    public function testRetrieveReturnsAInvoice(): void
    {
        $this->addJsonResponseFromFile('invoice/123.json');
        $invoice = $this->endpoint->retrieve(123);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(123, $invoice->getId());
    }

    public function testUnknownInvoiceThrowsAnException(): void
    {
        $this->expectException(ClientErrorException::class);
        $this->expectExceptionCode(404);

        $this->addJsonResponse('', 404);

        $this->endpoint->retrieve(12345999);
    }

    public function testCreateSerializesTheInvoiceInTheRequest(): void
    {
        $this->addJsonResponseFromFile('invoice/123.json');
        $invoice = new Invoice();
        $invoice
            ->setAmount(100);

        $this->endpoint->create($invoice);

        $request = $this->mockHttpClient->getLastRequest();

        $jsonBody = json_decode($request->getBody()->getContents());

        $this->assertEquals(100, $jsonBody->amount);
    }

    public function testUpdateSerializesTheInvoiceInTheRequest(): void
    {
        $this->addJsonResponseFromFile('invoice/123.json');
        $invoice = new Invoice();
        $invoice
            ->setId(1)
            ->setClientKey("9e97f4a65c5b83b1fc02f54e5a41c9dc7d458542")
            ->setAmount(100.00);

        $this->endpoint->update($invoice);

        $request = $this->mockHttpClient->getLastRequest();

        $jsonBody = json_decode($request->getBody()->getContents());

        $this->assertEquals($invoice->getId(), $jsonBody->id);
        $this->assertEquals($invoice->getAmount(), $jsonBody->amount);
    }

    public function testInvoiceItemLineDeleteExecutesADeleteRequestWithTheGivenIds(): void
    {
        $this->addJsonResponseFromFile('invoice/123.json');

        $this->endpoint->deleteInvoiceItemLine(123, 12345);

        $request = $this->mockHttpClient->getLastRequest();

        $jsonBody = json_decode($request->getBody()->getContents());

        $expectedBody = new \stdClass();
        $expectedBody->line_items = new \stdClass();
        $expectedBody->line_items->id = 12345;
        $expectedBody->line_items->_destroy = true;

        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals($expectedBody, $jsonBody);
        $this->assertStringEndsWith('/invoices/123', (string) $request->getUri());
    }

    public function testDeleteExecutesADeleteRequestWithTheGivenId(): void
    {
        $this->endpoint->delete(123);

        $request = $this->mockHttpClient->getLastRequest();

        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertStringEndsWith('/invoices/123', (string) $request->getUri());
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
