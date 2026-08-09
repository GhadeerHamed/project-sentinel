<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\ReadModel;

use DateTimeImmutable;

final readonly class AccessibleOrganization
{
    public function __construct(
        private string $id,
        private string $name,
        private string $slug,
        private string $status,
        private string $timezone,
        private int $version,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
