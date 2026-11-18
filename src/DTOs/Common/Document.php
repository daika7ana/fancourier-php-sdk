<?php

declare(strict_types=1);

namespace Daika7ana\FanCourier\DTOs\Common;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Binary download (AWB labels, AWB confirmation archive).
 */
final class Document
{
    private ?string $contents = null;

    public function __construct(
        public readonly StreamInterface $stream,
        public readonly ?string $contentType = null,
        public readonly ?string $contentDisposition = null,
    ) {}

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            stream: $response->getBody(),
            contentType: $response->getHeaderLine('Content-Type') ?: null,
            contentDisposition: $response->getHeaderLine('Content-Disposition') ?: null,
        );
    }

    public function contents(): string
    {
        // Cache the bytes: PSR-7 streams are not guaranteed to be seekable.
        return $this->contents ??= (string) $this->stream;
    }

    public function filename(): ?string
    {
        if ($this->contentDisposition === null) {
            return null;
        }

        if (preg_match('/filename="?([^";]+)"?/', $this->contentDisposition, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
