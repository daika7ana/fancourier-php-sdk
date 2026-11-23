<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier;

use Daika7ana\FanCourier\Auth\CredentialsAuthenticator;
use Daika7ana\FanCourier\Auth\InMemoryTokenStore;
use Daika7ana\FanCourier\Auth\Token;
use Daika7ana\FanCourier\Auth\TokenStoreInterface;
use Daika7ana\FanCourier\Config\ClientConfig;
use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use Daika7ana\FanCourier\Exceptions\TransportException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Http\GuzzleHttpClient;
use Daika7ana\FanCourier\Http\HttpClientInterface;
use Daika7ana\FanCourier\Http\Psr18HttpClientAdapter;
use Daika7ana\FanCourier\Resources\AwbResource;
use Daika7ana\FanCourier\Resources\BranchResource;
use Daika7ana\FanCourier\Resources\LocationResource;
use Daika7ana\FanCourier\Resources\OrderResource;
use Daika7ana\FanCourier\Resources\PickupPointResource;
use Daika7ana\FanCourier\Resources\ReportResource;
use Daika7ana\FanCourier\Resources\ServiceResource;
use Daika7ana\FanCourier\Support\JsonHelper;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    private HttpClientInterface $httpClient;

    private ClientConfig $config;

    private TokenStoreInterface $tokenStore;

    private string $username;

    private string $password;

    private ?ServiceResource $serviceResource = null;

    private ?AwbResource $awbResource = null;

    private ?LocationResource $locationResource = null;

    private ?PickupPointResource $pickupPointResource = null;

    private ?OrderResource $orderResource = null;

    private ?ReportResource $reportResource = null;

    private ?BranchResource $branchResource = null;

    public function __construct(
        ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        ?ClientConfig $config = null,
        ?TokenStoreInterface $tokenStore = null,
    ) {
        if ($config !== null && $config->timeout !== null && !$httpClient instanceof HttpClientInterface) {
            throw new \InvalidArgumentException('ClientConfig::timeout cannot be applied to a foreign PSR-18 client; configure the timeout on that client or leave it null.');
        }

        $this->httpClient = $httpClient instanceof HttpClientInterface
            ? $httpClient
            : new Psr18HttpClientAdapter($httpClient);
        $this->config = $config ?? new ClientConfig();
        $this->tokenStore = $tokenStore ?? new InMemoryTokenStore();
        $this->username = $this->config->username;
        $this->password = $this->config->password;
    }

    /**
     * @throws AuthenticationException
     * @throws TransportException
     */
    public function authenticate(string $username, string $password): void
    {
        $this->username = $username;
        $this->password = $password;
        $token = $this->makeAuthenticator()->authenticate($username, $password);
        $this->storeToken($token);
    }

    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    /**
     * A client sharing this client's transport, PSR-17 factories, token store
     * and credentials, but with a different request timeout budget. The token
     * is shared through the store, so the derived client starts with the same
     * token instead of logging in again.
     */
    public function withTimeout(?float $timeout): self
    {
        if ($timeout !== null && $this->httpClient instanceof Psr18HttpClientAdapter) {
            throw new \InvalidArgumentException('ClientConfig::timeout cannot be applied to a foreign PSR-18 client; configure the timeout on that client or leave it null.');
        }

        $client = new self(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->config->withTimeout($timeout),
            $this->tokenStore,
        );
        $client->username = $this->username;
        $client->password = $this->password;

        return $client;
    }

    public function getToken(): ?Token
    {
        return $this->currentToken();
    }

    public function setToken(Token|string $token): self
    {
        $this->storeToken(is_string($token) ? new Token($token) : $token);

        return $this;
    }

    /**
     * The selfAWB client (branch) ID required by most endpoints.
     *
     * @throws ValidationException
     */
    public function clientId(): int|string
    {
        if ($this->config->clientId === null || $this->config->clientId === '') {
            throw new ValidationException([], 'No clientId configured. Pass it to ClientConfig.');
        }

        return $this->config->clientId;
    }

    /**
     * @internal Used by the bundled resources. Not part of the supported API.
     *
     * @param array<string, mixed> $query Scalars and lists (`awbs` becomes `awbs[]`)
     */
    public function createRequest(string $method, string $path, array $query = []): RequestInterface
    {
        $uri = rtrim($this->config->baseUrl, '/') . $path;
        $queryString = self::buildQuery($query);

        if ($queryString !== '') {
            $uri .= '?' . $queryString;
        }

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json');

        return $this->applyToken($request, $this->currentToken());
    }

    /**
     * @internal Used by the bundled resources. Not part of the supported API.
     *
     * @param array<string, mixed> $body
     *
     * @throws \JsonException
     */
    public function withJsonBody(RequestInterface $request, array $body): RequestInterface
    {
        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(JsonHelper::encode($body)));
    }

    /**
     * @internal Used by the bundled resources. Not part of the supported API.
     *
     * Send a request, re-authenticating on expiry or on a rejected token.
     *
     * @throws TransportException
     */
    public function send(RequestInterface $request, ?float $timeout = null): ResponseInterface
    {
        $token = $this->currentToken();

        if ($token?->isExpired() === true && $this->canReauthenticate()) {
            $token = $this->reauthenticate();
        }

        $request = $this->applyToken($request, $token);
        $response = $this->httpClient->sendRequest($request, $timeout ?? $this->config->timeout);

        if ($this->authWasRejected($response) && $this->canReauthenticate()) {
            $request = $this->applyToken($request, $this->reauthenticate());
            $response = $this->httpClient->sendRequest($request, $timeout ?? $this->config->timeout);
        }

        return $response;
    }

    public function services(): ServiceResource
    {
        return $this->serviceResource ??= new ServiceResource($this);
    }

    public function awb(): AwbResource
    {
        return $this->awbResource ??= new AwbResource($this);
    }

    public function locations(): LocationResource
    {
        return $this->locationResource ??= new LocationResource($this);
    }

    public function pickupPoints(): PickupPointResource
    {
        return $this->pickupPointResource ??= new PickupPointResource($this);
    }

    public function orders(): OrderResource
    {
        return $this->orderResource ??= new OrderResource($this);
    }

    public function reports(): ReportResource
    {
        return $this->reportResource ??= new ReportResource($this);
    }

    public function branches(): BranchResource
    {
        return $this->branchResource ??= new BranchResource($this);
    }

    public static function create(
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?ClientConfig $config = null,
        ?TokenStoreInterface $tokenStore = null,
    ): self {
        $httpClient ??= new GuzzleHttpClient();
        $config ??= ClientConfig::fromEnvironment();

        if ($requestFactory === null || $streamFactory === null) {
            $psr17Factory = new \GuzzleHttp\Psr7\HttpFactory();
            $requestFactory ??= $psr17Factory;
            $streamFactory ??= $psr17Factory;
        }

        return new self($httpClient, $requestFactory, $streamFactory, $config, $tokenStore);
    }

    private function makeAuthenticator(): CredentialsAuthenticator
    {
        return new CredentialsAuthenticator($this->httpClient, $this->requestFactory, $this->config);
    }

    /**
     * @throws AuthenticationException
     * @throws TransportException
     */
    private function reauthenticate(): Token
    {
        if (!$this->canReauthenticate()) {
            throw new AuthenticationException('Cannot re-authenticate: username and password are missing.');
        }

        $token = $this->makeAuthenticator()->authenticate($this->username, $this->password);
        $this->storeToken($token);

        return $token;
    }

    private function canReauthenticate(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    private function applyToken(RequestInterface $request, ?Token $token): RequestInterface
    {
        if ($token === null) {
            return $request->withoutHeader('Authorization');
        }

        return $request
            ->withoutHeader('Authorization')
            ->withHeader('Authorization', sprintf('%s %s', $token->tokenType, $token->accessToken));
    }

    /**
     * A 401 always means "log in again". A 403 usually does not: FAN uses it for
     * business refusals with a `status:fail` body, which must reach the caller
     * instead of being replayed as a second write.
     */
    private function authWasRejected(ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode === 401) {
            return true;
        }

        if ($statusCode !== 403) {
            return false;
        }

        $body = (string) $response->getBody();

        // shortcut: assumes a seekable body (Guzzle is); a non-seekable stream loses the 403 body.
        if ($response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        try {
            return (JsonHelper::decode($body)['status'] ?? null) === 'success';
        } catch (JsonException) {
            return true;
        }
    }

    private function currentToken(): ?Token
    {
        return $this->tokenStore->getToken();
    }

    private function storeToken(Token $token): void
    {
        $this->tokenStore->setToken($token);
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function buildQuery(array $query): string
    {
        $parts = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = rawurlencode((string) $key . '[]') . '=' . rawurlencode((string) $item);
                }

                continue;
            }

            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode(is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }

        return implode('&', $parts);
    }
}
