<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class SuspendMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['if_match' => ['bail', 'required', 'regex:/\A"?[1-9][0-9]*"?\z/']];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return ['if_match' => $this->header('If-Match')];
    }

    public function expectedVersion(): int
    {
        return (int) trim((string) $this->validated('if_match'), '"');
    }
}
