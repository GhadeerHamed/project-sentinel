<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Command;

final readonly class CreateEnvironmentCommand
{
    private function __construct(
        private string $projectId,
        private string $applicationId,
        private string $name,
        private string $slug,
        private string $environmentType,
        private string $idempotencyKey,
    ) {}

    public static function fromValues(
        string $projectId,
        string $applicationId,
        string $name,
        string $slug,
        string $environmentType,
        string $idempotencyKey,
    ): self {
        return new self(
            projectId: trim($projectId),
            applicationId: trim($applicationId),
            name: trim($name),
            slug: strtolower(trim($slug)),
            environmentType: strtolower(trim($environmentType)),
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

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    /** @return array{project_id: string, application_id: string, name: string, slug: string, environment_type: string} */
    public function normalizedPayload(): array
    {
        return [
            'project_id' => $this->projectId,
            'application_id' => $this->applicationId,
            'name' => $this->name,
            'slug' => $this->slug,
            'environment_type' => $this->environmentType,
        ];
    }
}
