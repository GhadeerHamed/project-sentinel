<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Sentinel\Project\Domain\ValueObject\ProjectId;

final class ArchiveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'project' => ['bail', 'required', 'string', 'regex:/\\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\\z/i'],
            'if_match' => ['bail', 'required', 'regex:/\\A"?[1-9][0-9]*"?\\z/'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [
            'project' => $this->route('project'),
            'if_match' => $this->header('If-Match'),
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_keys($this->all()) as $field) {
                $validator->errors()->add($field, 'The field is not supported.');
            }
        }];
    }

    public function projectId(): ProjectId
    {
        return ProjectId::fromString((string) $this->validated('project'));
    }

    public function expectedVersion(): int
    {
        return (int) trim((string) $this->validated('if_match'), '"');
    }
}
