<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\ReadModel;

use DateTimeImmutable;

final readonly class EnvironmentListItem
{
    public function __construct(
        private string $id,
        private string $applicationId,
        private string $name,
        private string $slug,
        private string $environmentType,
        private string $status,
        private int $version,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function applicationId(): string
    {
        return $this->applicationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function environmentType(): string
    {
        return $this->environmentType;
    }

    public function status(): string
    {
        return $this->status;
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
