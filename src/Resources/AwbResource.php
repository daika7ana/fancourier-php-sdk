<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Resources;

use Daika7ana\FanCourier\Client;
use Daika7ana\FanCourier\DTOs\Awb\ExternalAwbRequest;
use Daika7ana\FanCourier\DTOs\Awb\InternalAwbRequest;
use Daika7ana\FanCourier\DTOs\Awb\AwbResult;
use Daika7ana\FanCourier\DTOs\Common\Document;
use Daika7ana\FanCourier\DTOs\Common\OperationResult;
use Daika7ana\FanCourier\DTOs\Tariff\ExternalTariffRequest;
use Daika7ana\FanCourier\DTOs\Tariff\InternalTariffRequest;
use Daika7ana\FanCourier\DTOs\Tariff\Tariff;
use Daika7ana\FanCourier\Enums\LabelFormat;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;

final class AwbResource
{
    public function __construct(private Client $client) {}

    /**
     * @param InternalAwbRequest|array<string, mixed> $request
     *
     * @throws UnexpectedStatusException|ValidationException|TransportException
     */
    public function createInternal(InternalAwbRequest|array $request): AwbResult
    {
        $body = $request instanceof InternalAwbRequest ? $request->toArray() : $request;

        $body['clientId'] = $this->client->clientId();

        $response = $this->client->send($this->client->withJsonBody(
            $this->client->createRequest('POST', '/intern-awb'),
            $body,
        ));

        return AwbResult::fromArray(ApiResponseMapper::decodeJson($response));
    }

    /**
     * @param ExternalAwbRequest|array<string, mixed> $request
     *
     * @throws UnexpectedStatusException|ValidationException|TransportException
     */
    public function createExternal(ExternalAwbRequest|array $request): AwbResult
    {
        $body = $request instanceof ExternalAwbRequest ? $request->toArray() : $request;

        $body['clientId'] = $this->client->clientId();

        $response = $this->client->send($this->client->withJsonBody(
            $this->client->createRequest('POST', '/extern-awb'),
            $body,
        ));

        return AwbResult::fromArray(ApiResponseMapper::decodeJson($response));
    }

    /**
     * @throws UnexpectedStatusException|ValidationException|TransportException
     */
    public function delete(int|string $awb): OperationResult
    {
        $response = $this->client->send($this->client->createRequest('DELETE', '/awb', [
            'clientId' => $this->client->clientId(),
            'awb' => $awb,
        ]));

        return OperationResult::fromArray(ApiResponseMapper::decodeJson($response));
    }

    /**
     * @param int|string|list<int|string> $awbs
     *
     * @throws UnexpectedStatusException|ValidationException|TransportException
     */
    public function label(
        int|string|array $awbs,
        LabelFormat|string|null $format = null,
        ?bool $pdf = null,
        ?bool $zpl = null,
        ?int $dpi = null,
        ?string $language = null,
    ): Document {
        $response = $this->client->send($this->client->createRequest('GET', '/awb/label', [
            'clientId' => $this->client->clientId(),
            'awbs' => is_array($awbs) ? array_values($awbs) : [$awbs],
            'pdf' => $pdf,
            'zpl' => $zpl,
            'language' => $language ?? $this->client->getConfig()->language,
            'format' => $format instanceof LabelFormat ? $format->value : $format,
            'dpi' => $dpi,
        ]));

        return ApiResponseMapper::document($response);
    }

    /**
     * @param InternalTariffRequest|array<string, mixed> $request
     *
     * @throws UnexpectedStatusException|ValidationException|TransportException
     */
    public function internalTariff(InternalTariffRequest|array $request): Tariff
    {
        $data = $request instanceof InternalTariffRequest ? $request->toArray() : $request;

        $data['clientId'] = $this->client->clientId();

        $response = $this->client->send($this->client->createRequest(
            'GET',
            '/reports/awb/internal-tariff',
            $this->flatten($data),
        ));
        $payload = ApiResponseMapper::decodeJson($response);
        $tariff = $payload['data'] ?? [];

        return Tariff::fromArray(is_array($tariff) ? $tariff : []);
    }

    /**
     * @param ExternalTariffRequest|array<string, mixed> $request
     *
     * @throws UnexpectedStatusException|ValidationException|TransportException
     */
    public function externalTariff(ExternalTariffRequest|array $request): Tariff
    {
        $data = $request instanceof ExternalTariffRequest ? $request->toArray() : $request;

        $data['clientId'] = $this->client->clientId();

        $response = $this->client->send($this->client->createRequest(
            'GET',
            '/reports/awb/external-tariff',
            $this->flatten($data),
        ));
        $payload = ApiResponseMapper::decodeJson($response);
        $tariff = $payload['data'] ?? [];

        return Tariff::fromArray(is_array($tariff) ? $tariff : []);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function flatten(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';

            if (is_array($value)) {
                if ($value === []) {
                    continue;
                }

                if (array_is_list($value)) {
                    $result[$name] = $value;

                    continue;
                }

                $result += $this->flatten($value, $name);

                continue;
            }

            $result[$name] = $value;
        }

        return $result;
    }
}
