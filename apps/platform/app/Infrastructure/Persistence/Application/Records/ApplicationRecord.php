<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Application\Records;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $criticality
 * @property string $status
 * @property string $created_by_identity_id
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property \DateTimeImmutable|null $archived_at
 * @property int $version
 */
final class ApplicationRecord extends Model
{
    protected $table = 'applications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'project_id',
        'name',
        'slug',
        'description',
        'criticality',
        'status',
        'created_by_identity_id',
        'created_at',
        'updated_at',
        'archived_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }
}
