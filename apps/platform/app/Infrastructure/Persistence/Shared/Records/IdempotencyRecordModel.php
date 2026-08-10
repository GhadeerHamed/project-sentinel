<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Shared\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $identity_id
 * @property string $operation
 * @property string $idempotency_key
 * @property string $request_hash
 * @property string $status
 * @property int|null $response_status
 * @property string|null $response_reference
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable|null $completed_at
 */
final class IdempotencyRecordModel extends Model
{
    protected $table = 'idempotency_records';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'identity_id',
        'operation',
        'idempotency_key',
        'request_hash',
        'status',
        'response_status',
        'response_reference',
        'created_at',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'created_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
