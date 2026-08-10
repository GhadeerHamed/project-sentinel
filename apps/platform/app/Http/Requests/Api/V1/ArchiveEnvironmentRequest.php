<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Project\Domain\ValueObject\ProjectId;

final class ArchiveEnvironmentRequest extends FormRequest
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
            'application' => ['bail', 'required', 'string', 'regex:/\\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\\z/i'],
            'environment' => ['bail', 'required', 'string', 'regex:/\\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\\z/i'],
            'if_match' => ['bail', 'required', 'string', 'regex:/\\A\"?[1-9][0-9]*\"?\\z/'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [
            'project' => $this->route('project'),
            'application' => $this->route('application'),
            'environment' => $this->route('environment'),
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

    public function applicationId(): ApplicationId
    {
        return ApplicationId::fromString((string) $this->validated('application'));
    }

    public function environmentId(): EnvironmentId
    {
        return EnvironmentId::fromString((string) $this->validated('environment'));
    }

    public function expectedVersion(): int
    {
        return (int) trim((string) $this->validated('if_match'), '"');
    }
}
