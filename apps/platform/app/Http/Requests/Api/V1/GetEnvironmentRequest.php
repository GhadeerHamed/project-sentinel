<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Project\Domain\ValueObject\ProjectId;

final class GetEnvironmentRequest extends FormRequest
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
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [
            'project' => $this->route('project'),
            'application' => $this->route('application'),
            'environment' => $this->route('environment'),
        ];
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
}
