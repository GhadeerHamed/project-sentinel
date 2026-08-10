<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Query;

use JsonException;
use Sentinel\Application\Domain\ValueObject\ApplicationStatus;
use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;

final readonly class ListApplicationsQuery
{
    public const DEFAULT_LIMIT = 50;

    public const MAXIMUM_LIMIT = 100;

    private function __construct(
        private ApplicationStatus $status,
        private ?string $name,
        private ?string $slug,
        private int $limit,
        private ?CreatedAtIdCursor $cursor,
    ) {}

    public static function fromValues(
        ?string $status,
        ?string $name,
        ?string $slug,
        ?int $limit = null,
        ?CreatedAtIdCursor $cursor = null,
    ): self {
        return new self(
            status: ApplicationStatus::fromString($status ?? ApplicationStatus::ACTIVE->value),
            name: self::normalizedOptional($name),
            slug: self::normalizedOptional($slug),
            limit: $limit ?? self::DEFAULT_LIMIT,
            cursor: $cursor,
        );
    }

    public function status(): ApplicationStatus
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

    public function cursor(): ?CreatedAtIdCursor
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
