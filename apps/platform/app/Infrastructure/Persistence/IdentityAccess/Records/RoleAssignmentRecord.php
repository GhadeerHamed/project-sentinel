<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $identity_id
 * @property string $role_id
 * @property string $assigned_by_identity_id
 * @property \DateTimeImmutable $assigned_at
 * @property \DateTimeImmutable|null $expires_at
 * @property \DateTimeImmutable|null $revoked_at
 * @property string|null $revoked_by_identity_id
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class RoleAssignmentRecord extends Model
{
    protected $table = 'role_assignments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'identity_id',
        'role_id',
        'assigned_by_identity_id',
        'assigned_at',
        'expires_at',
        'revoked_at',
        'revoked_by_identity_id',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
