<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Query;

use JsonException;
use Sentinel\Environment\Domain\ValueObject\EnvironmentStatus;
use Sentinel\Environment\Domain\ValueObject\EnvironmentType;
use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;

final readonly class ListEnvironmentsQuery
{
    public const DEFAULT_LIMIT = 50;

    public const MAXIMUM_LIMIT = 100;

    private function __construct(
        private EnvironmentStatus $status,
        private ?EnvironmentType $environmentType,
        private ?string $slug,
        private int $limit,
        private ?CreatedAtIdCursor $cursor,
    ) {}

    public static function fromValues(
        ?string $status,
        ?string $environmentType,
        ?string $slug,
        ?int $limit = null,
        ?CreatedAtIdCursor $cursor = null,
    ): self {
        return new self(
            status: EnvironmentStatus::fromString($status ?? EnvironmentStatus::ACTIVE->value),
            environmentType: $environmentType === null ? null : EnvironmentType::fromString($environmentType),
            slug: self::normalizedSlug($slug),
            limit: $limit ?? self::DEFAULT_LIMIT,
            cursor: $cursor,
        );
    }

    public function status(): EnvironmentStatus
    {
        return $this->status;
    }

    public function environmentType(): ?EnvironmentType
    {
        return $this->environmentType;
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
            'environment_type' => $this->environmentType?->value,
            'slug' => $this->slug,
        ], JSON_THROW_ON_ERROR));
    }

    private static function normalizedSlug(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return $normalized === '' ? null : $normalized;
    }
}
