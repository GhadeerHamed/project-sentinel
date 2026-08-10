<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Query;

use JsonException;
use Sentinel\Project\Domain\ValueObject\ProjectStatus;

final readonly class ListProjectsQuery
{
    public const DEFAULT_LIMIT = 50;

    public const MAXIMUM_LIMIT = 100;

    private function __construct(
        private ProjectStatus $status,
        private ?string $name,
        private ?string $slug,
        private int $limit,
        private ?ProjectListCursor $cursor,
    ) {}

    public static function fromValues(
        ?string $status,
        ?string $name,
        ?string $slug,
        ?int $limit = null,
        ?ProjectListCursor $cursor = null,
    ): self {
        return new self(
            status: ProjectStatus::fromString($status ?? ProjectStatus::ACTIVE->value),
            name: self::normalizedOptional($name),
            slug: self::normalizedOptional($slug),
            limit: $limit ?? self::DEFAULT_LIMIT,
            cursor: $cursor,
        );
    }

    public function status(): ProjectStatus
    {
        return $this->status;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function slug(): ?string
    {
        return $this->slug;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function cursor(): ?ProjectListCursor
    {
        return $this->cursor;
    }

    /**
     * @throws JsonException
     */
    public function filterHash(): string
    {
        return hash('sha256', json_encode([
            'status' => $this->status->value,
            'name' => $this->name,
            'slug' => $this->slug,
        ], JSON_THROW_ON_ERROR));
    }

    private static function normalizedOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
