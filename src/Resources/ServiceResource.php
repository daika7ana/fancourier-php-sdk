<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Resources;

use Daika7ana\FanCourier\Client;
use Daika7ana\SimpleCollections\Collection;
use Daika7ana\FanCourier\DTOs\Service\Service;
use Daika7ana\FanCourier\DTOs\Service\ServiceOption;
use Daika7ana\FanCourier\Enums\ServiceType;
use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;

final class ServiceResource
{
    public function __construct(private Client $client) {}

    /**
     * @return Collection<int, Service>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function all(): Collection
    {
        $response = $this->client->send($this->client->createRequest('GET', '/reports/services'));
        $payload = ApiResponseMapper::decodeJson($response);

        $services = [];

        foreach (ApiResponseMapper::listOfArrays($payload['data'] ?? null) as $item) {
            $services[] = Service::fromArray($item);
        }

        return new Collection($services);
    }

    /**
     * @return Collection<int, ServiceOption>
     *
     * @throws ApiException|UnexpectedStatusException|ValidationException|TransportException
     */
    public function options(ServiceType|string $service): Collection
    {
        $response = $this->client->send($this->client->createRequest('GET', '/reports/service-options', [
            'clientId' => $this->client->clientId(),
            'service' => $service instanceof ServiceType ? $service->value : $service,
        ]));
        $payload = ApiResponseMapper::decodeJson($response);

        $options = [];

        foreach (ApiResponseMapper::listOfArrays($payload['data'] ?? null) as $item) {
            $options[] = ServiceOption::fromArray($item);
        }

        return new Collection($options);
    }
}
