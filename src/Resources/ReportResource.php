<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Resources;

use Daika7ana\FanCourier\Client;
use Daika7ana\SimpleCollections\Collection;
use Daika7ana\FanCourier\DTOs\Common\Document;
use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use Daika7ana\FanCourier\DTOs\Report\AwbEvent;
use Daika7ana\FanCourier\DTOs\Report\AwbTracking;
use Daika7ana\FanCourier\DTOs\Report\BankTransfer;
use Daika7ana\FanCourier\DTOs\Report\ShippingSlipEntry;
use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;

final class ReportResource
{
    public function __construct(private Client $client) {}

    /**
     * @return PaginatedResult<ShippingSlipEntry>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function shippingSlip(string $date, ?int $page = null, ?int $perPage = null): PaginatedResult
    {
        $httpRequest = $this->client->createRequest('GET', '/reports/awb', [
            'clientId' => $this->client->clientId(),
            'date' => $date,
            'perPage' => $perPage,
            'page' => $page,
        ]);

        return PaginatedResult::fromArray(
            ApiResponseMapper::decodeJson($this->client->send($httpRequest)),
            static fn(array $item): ShippingSlipEntry => ShippingSlipEntry::fromArray($item),
        );
    }

    /**
     * @return Collection<int, AwbEvent>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function awbEvents(?string $language = null): Collection
    {
        $language ??= $this->client->getConfig()->language;

        $httpRequest = $this->client->createRequest('GET', '/reports/awb-events', [
            'language' => $language,
        ]);

        $response = $this->client->send($httpRequest);

        return new Collection(array_map(
            static fn(array $item): AwbEvent => AwbEvent::fromArray($item),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::data($response)),
        ));
    }

    /**
     * @param list<int|string> $awbs
     * @return Collection<int, AwbTracking>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function trackAwbs(array $awbs, ?string $language = null): Collection
    {
        $language ??= $this->client->getConfig()->language;

        $httpRequest = $this->client->createRequest('GET', '/reports/awb/tracking', [
            'clientId' => $this->client->clientId(),
            'awb' => $awbs,
            'language' => $language,
        ]);

        $response = $this->client->send($httpRequest);

        return new Collection(array_map(
            static fn(array $item): AwbTracking => AwbTracking::fromArray($item),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::data($response)),
        ));
    }

    /**
     * @return PaginatedResult<BankTransfer>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function bankTransfers(string $date, ?int $page = null, ?int $perPage = null): PaginatedResult
    {
        $httpRequest = $this->client->createRequest('GET', '/reports/bank-transfers', [
            'clientId' => $this->client->clientId(),
            'date' => $date,
            'perPage' => $perPage,
            'page' => $page,
        ]);

        return PaginatedResult::fromArray(
            ApiResponseMapper::decodeJson($this->client->send($httpRequest)),
            static fn(array $item): BankTransfer => BankTransfer::fromArray($item),
        );
    }

    /**
     * @param list<int|string> $awbs
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function confirmations(array $awbs): Document
    {
        $httpRequest = $this->client->createRequest('GET', '/reports/get-awb-confirmations', [
            'clientId' => $this->client->clientId(),
            'awb' => $awbs,
        ]);

        $response = $this->client->send($httpRequest);

        return ApiResponseMapper::document($response);
    }
}
