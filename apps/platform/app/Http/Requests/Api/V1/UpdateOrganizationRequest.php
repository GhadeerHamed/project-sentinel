<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'sometimes', 'string', 'max:255'],
            'timezone' => ['bail', 'sometimes', 'string', 'timezone'],
            'if_match' => ['bail', 'required', 'regex:/\A"?[1-9][0-9]*"?\z/'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [...$this->all(), 'if_match' => $this->header('If-Match')];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $unknownFields = array_diff(array_keys($this->all()), ['name', 'timezone']);
            foreach ($unknownFields as $field) {
                $validator->errors()->add($field, 'The field is not supported.');
            }
            if (! $this->exists('name') && ! $this->exists('timezone')) {
                $validator->errors()->add('name', 'At least one profile field is required.');
            }
        }];
    }

    public function expectedVersion(): int
    {
        return (int) trim((string) $this->validated('if_match'), '"');
    }
}
