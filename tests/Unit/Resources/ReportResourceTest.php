<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Resources;

use Daika7ana\FanCourier\DTOs\Common\Document;
use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use Daika7ana\FanCourier\DTOs\Report\AwbEvent;
use Daika7ana\FanCourier\DTOs\Report\AwbTracking;
use Daika7ana\FanCourier\DTOs\Report\BankTransfer;
use Daika7ana\FanCourier\DTOs\Report\ShippingSlipEntry;
use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use Daika7ana\FanCourier\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;

final class ReportResourceTest extends TestCase
{
    public function testShippingSlipMapsNestedInfoRecipientAndSender(): void
    {
        [$client] = TestClientFactory::create(static fn() => Fixture::json([
            'status' => 'success',
            'perPage' => 1,
            'currentPage' => 1,
            'total' => 15,
            'data' => [[
                'info' => [
                    'awbNumber' => 2078300120037,
                    'weight' => 1,
                    'dimensions' => ['height' => 3.3, 'width' => 2.31, 'length' => 2.26],
                    'payment' => 'sender',
                    'cod' => null,
                    'returnPayment' => null,
                    'declaredValue' => null,
                    'observations' => 'ATROFANFO-1679242522843',
                    'content' => null,
                    'date' => '2023-03-19 18:17:21',
                    'service' => 'Standard',
                    'cost' => '0',
                    'packages' => ['envelope' => 0, 'parcel' => 1],
                    'costCenter' => null,
                    'options' => 'ePOD',
                    'refund' => null,
                    'currency' => null,
                ],
                'recipient' => [
                    'name' => 'nametest001',
                    'phone' => '11111111111',
                    'address' => ['locality' => 'Bucuresti', 'county' => 'Bucuresti'],
                ],
                'sender' => [
                    'name' => 'FAN COURIER - cont test',
                    'contactPerson' => 'Jon Snow',
                ],
            ]],
        ]));

        $result = $client->reports()->shippingSlip('2023-03-19', perPage: 1, page: 1);

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertSame(15, $result->total);
        self::assertCount(1, $result->items);

        $entry = $result->items->first();
        self::assertInstanceOf(ShippingSlipEntry::class, $entry);
        self::assertSame(2078300120037, $entry->info->awbNumber);
        self::assertSame(1.0, $entry->info->weight);
        self::assertSame(2.31, $entry->info->dimensions->width);
        self::assertSame('sender', $entry->info->payment);
        self::assertSame(0.0, $entry->info->cost);
        self::assertSame(1, $entry->info->packages->parcel);
        self::assertSame('ePOD', $entry->info->options);
        self::assertSame('nametest001', $entry->recipient?->name);
        self::assertSame('Bucuresti', $entry->recipient?->address?->locality);
        self::assertSame('Jon Snow', $entry->sender?->contactPerson);
    }

    public function testAwbTrackingMapsUppercaseOtdAndConfirmation(): void
    {
        [$client, $httpClient] = TestClientFactory::create(static fn() => Fixture::ok([[
            'content' => 'test',
            'awbNumber' => '2078300120037',
            'date' => '2024-03-27 00:00:00',
            'paymentDate' => null,
            'returnAwbNumber' => null,
            'redirectionAwbNumber' => null,
            'reimbursementAwbNumber' => null,
            'oPODAwbNumber' => null,
            'confirmation' => ['name' => 'test', 'date' => '2024-03-28 10:02'],
            'OTD' => '24H',
            'events' => [[
                'id' => 'H4',
                'name' => 'Expeditie sortata pe banda',
                'location' => 'Bucuresti',
                'date' => '2023-03-01 04:46:46',
            ]],
        ]]));

        $tracking = $client->reports()->trackAwbs(['2078300120037']);

        self::assertCount(1, $tracking);
        $entry = $tracking->first();
        self::assertInstanceOf(AwbTracking::class, $entry);
        self::assertSame('24H', $entry->otd);
        self::assertSame('test', $entry->confirmation?->name);
        self::assertSame('2078300120037', $entry->awbNumber);
        self::assertInstanceOf(AwbEvent::class, $entry->events[0]);
        self::assertSame('H4', $entry->events[0]->id);
        self::assertSame('Bucuresti', $entry->events[0]->location);

        $query = (string) $httpClient->lastRequest?->getUri()->getQuery();
        self::assertStringContainsString('awb%5B%5D=2078300120037', $query);
        self::assertStringContainsString('language=ro', $query);
    }

    public function testBankTransfersMapsNestedInfoRecipientAndSender(): void
    {
        [$client] = TestClientFactory::create(static fn() => Fixture::json([
            'status' => 'success',
            'perPage' => 1,
            'currentPage' => 1,
            'total' => 2534,
            'data' => [[
                'info' => [
                    'awbNumber' => 276,
                    'awbDate' => '27.02.2023',
                    'amountCollected' => 276.07,
                    'content' => 'test',
                    'transferDate' => '01.03.2023',
                    'returnAwbNumber' => null,
                    'reimbursementAwbNumber' => null,
                    'transactionDate' => '28.02.2023',
                ],
                'recipient' => [
                    'name' => 'Recipient SRL',
                    'contactPerson' => '',
                    'address' => ['locality' => 'Adjud'],
                ],
                'sender' => ['name' => 'Sender SRL', 'contactPerson' => 'Ion Popescu'],
            ]],
        ]));

        $result = $client->reports()->bankTransfers('2023-02-27', page: 1, perPage: 1);

        self::assertSame(2534, $result->total);
        $transfer = $result->items->first();
        self::assertInstanceOf(BankTransfer::class, $transfer);
        self::assertSame(276, $transfer->awbNumber);
        self::assertSame(276.07, $transfer->amountCollected);
        self::assertSame('27.02.2023', $transfer->awbDate);
        self::assertSame('Recipient SRL', $transfer->recipientName);
        self::assertSame('Adjud', $transfer->recipientLocality);
        self::assertSame('Sender SRL', $transfer->senderName);
        self::assertSame('Ion Popescu', $transfer->senderContactPerson);
    }

    public function testConfirmationsReturnZipDocument(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn() => Fixture::raw('zip-bytes', 200, ['Content-Type' => 'application/zip']),
        );

        $document = $client->reports()->confirmations(['2078300120037', '2078300120038']);

        self::assertInstanceOf(Document::class, $document);
        self::assertSame('application/zip', $document->contentType);
        self::assertSame('zip-bytes', $document->contents());

        $query = (string) $httpClient->lastRequest?->getUri()->getQuery();
        self::assertSame(2, substr_count($query, 'awb%5B%5D='));
        self::assertStringContainsString('clientId=' . TestClientFactory::clientId(), $query);
    }

    public function testFailedReportThrowsApiException(): void
    {
        [$client] = TestClientFactory::create(static fn() => Fixture::fail('Invalid date'));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid date');

        $client->reports()->bankTransfers('not-a-date');
    }
}
