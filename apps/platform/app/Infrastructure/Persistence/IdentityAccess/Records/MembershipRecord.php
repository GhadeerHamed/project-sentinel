<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $user_identity_id
 * @property string $status
 * @property \DateTimeImmutable|null $activated_at
 * @property \DateTimeImmutable|null $suspended_at
 * @property \DateTimeImmutable|null $removed_at
 * @property int $version
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class MembershipRecord extends Model
{
    protected $table = 'memberships';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'user_identity_id',
        'status',
        'activated_at',
        'suspended_at',
        'removed_at',
        'version',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'activated_at' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
            'removed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
