<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;

final class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $supportedPermissions = implode(',', CorePermissions::values());

        return [
            'grant' => ['bail', 'present', 'array'],
            'grant.*' => ['bail', 'string', 'distinct:strict', 'in:'.$supportedPermissions],
            'revoke' => ['bail', 'present', 'array'],
            'revoke.*' => ['bail', 'string', 'distinct:strict', 'in:'.$supportedPermissions],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->all()), ['grant', 'revoke']) as $field) {
                $validator->errors()->add($field, 'The field is not supported.');
            }

            $grants = $this->input('grant');
            $revocations = $this->input('revoke');
            if (! is_array($grants) || ! is_array($revocations)) {
                return;
            }

            if ($grants === [] && $revocations === []) {
                $validator->errors()->add('grant', 'At least one permission change is required.');
            }

            foreach (array_intersect($grants, $revocations) as $permission) {
                $validator->errors()->add('grant', sprintf('The permission %s cannot be granted and revoked together.', $permission));
            }
        }];
    }
}
