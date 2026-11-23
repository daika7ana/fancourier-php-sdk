<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Tests\Unit\DTOs\Common\Errors;

use Daika7ana\FanCourier\DTOs\Common\Errors\FanCourierError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FanCourierErrorTest extends TestCase
{
    public function testFromArrayMapsTheFailureEnvelope(): void
    {
        $raw = ['status' => 'fail', 'message' => 'Invalid AWB', 'id' => 9, 'context' => 'awb', 'component' => 'label'];
        $error = FanCourierError::fromArray($raw, 502);

        self::assertSame(502, $error->code);
        self::assertSame('Invalid AWB', $error->message);
        self::assertSame('9', $error->id);
        self::assertSame($raw, $error->raw);
        self::assertSame('awb', $error->context);
        self::assertSame('label', $error->component);
    }

    public function testMessageFallsBackThroughErrorThenGeneralError(): void
    {
        self::assertSame('boom', FanCourierError::fromArray(['error' => 'boom'])->message);
        self::assertSame('boom', FanCourierError::fromArray(['general_error' => 'boom'])->message);
        self::assertSame('FanCourier API error.', FanCourierError::fromArray([])->message);
    }

    public function testValidationRelated(): void
    {
        self::assertTrue(FanCourierError::fromArray([], 400)->isValidationRelated());
        self::assertTrue(FanCourierError::fromArray([], 422)->isValidationRelated());
        self::assertTrue(FanCourierError::fromArray(['errors' => ['weight' => ['required']]])->isValidationRelated());
        self::assertFalse(FanCourierError::fromArray(['errors' => []])->isValidationRelated());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validationMessages(): iterable
    {
        yield 'invalid' => ['Invalid AWB'];
        yield 'required' => ['The weight field is required.'];
        yield 'mandatory' => ['The locality is mandatory.'];
        yield 'missing' => ['Missing recipient phone'];
        yield 'duplicate' => ['Duplicate order'];
        yield 'already' => ['This order was already deleted'];
        yield 'not valid' => ['The date is not valid'];
        yield 'validation' => ['Validation failed'];
        yield 'must be' => ['The weight must be greater than zero'];
    }

    #[DataProvider('validationMessages')]
    public function testValidationRelatedMessageHeuristics(string $message): void
    {
        self::assertTrue(FanCourierError::fromArray(['message' => $message])->isValidationRelated());
    }

    public function testUnclassifiedMessageIsNotValidationRelated(): void
    {
        self::assertFalse(FanCourierError::fromArray(['message' => 'Service unavailable'])->isValidationRelated());
    }

    public function testAuthenticationRelated(): void
    {
        self::assertTrue(FanCourierError::fromArray([], 401)->isAuthenticationRelated());
        self::assertTrue(FanCourierError::fromArray([], 403)->isAuthenticationRelated());
        self::assertTrue(FanCourierError::fromArray(['message' => 'Invalid credentials'])->isAuthenticationRelated());
        self::assertFalse(FanCourierError::fromArray(['message' => 'Invalid AWB'])->isAuthenticationRelated());
    }
}
