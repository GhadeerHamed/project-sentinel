<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string $application_id
 * @property string $environment_id
 * @property string $event_source_id
 * @property string|null $external_event_id
 * @property string $event_type
 * @property string $category
 * @property string|null $severity
 * @property string $schema_version
 * @property \DateTimeImmutable $occurred_at
 * @property \DateTimeImmutable $received_at
 * @property array<string, mixed> $payload
 * @property array<string, scalar> $attributes
 * @property string|null $correlation_id
 * @property string|null $causation_id
 * @property string|null $trace_id
 * @property string $normalized_event_hash
 * @property \DateTimeImmutable $created_at
 */
final class EventRecord extends Model
{
    protected $table = 'events';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'project_id',
        'application_id',
        'environment_id',
        'event_source_id',
        'external_event_id',
        'event_type',
        'category',
        'severity',
        'schema_version',
        'occurred_at',
        'received_at',
        'payload',
        'attributes',
        'correlation_id',
        'causation_id',
        'trace_id',
        'normalized_event_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'payload' => 'array',
            'attributes' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
