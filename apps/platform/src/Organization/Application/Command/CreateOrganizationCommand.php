<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Command;

use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityReference;

final readonly class CreateOrganizationCommand
{
    private function __construct(
        private string $organizationName,
        private string $organizationSlug,
        private IdentityReference $creator,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
    ) {}

    public static function fromValues(
        string $organizationName,
        string $organizationSlug,
        string $creatorIdentityId,
        string $creatorIdentityType,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        return new self(
            organizationName: trim($organizationName),
            organizationSlug: trim($organizationSlug),
            creator: IdentityReference::fromString($creatorIdentityId, $creatorIdentityType),
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
        );
    }

    public function organizationName(): string
    {
        return $this->organizationName;
    }

    public function organizationSlug(): string
    {
        return $this->organizationSlug;
    }

    public function creator(): IdentityReference
    {
        return $this->creator;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }
}
