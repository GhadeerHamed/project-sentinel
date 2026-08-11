<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

final readonly class IdempotencyResponse
{
    /** @param array<string, mixed>|null $resultPayload */
    private function __construct(
        private int $status,
        private ?IdempotencyResourceReference $resourceReference,
        private ?array $resultPayload,
    ) {}

    /** @param array<string, mixed>|null $resultPayload */
    public static function fromValues(int $status, string $resourceReference, ?array $resultPayload = null): self
    {
        if ($status < 200 || $status > 299) {
            throw InvalidIdempotencyRequest::forField('response_status');
        }

        self::assertSafePayload($resultPayload);

        return new self($status, IdempotencyResourceReference::fromString($resourceReference), $resultPayload);
    }

    /** @param array<string, mixed> $resultPayload */
    public static function fromResultPayload(int $status, array $resultPayload): self
    {
        if ($status < 200 || $status > 299) {
            throw InvalidIdempotencyRequest::forField('response_status');
        }

        self::assertSafePayload($resultPayload);

        return new self($status, null, $resultPayload);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function resourceReference(): IdempotencyResourceReference
    {
        if ($this->resourceReference === null) {
            throw InvalidIdempotencyRequest::forField('resource_reference');
        }

        return $this->resourceReference;
    }

    public function resourceReferenceOrNull(): ?IdempotencyResourceReference
    {
        return $this->resourceReference;
    }

    /** @return array<string, mixed>|null */
    public function resultPayload(): ?array
    {
        return $this->resultPayload;
    }

    /** @param array<string, mixed>|null $payload */
    private static function assertSafePayload(?array $payload): void
    {
        foreach ($payload ?? [] as $key => $value) {
            if (in_array(strtolower((string) $key), ['authorization', 'password', 'secret', 'credential', 'token'], true)) {
                throw InvalidIdempotencyRequest::forField('result_payload');
            }
            if (is_array($value)) {
                self::assertSafePayload($value);
            }
        }
    }
}
