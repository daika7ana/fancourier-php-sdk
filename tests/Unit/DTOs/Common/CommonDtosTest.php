<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\DTOs\Common;

use Daika7ana\FanCourier\DTOs\Common\Address;
use Daika7ana\SimpleCollections\Collection;
use Daika7ana\FanCourier\DTOs\Common\Contact;
use Daika7ana\FanCourier\DTOs\Common\Dimensions;
use Daika7ana\FanCourier\DTOs\Common\Document;
use Daika7ana\FanCourier\DTOs\Common\OperationResult;
use Daika7ana\FanCourier\DTOs\Common\Packages;
use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

final class CommonDtosTest extends TestCase
{
    public function testAddressMapsLowercaseZipcodeAndDropsEmptyValues(): void
    {
        $address = Address::fromArray([
            'locality' => 'Bucuresti',
            'county' => 'Bucuresti',
            'zipcode' => '020331',
            'floor' => '',
            'street' => null,
        ]);

        self::assertSame('020331', $address->zipCode);
        self::assertSame(
            ['county' => 'Bucuresti', 'locality' => 'Bucuresti', 'zipCode' => '020331'],
            $address->toArray(),
        );
    }

    public function testContactNestsAddress(): void
    {
        $contact = Contact::fromArray([
            'name' => 'Alex',
            'phone' => '0723456789',
            'address' => ['county' => 'Bucuresti', 'locality' => 'Bucuresti'],
        ]);

        self::assertSame('Alex', $contact->name);
        self::assertSame(['county' => 'Bucuresti', 'locality' => 'Bucuresti'], $contact->address?->toArray());
        self::assertSame([
            'name' => 'Alex',
            'phone' => '0723456789',
            'address' => ['county' => 'Bucuresti', 'locality' => 'Bucuresti'],
        ], $contact->toArray());
    }

    public function testDimensionsAndPackagesCastNumericStrings(): void
    {
        $dimensions = Dimensions::fromArray(['length' => '3', 'width' => 4, 'height' => null]);
        $packages = Packages::fromArray(['parcel' => '1', 'envelopes' => 2]);

        self::assertSame(['length' => 3.0, 'width' => 4.0, 'height' => 0.0], $dimensions->toArray());
        self::assertSame(['parcel' => 1, 'envelope' => 2], $packages->toArray());
    }

    public function testPaginatedResultMapsItemsAndCounters(): void
    {
        $result = PaginatedResult::fromArray([
            'perPage' => '1',
            'currentPage' => 2,
            'total' => 10,
            'data' => [['id' => 1], ['id' => 2], 'ignored'],
        ], static fn(array $row): int => (int) $row['id']);

        self::assertInstanceOf(Collection::class, $result->items);
        self::assertSame([1, 2], $result->items->toArray());
        self::assertSame(1, $result->perPage);
        self::assertSame(2, $result->currentPage);
        self::assertSame(10, $result->total);
    }

    public function testOperationResultReadsStringDataOrMessage(): void
    {
        self::assertSame('AWB-ul a fost sters cu succes', OperationResult::fromArray([
            'status' => 'success',
            'data' => 'AWB-ul a fost sters cu succes',
        ])->message);

        self::assertSame('Cancelled', OperationResult::fromArray(['message' => 'Cancelled'])->message);
    }

    public function testDocumentParsesFilename(): void
    {
        $document = Document::fromResponse(new Response(
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="awb.pdf"',
            ],
            Utils::streamFor('%PDF-1.4'),
        ));

        self::assertSame('application/pdf', $document->contentType);
        self::assertSame('awb.pdf', $document->filename());
        self::assertSame('%PDF-1.4', $document->contents());
    }
}
