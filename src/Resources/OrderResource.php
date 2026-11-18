<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Resources;

use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\DTOs\Common\Collection;
use Daika7ana\FanCourier\DTOs\Common\OperationResult;
use Daika7ana\FanCourier\DTOs\Common\PaginatedResult;
use Daika7ana\FanCourier\DTOs\Order\OrderEvent;
use Daika7ana\FanCourier\DTOs\Order\OrderRequest;
use Daika7ana\FanCourier\DTOs\Order\OrderResult;
use Daika7ana\FanCourier\DTOs\Order\OrderSummary;
use Daika7ana\FanCourier\DTOs\Order\OrderTracking;
use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;

final class OrderResource
{
    public function __construct(private Client $client) {}

    /**
     * @param OrderRequest|array<string, mixed> $request
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function create(OrderRequest|array $request): OrderResult
    {
        $body = $request instanceof OrderRequest ? $request->toArray() : $request;
        $body['clientId'] = $this->client->clientId();

        $httpRequest = $this->client->withJsonBody(
            $this->client->createRequest('POST', '/order'),
            $body,
        );

        // POST /order answers 201 Created; other accounts/versions may still answer 200.
        return OrderResult::fromArray(ApiResponseMapper::decodeJson($this->client->send($httpRequest), [200, 201]));
    }

    /**
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function delete(int|string $id): OperationResult
    {
        $httpRequest = $this->client->createRequest('DELETE', '/order', [
            'clientId' => $this->client->clientId(),
            'id' => $id,
        ]);

        return OperationResult::fromArray(ApiResponseMapper::decodeJson($this->client->send($httpRequest)));
    }

    /**
     * @return PaginatedResult<OrderSummary>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function report(string $date, ?int $page = null, ?int $perPage = null): PaginatedResult
    {
        $httpRequest = $this->client->createRequest('GET', '/reports/orders', [
            'clientId' => $this->client->clientId(),
            'date' => $date,
            'perPage' => $perPage,
            'page' => $page,
        ]);

        return PaginatedResult::fromArray(
            ApiResponseMapper::decodeJson($this->client->send($httpRequest)),
            static fn(array $item): OrderSummary => OrderSummary::fromArray($item),
        );
    }

    /**
     * @return Collection<int, OrderEvent>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function events(?string $language = null): Collection
    {
        $language ??= $this->client->getConfig()->language;

        $httpRequest = $this->client->createRequest('GET', '/reports/order-events', [
            'language' => $language,
        ]);

        $response = $this->client->send($httpRequest);

        return new Collection(array_map(
            static fn(array $item): OrderEvent => OrderEvent::fromArray($item),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::data($response)),
        ));
    }

    /**
     * @param list<int|string> $orderIds
     * @return Collection<int, OrderTracking>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function tracking(array $orderIds, ?string $language = null): Collection
    {
        $language ??= $this->client->getConfig()->language;

        $httpRequest = $this->client->createRequest('GET', '/reports/orders/tracking', [
            'clientId' => $this->client->clientId(),
            'orderId' => $orderIds,
            'language' => $language,
        ]);

        $response = $this->client->send($httpRequest);

        return new Collection(array_map(
            static fn(array $item): OrderTracking => OrderTracking::fromArray($item),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::data($response)),
        ));
    }
}
