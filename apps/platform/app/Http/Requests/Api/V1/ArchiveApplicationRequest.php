<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Domain\ValueObject\ProjectId;

final class ArchiveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['project' => ['required', 'regex:/\\A[0-9a-f-]+\\z/i'], 'application' => ['required', 'regex:/\\A[0-9a-f-]+\\z/i'], 'if_match' => ['required', 'regex:/\\A"?[1-9][0-9]*"?\\z/']];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return ['project' => $this->route('project'), 'application' => $this->route('application'), 'if_match' => $this->header('If-Match')];
    }

    public function projectId(): ProjectId
    {
        return ProjectId::fromString((string) $this->validated('project'));
    }

    public function applicationId(): ApplicationId
    {
        return ApplicationId::fromString((string) $this->validated('application'));
    }

    public function expectedVersion(): int
    {
        return (int) trim((string) $this->validated('if_match'), '"');
    }
}
