<?php

declare(strict_types=1);

namespace App\Infrastructure\Authentication;

use App\Infrastructure\Persistence\IdentityAccess\Records\IdentityCredentialRecord;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Sentinel\IdentityAccess\Domain\ValueObject\IdentityStatus;
use Sentinel\Shared\Domain\Identity\IdentityReference;
use Sentinel\Shared\Domain\Identity\IdentityType;

/**
 * @property string $identity_id
 * @property string $email
 * @property string $display_name
 * @property string $status
 * @property int $version
 */
#[Fillable(['identity_id', 'email', 'display_name', 'status', 'version'])]
final class IdentityUserAuthenticatable extends Authenticatable
{
    protected $table = 'identity_users';

    protected $primaryKey = 'identity_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function getAuthIdentifierName(): string
    {
        return 'identity_id';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->passwordCredential()->value('secret_reference');
    }

    public function canAuthenticate(): bool
    {
        return $this->status === IdentityStatus::ACTIVE->value;
    }

    public function identityReference(): IdentityReference
    {
        return IdentityReference::fromString(
            identityId: $this->identity_id,
            identityType: IdentityType::USER->value,
        );
    }

    /**
     * @return HasOne<IdentityCredentialRecord, $this>
     */
    public function passwordCredential(): HasOne
    {
        return $this->hasOne(
            related: IdentityCredentialRecord::class,
            foreignKey: 'identity_id',
            localKey: 'identity_id',
        )
            ->where('credential_type', 'password')
            ->where('status', 'active');
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
