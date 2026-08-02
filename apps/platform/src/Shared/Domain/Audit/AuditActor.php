<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Audit;

final readonly class AuditActor
{
    private function __construct(
        private string $type,
        private ?AuditActorId $id,
    ) {}

    public static function fromValues(string $type, ?string $id): self
    {
        $normalizedType = strtolower(trim($type));

        if ($normalizedType === '') {
            throw InvalidAuditRecord::forField('actor_type');
        }

        return new self(
            type: $normalizedType,
            id: $id === null ? null : AuditActorId::fromString($id),
        );
    }

    public function type(): string
    {
        return $this->type;
    }

    public function id(): ?AuditActorId
    {
        return $this->id;
    }
}
