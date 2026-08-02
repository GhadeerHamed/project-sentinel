<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Shared\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $organization_id
 * @property string $actor_type
 * @property string|null $actor_id
 * @property string $action
 * @property string $resource_type
 * @property string|null $resource_id
 * @property \DateTimeImmutable $occurred_at
 * @property \DateTimeImmutable $recorded_at
 * @property string $correlation_id
 * @property string|null $causation_id
 */
final class AuditRecordModel extends Model
{
    protected $table = 'audit_records';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'actor_type',
        'actor_id',
        'action',
        'resource_type',
        'resource_id',
        'occurred_at',
        'recorded_at',
        'correlation_id',
        'causation_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
