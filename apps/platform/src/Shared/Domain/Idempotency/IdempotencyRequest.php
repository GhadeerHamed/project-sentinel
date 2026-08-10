<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

use JsonException;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class IdempotencyRequest
{
    private const OPERATION_PATTERN = '/\A[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+\z/';

    private function __construct(
        private OrganizationId $organizationId,
        private IdentityId $identityId,
        private string $operation,
        private string $key,
        private string $requestHash,
    ) {}

    /**
     * @param  array<string, scalar|null>  $normalizedPayload
     */
    public static function fromNormalizedPayload(
        OrganizationId $organizationId,
        IdentityId $identityId,
        string $operation,
        string $key,
        array $normalizedPayload,
    ): self {
        ksort($normalizedPayload);

        try {
            $requestHash = hash('sha256', json_encode($normalizedPayload, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            throw InvalidIdempotencyRequest::forField('request_payload');
        }

        return self::fromValues($organizationId, $identityId, $operation, $key, $requestHash);
    }

    public static function fromValues(
        OrganizationId $organizationId,
        IdentityId $identityId,
        string $operation,
        string $key,
        string $requestHash,
    ): self {
        $normalizedOperation = strtolower(trim($operation));
        $normalizedKey = trim($key);
        $normalizedHash = strtolower(trim($requestHash));

        if (preg_match(self::OPERATION_PATTERN, $normalizedOperation) !== 1) {
            throw InvalidIdempotencyRequest::forField('operation');
        }

        if ($normalizedKey === '' || mb_strlen($normalizedKey) > 255) {
            throw InvalidIdempotencyRequest::forField('key');
        }

        if (preg_match('/\A[0-9a-f]{64}\z/', $normalizedHash) !== 1) {
            throw InvalidIdempotencyRequest::forField('request_hash');
        }

        return new self($organizationId, $identityId, $normalizedOperation, $normalizedKey, $normalizedHash);
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function identityId(): IdentityId
    {
        return $this->identityId;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function requestHash(): string
    {
        return $this->requestHash;
    }
}
