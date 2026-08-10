<?php

declare(strict_types=1);

namespace Sentinel\Shared\Application\Pagination;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use Sentinel\Shared\Domain\Identifier\InvalidResourceId;
use Sentinel\Shared\Domain\Identifier\ResourceId;

final readonly class CreatedAtIdCursor
{
    private function __construct(
        private DateTimeImmutable $createdAt,
        private ResourceId $id,
    ) {}

    public static function fromValues(DateTimeImmutable $createdAt, string $id): self
    {
        return new self($createdAt, CursorResourceId::fromString($id));
    }

    public static function fromEncoded(string $cursor, string $filterHash): self
    {
        try {
            $base64 = strtr($cursor, '-_', '+/');
            $decoded = base64_decode($base64.str_repeat('=', (4 - strlen($base64) % 4) % 4), true);

            if ($decoded === false) {
                throw new InvalidArgumentException('The cursor is not valid base64url.');
            }

            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($payload)
                || ! isset($payload['created_at'], $payload['id'], $payload['filter_hash'])
                || ! is_string($payload['created_at'])
                || ! is_string($payload['id'])
                || ! is_string($payload['filter_hash'])
                || ! hash_equals($filterHash, $payload['filter_hash'])) {
                throw new InvalidArgumentException('The cursor is malformed or incompatible with the query.');
            }

            $createdAt = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:s.uP', $payload['created_at']);
            if ($createdAt === false) {
                throw new InvalidArgumentException('The cursor timestamp is invalid.');
            }

            return self::fromValues($createdAt, $payload['id']);
        } catch (JsonException|InvalidResourceId $exception) {
            throw new InvalidArgumentException('The cursor is malformed.', previous: $exception);
        }
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function id(): string
    {
        return $this->id->value();
    }

    /**
     * @throws JsonException
     */
    public function encode(string $filterHash): string
    {
        return rtrim(strtr(base64_encode(json_encode([
            'created_at' => $this->createdAt->format('Y-m-d\\TH:i:s.uP'),
            'id' => $this->id->value(),
            'filter_hash' => $filterHash,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
}

final readonly class CursorResourceId extends ResourceId {}
