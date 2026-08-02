<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Audit;

final readonly class AuditResource
{
    private function __construct(
        private string $type,
        private ?AuditResourceId $id,
    ) {}

    public static function fromValues(string $type, ?string $id): self
    {
        $normalizedType = strtolower(trim($type));

        if ($normalizedType === '') {
            throw InvalidAuditRecord::forField('resource_type');
        }

        return new self(
            type: $normalizedType,
            id: $id === null ? null : AuditResourceId::fromString($id),
        );
    }

    public function type(): string
    {
        return $this->type;
    }

    public function id(): ?AuditResourceId
    {
        return $this->id;
    }
}
