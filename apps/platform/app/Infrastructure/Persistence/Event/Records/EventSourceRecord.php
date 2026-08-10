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
 * @property string $source_type
 * @property string $name
 * @property string $status
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 * @property int $version
 */
final class EventSourceRecord extends Model
{
    protected $table = 'event_sources';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'organization_id',
        'project_id',
        'application_id',
        'environment_id',
        'source_type',
        'name',
        'status',
        'created_at',
        'updated_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }
}
