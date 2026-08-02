<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Shared\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $organization_id
 * @property string $event_id
 * @property string $event_type
 * @property int $event_version
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property int $aggregate_version
 * @property array<string, mixed> $payload
 * @property \DateTimeImmutable $occurred_at
 * @property \DateTimeImmutable $created_at
 * @property string $correlation_id
 * @property string|null $causation_id
 * @property \DateTimeImmutable|null $published_at
 * @property int $attempt_count
 * @property string $status
 */
final class OutboxMessageRecord extends Model
{
    protected $table = 'outbox_messages';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'event_id',
        'event_type',
        'event_version',
        'aggregate_type',
        'aggregate_id',
        'aggregate_version',
        'payload',
        'occurred_at',
        'created_at',
        'correlation_id',
        'causation_id',
        'published_at',
        'attempt_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_version' => 'integer',
            'aggregate_version' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'attempt_count' => 'integer',
        ];
    }
}
