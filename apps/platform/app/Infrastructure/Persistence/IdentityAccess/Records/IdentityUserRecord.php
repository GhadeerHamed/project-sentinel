<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $identity_id
 * @property string $email
 * @property string $display_name
 * @property string $status
 * @property int $version
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class IdentityUserRecord extends Model
{
    protected $table = 'identity_users';

    protected $primaryKey = 'identity_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'identity_id',
        'email',
        'display_name',
        'status',
        'version',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
