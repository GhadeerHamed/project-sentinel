<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Command;

final readonly class RevokeEventSourceCredentialCommand
{
    public function __construct(
        private string $projectId,
        private string $applicationId,
        private string $environmentId,
        private string $eventSourceId,
        private string $credentialId,
    ) {}

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

    public function eventSourceId(): string
    {
        return $this->eventSourceId;
    }

    public function credentialId(): string
    {
        return $this->credentialId;
    }
}
