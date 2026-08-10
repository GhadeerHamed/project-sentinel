<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Command;

final readonly class RegisterEventSourceCommand
{
    private function __construct(
        private string $projectId,
        private string $applicationId,
        private string $environmentId,
        private string $name,
    ) {}

    public static function fromValues(
        string $projectId,
        string $applicationId,
        string $environmentId,
        string $name,
    ): self {
        return new self(
            projectId: trim($projectId),
            applicationId: trim($applicationId),
            environmentId: trim($environmentId),
            name: trim($name),
        );
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function applicationId(): string
    {
        return $this->applicationId;
    }

    public function environmentId(): string
    {
        return $this->environmentId;
    }

    public function name(): string
    {
        return $this->name;
    }
}
