<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Records;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $identity_id
 * @property string $credential_type
 * @property string $secret_reference
 * @property string $status
 * @property \DateTimeImmutable $created_at
 */
#[Fillable(['id', 'identity_id', 'credential_type', 'status', 'created_at'])]
#[Hidden(['secret_reference'])]
final class IdentityCredentialRecord extends Model
{
    protected $table = 'identity_credentials';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'last_used_at' => 'immutable_datetime',
            'rotated_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
