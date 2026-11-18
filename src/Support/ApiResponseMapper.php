<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\Support;

use Daika7ana\FanCourier\DTOs\Common\Document;
use Daika7ana\FanCourier\Exceptions\ApiException;
use Daika7ana\FanCourier\Exceptions\UnexpectedStatusException;
use Daika7ana\FanCourier\Exceptions\ValidationException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final class ApiResponseMapper
{
    /**
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function decodeJson(ResponseInterface $response, int $expectedStatus = 200): array
    {
        self::assertStatus($response, $expectedStatus);

        try {
            $payload = JsonHelper::decode((string) $response->getBody());
        } catch (JsonException $e) {
            throw new UnexpectedStatusException(
                $response,
                'Response body is not valid JSON: ' . $e->getMessage(),
            );
        }

        self::assertSuccess($payload);

        return $payload;
    }

    /**
     * Decode the response and return its `data` member.
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function data(ResponseInterface $response, int $expectedStatus = 200): mixed
    {
        return self::decodeJson($response, $expectedStatus)['data'] ?? null;
    }

    /**
     * Binary payload (label, confirmation archive), unless the API answered
     * with a JSON envelope — then the usual fail/validation errors apply.
     *
     * @throws ApiException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function document(ResponseInterface $response, int $expectedStatus = 200): Document
    {
        self::assertStatus($response, $expectedStatus);

        $document = Document::fromResponse($response);
        $contents = $document->contents();

        $isJson = str_contains(strtolower($document->contentType ?? ''), 'json')
            || str_starts_with(ltrim($contents), '{');

        if ($isJson) {
            try {
                self::assertSuccess(JsonHelper::decode($contents));
            } catch (JsonException $e) {
                throw new UnexpectedStatusException(
                    $response,
                    'Expected binary content, got invalid JSON: ' . $e->getMessage(),
                );
            }
        }

        return $document;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @throws ApiException
     */
    public static function assertSuccess(array $payload): void
    {
        if (($payload['status'] ?? null) === 'fail') {
            throw new ApiException(self::failureMessage($payload), $payload);
        }
    }

    /**
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public static function assertStatus(ResponseInterface $response, int $expectedStatus): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode === 422) {
            $payload = self::decodeValidationPayload((string) $response->getBody());

            throw new ValidationException(
                errors: $payload['errors'],
                message: $payload['message'],
                code: 422,
            );
        }

        if ($statusCode !== $expectedStatus) {
            throw new UnexpectedStatusException($response);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listOfArrays(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function failureMessage(array $payload): string
    {
        foreach (['message', 'error', 'general_error'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && $payload[$key] !== '') {
                return $payload[$key];
            }
        }

        return 'The API reported a failure.';
    }

    /**
     * @return array{message: string, errors: array<string, string[]>}
     */
    private static function decodeValidationPayload(string $payload): array
    {
        try {
            $decoded = JsonHelper::decode($payload);
        } catch (\Throwable) {
            return [
                'message' => 'Validation failed',
                'errors' => [],
            ];
        }

        $message = self::failureMessage($decoded);
        $rawErrors = $decoded['errors'] ?? [];
        $errors = [];

        if (is_array($rawErrors)) {
            foreach ($rawErrors as $field => $messages) {
                if (!is_string($field) || !is_array($messages)) {
                    continue;
                }

                $errors[$field] = array_values(array_filter($messages, static fn(mixed $message): bool => is_string($message)));
            }
        }

        return [
            'message' => $message,
            'errors' => $errors,
        ];
    }
}
