<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Support;

use Daika7ana\FanCourier\Exceptions\AuthenticationException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ApiResponseMapperTest extends TestCase
{
    public function testDecodesSuccessfulEnvelope(): void
    {
        $payload = ApiResponseMapper::decodeJson(Fixture::ok(['id' => 7]));

        self::assertSame(['id' => 7], $payload['data']);
    }

    public function testFailEnvelopeBecomesUnexpectedStatusException(): void
    {
        $this->expectException(UnexpectedStatusException::class);
        $this->expectExceptionMessage('AWB already deleted');

        ApiResponseMapper::decodeJson(Fixture::fail('AWB already deleted'));
    }

    public function test4xxFailureEnvelopeBecomesUnexpectedStatusException(): void
    {
        $response = Fixture::json(['status' => 'fail', 'message' => 'This order was already deleted'], 403);

        try {
            ApiResponseMapper::decodeJson($response);
            self::fail('Expected UnexpectedStatusException.');
        } catch (UnexpectedStatusException $e) {
            self::assertSame('This order was already deleted', $e->getMessage());
            self::assertSame($response, $e->response);
            self::assertSame(403, $e->error?->code);
            self::assertSame(403, $e->getCode());
            self::assertTrue($e->error?->isAuthenticationRelated() ?? false);
            self::assertSame('This order was already deleted', $e->error?->message);
            self::assertSame(['status' => 'fail', 'message' => 'This order was already deleted'], $e->error?->raw);
        }
    }

    public function testFailEnvelopeCarriesTheResponse(): void
    {
        $response = Fixture::fail('AWB already deleted');

        try {
            ApiResponseMapper::decodeJson($response);
            self::fail('Expected UnexpectedStatusException.');
        } catch (UnexpectedStatusException $e) {
            self::assertSame($response, $e->response);
            self::assertSame('AWB already deleted', $e->error?->message);
        }
    }

    public function testNon200StatusBecomesUnexpectedStatusException(): void
    {
        $this->expectException(UnexpectedStatusException::class);

        ApiResponseMapper::decodeJson(Fixture::ok([]), 201);
    }

    public function testUnauthorizedBecomesAuthenticationException(): void
    {
        $response = Fixture::json(['status' => 'fail', 'message' => 'Unauthenticated.'], 401);

        try {
            ApiResponseMapper::decodeJson($response);
            self::fail('Expected AuthenticationException.');
        } catch (AuthenticationException $e) {
            self::assertSame('Unauthenticated.', $e->getMessage());
            self::assertSame(401, $e->getCode());
            self::assertSame($response, $e->response);
            self::assertSame(401, $e->error?->code);
        }
    }

    public function testBadRequestBecomesValidationException(): void
    {
        $response = Fixture::json([
            'message' => 'The given data was invalid.',
            'errors' => ['awb' => ['The awb field is required.']],
        ], 400);

        try {
            ApiResponseMapper::decodeJson($response);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame('The given data was invalid.', $e->getMessage());
            self::assertSame(['awb' => ['The awb field is required.']], $e->errors);
            self::assertSame(400, $e->getCode());
            self::assertSame($response, $e->response);
            self::assertSame(400, $e->error?->code);
        }
    }

    public function testUnprocessableEntityBecomesValidationException(): void
    {
        $response = Fixture::json([
            'message' => 'The given data was invalid.',
            'errors' => ['weight' => ['The weight is required.']],
        ], 422);

        try {
            ApiResponseMapper::decodeJson($response);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame('The given data was invalid.', $e->getMessage());
            self::assertSame(['weight' => ['The weight is required.']], $e->errors);
            self::assertSame($response, $e->response);
            self::assertSame(422, $e->error?->code);
            self::assertSame(422, $e->getCode());
            self::assertSame(['weight' => ['The weight is required.']], $e->error?->raw['errors'] ?? null);
        }
    }

    public function testInvalidJsonBecomesUnexpectedStatusException(): void
    {
        $this->expectException(UnexpectedStatusException::class);

        ApiResponseMapper::decodeJson(Fixture::raw('<html>'));
    }

    public function testUnexpectedStatusExposesRetryAfterHeader(): void
    {
        try {
            ApiResponseMapper::decodeJson(new Response(503, ['Retry-After' => '30'], 'down'));
            $this->fail('Expected UnexpectedStatusException.');
        } catch (UnexpectedStatusException $exception) {
            $this->assertSame(30, $exception->retryAfter);
            $this->assertSame(503, $exception->getCode());
        }

        try {
            ApiResponseMapper::decodeJson(new Response(503, [], 'down'));
            $this->fail('Expected UnexpectedStatusException.');
        } catch (UnexpectedStatusException $exception) {
            $this->assertNull($exception->retryAfter);
        }
    }
}
