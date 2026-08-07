<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Organization\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $timezone
 * @property string $created_by_identity_id
 * @property string $status
 * @property int $version
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class OrganizationRecord extends Model
{
    protected $table = 'organizations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'timezone',
        'created_by_identity_id',
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
