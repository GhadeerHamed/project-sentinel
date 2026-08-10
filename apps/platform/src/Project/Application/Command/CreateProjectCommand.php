<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Command;

final readonly class CreateProjectCommand
{
    private function __construct(
        private string $name,
        private string $slug,
        private ?string $description,
        private string $idempotencyKey,
    ) {}

    public static function fromValues(string $name, string $slug, ?string $description, string $idempotencyKey): self
    {
        $normalizedDescription = $description === null ? null : trim($description);

        return new self(
            name: trim($name),
            slug: strtolower(trim($slug)),
            description: $normalizedDescription === '' ? null : $normalizedDescription,
            idempotencyKey: trim($idempotencyKey),
        );
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

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    /** @return array{name: string, slug: string, description: ?string} */
    public function normalizedPayload(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }
}
