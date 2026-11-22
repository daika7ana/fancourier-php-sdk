<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Auth;

use Daika7ana\FanCourier\Config\ClientConfig;
use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Http\HttpClientInterface;
use Daika7ana\FanCourier\Support\ApiResponseMapper;
use Daika7ana\FanCourier\Support\JsonHelper;
use JsonException;
use Psr\Http\Message\RequestFactoryInterface;

class CredentialsAuthenticator
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private ClientConfig $config,
    ) {}

    /**
     * FAN Courier accepts the selfAWB credentials as query parameters on `POST /login`.
     *
     * @throws AuthenticationException
     * @throws TransportException
     */
    public function authenticate(string $username, string $password): Token
    {
        $query = http_build_query(['username' => $username, 'password' => $password]);

        $request = $this->requestFactory
            ->createRequest('POST', rtrim($this->config->baseUrl, '/') . '/login?' . $query)
            ->withHeader('Accept', 'application/json');

        $response = $this->httpClient->sendRequest($request, $this->config->timeout);

        try {
            $payload = JsonHelper::decode((string) $response->getBody());
        } catch (JsonException $e) {
            throw new AuthenticationException(sprintf(
                'Authentication failed with status %d: %s',
                $response->getStatusCode(),
                (string) $response->getBody(),
            ), 0, $e, $response, ApiResponseMapper::retryAfter($response));
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if ($response->getStatusCode() !== 200 || ($payload['status'] ?? null) === 'fail') {
            throw new AuthenticationException(sprintf(
                'Authentication failed with status %d: %s',
                $response->getStatusCode(),
                self::failureMessage($payload),
            ), 0, response: $response, retryAfter: ApiResponseMapper::retryAfter($response));
        }

        return Token::fromResponse($data, $response);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function failureMessage(array $payload): string
    {
        foreach (['message', 'error'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && $payload[$key] !== '') {
                return $payload[$key];
            }
        }

        return 'Invalid credentials or unexpected login response.';
    }
}
