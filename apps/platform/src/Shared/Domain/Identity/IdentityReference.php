<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Identity;

use JsonSerializable;
use Stringable;

final readonly class IdentityReference implements JsonSerializable, Stringable
{
    private function __construct(
        private IdentityId $identityId,
        private IdentityType $identityType,
    ) {}

    public static function fromString(string $identityId, string $identityType): self
    {
        return new self(
            identityId: IdentityId::fromString($identityId),
            identityType: IdentityType::fromString($identityType),
        );
    }

    public function identityId(): string
    {
        return $this->identityId->value();
    }

    public function identityType(): string
    {
        return $this->identityType->value;
    }

    public function equals(self $other): bool
    {
        return $this->identityId->equals($other->identityId)
            && $this->identityType === $other->identityType;
    }

    /**
     * @return array{identity_id: string, identity_type: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'identity_id' => $this->identityId(),
            'identity_type' => $this->identityType(),
        ];
    }

    public function __toString(): string
    {
        return sprintf('%s:%s', $this->identityType(), $this->identityId());
    }
}
