<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CreateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:255'],
            'slug' => ['bail', 'required', 'string', 'max:255', 'regex:/\\A[a-z0-9]+(?:-[a-z0-9]+)*\\z/'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $unknownFields = array_diff(array_keys($this->all()), ['name', 'slug']);

                foreach ($unknownFields as $field) {
                    $validator->errors()->add($field, 'The field is not supported.');
                }
            },
        ];
    }
}
