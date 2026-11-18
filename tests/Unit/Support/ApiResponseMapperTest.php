<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\Support;

use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use Daika7ana\FanCourier\Support\ApiResponseMapper;
use Daika7ana\FanCourier\Tests\Support\Fixture;
use PHPUnit\Framework\TestCase;

final class ApiResponseMapperTest extends TestCase
{
    public function testDecodesSuccessfulEnvelope(): void
    {
        $payload = ApiResponseMapper::decodeJson(Fixture::ok(['id' => 7]));

        self::assertSame(['id' => 7], $payload['data']);
    }

    public function testFailEnvelopeBecomesApiException(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('AWB already deleted');

        ApiResponseMapper::decodeJson(Fixture::fail('AWB already deleted'));
    }

    public function testNon200StatusBecomesUnexpectedStatusException(): void
    {
        $this->expectException(UnexpectedStatusException::class);

        ApiResponseMapper::decodeJson(Fixture::ok([]), 201);
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
        }
    }

    public function testInvalidJsonBecomesUnexpectedStatusException(): void
    {
        $this->expectException(UnexpectedStatusException::class);

        ApiResponseMapper::decodeJson(Fixture::raw('<html>'));
    }
}
