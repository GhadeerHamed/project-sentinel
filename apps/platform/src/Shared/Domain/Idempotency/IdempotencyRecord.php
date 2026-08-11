<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

use DateTimeImmutable;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class IdempotencyRecord
{
    private function __construct(
        private IdempotencyRecordId $id,
        private string $requestHash,
        private IdempotencyStatus $status,
        private ?IdempotencyResponse $response,
    ) {}

    public static function processing(IdempotencyRecordId $id, IdempotencyRequest $request): self
    {
        return new self(
            id: $id,
            requestHash: $request->requestHash(),
            status: IdempotencyStatus::PROCESSING,
            response: null,
        );
    }

    /** @param array<string, mixed>|null $resultPayload */
    public static function fromValues(
        string $id,
        string $organizationId,
        string $identityId,
        string $operation,
        string $key,
        string $requestHash,
        string $status,
        ?int $responseStatus,
        ?string $responseReference,
        ?array $resultPayload,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $completedAt,
    ): self {
        $request = IdempotencyRequest::fromValues(
            OrganizationId::fromString($organizationId),
            IdentityId::fromString($identityId),
            $operation,
            $key,
            $requestHash,
        );
        $idempotencyStatus = IdempotencyStatus::from($status);
        $response = $responseStatus === null
            ? null
            : ($responseReference === null ? IdempotencyResponse::fromResultPayload($responseStatus, $resultPayload ?? []) : IdempotencyResponse::fromValues($responseStatus, $responseReference, $resultPayload));

        if (($idempotencyStatus === IdempotencyStatus::COMPLETED) !== ($response !== null && $completedAt !== null)) {
            throw InvalidIdempotencyRequest::forField('status');
        }

        return new self(
            id: IdempotencyRecordId::fromString($id),
            requestHash: $request->requestHash(),
            status: $idempotencyStatus,
            response: $response,
        );
    }

    public function id(): IdempotencyRecordId
    {
        return $this->id;
    }

    public function requestHash(): string
    {
        return $this->requestHash;
    }

    public function isCompleted(): bool
    {
        return $this->status === IdempotencyStatus::COMPLETED;
    }

    public function response(): IdempotencyResponse
    {
        if ($this->response === null) {
            throw InvalidIdempotencyRequest::forField('response');
        }

        return $this->response;
    }
}
