<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $event_source_id
 * @property string $secret_hash
 * @property string $status
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable|null $revoked_at
 * @property \DateTimeImmutable|null $expires_at
 */
final class EventSourceCredentialRecord extends Model
{
    protected $table = 'event_source_credentials';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'organization_id',
        'event_source_id',
        'secret_hash',
        'status',
        'created_at',
        'revoked_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
