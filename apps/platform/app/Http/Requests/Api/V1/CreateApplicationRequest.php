<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CreateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:255'],
            'slug' => ['bail', 'required', 'string', 'max:255', 'regex:/\\A[a-z0-9]+(?:-[a-z0-9]+)*\\z/'],
            'description' => ['bail', 'nullable', 'string'],
            'criticality' => ['bail', 'required', 'string', 'in:critical,high,medium,low'],
            'idempotency_key' => ['bail', 'required', 'string', 'max:255', 'regex:/\\S/'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [...$this->all(), 'idempotency_key' => $this->header('Idempotency-Key')];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->all()), ['name', 'slug', 'description', 'criticality']) as $field) {
                $validator->errors()->add($field, 'The field is not supported.');
            }
        }];
    }
}
