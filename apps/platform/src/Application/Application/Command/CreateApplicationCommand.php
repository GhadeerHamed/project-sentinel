<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Command;

final readonly class CreateApplicationCommand
{
    private function __construct(
        private string $projectId,
        private string $name,
        private string $slug,
        private ?string $description,
        private string $criticality,
        private string $idempotencyKey,
    ) {}

    public static function fromValues(
        string $projectId,
        string $name,
        string $slug,
        ?string $description,
        string $criticality,
        string $idempotencyKey,
    ): self {
        $normalizedDescription = $description === null ? null : trim($description);

        return new self(
            projectId: trim($projectId),
            name: trim($name),
            slug: strtolower(trim($slug)),
            description: $normalizedDescription === '' ? null : $normalizedDescription,
            criticality: strtolower(trim($criticality)),
            idempotencyKey: trim($idempotencyKey),
        );
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function criticality(): string
    {
        return $this->criticality;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    /** @return array{project_id: string, name: string, slug: string, description: ?string, criticality: string} */
    public function normalizedPayload(): array
    {
        return [
            'project_id' => $this->projectId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'criticality' => $this->criticality,
        ];
    }
}
