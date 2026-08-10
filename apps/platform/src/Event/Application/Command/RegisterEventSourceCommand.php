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
        private string $idempotencyKey,
    ) {}

    public static function fromValues(
        string $projectId,
        string $applicationId,
        string $environmentId,
        string $name,
        string $idempotencyKey,
    ): self {
        return new self(
            projectId: trim($projectId),
            applicationId: trim($applicationId),
            environmentId: trim($environmentId),
            name: trim($name),
            idempotencyKey: trim($idempotencyKey),
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

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    /** @return array{project_id: string, application_id: string, environment_id: string, name: string} */
    public function normalizedPayload(): array
    {
        return [
            'project_id' => $this->projectId,
            'application_id' => $this->applicationId,
            'environment_id' => $this->environmentId,
            'name' => $this->name,
        ];
    }
}
