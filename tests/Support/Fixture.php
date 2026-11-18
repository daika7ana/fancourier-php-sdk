<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class Fixture
{
    public static function ok(mixed $data): ResponseInterface
    {
        return self::json(['status' => 'success', 'data' => $data]);
    }

    public static function fail(string $message = 'Operation failed'): ResponseInterface
    {
        return self::json(['status' => 'fail', 'message' => $message]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function json(array $payload, int $status = 200, array $headers = []): ResponseInterface
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'] + $headers,
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param array<string, string> $headers
     */
    public static function raw(string $body, int $status = 200, array $headers = []): ResponseInterface
    {
        return new Response($status, $headers, $body);
    }
}
