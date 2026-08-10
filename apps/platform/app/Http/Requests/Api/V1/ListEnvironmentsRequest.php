<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use JsonException;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\Query\ListEnvironmentsQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;

final class ListEnvironmentsRequest extends FormRequest
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
            'status' => ['bail', 'nullable', 'string', 'in:active,archived'],
            'environment_type' => ['bail', 'nullable', 'string', 'in:production,staging,testing,development,sandbox,other'],
            'slug' => ['bail', 'nullable', 'string', 'max:255', 'regex:/\\A[a-z0-9]+(?:-[a-z0-9]+)*\\z/'],
            'limit' => ['bail', 'nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['bail', 'nullable', 'string', 'max:2048'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [...$this->query(), 'project' => $this->route('project'), 'application' => $this->route('application')];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->query()), ['status', 'environment_type', 'slug', 'limit', 'cursor']) as $filter) {
                $validator->errors()->add($filter, 'The filter is not supported.');
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

    public function environmentListQuery(): ListEnvironmentsQuery
    {
        $status = $this->validated('status');
        $environmentType = $this->validated('environment_type');
        $slug = $this->validated('slug');
        $limit = $this->validated('limit');
        $cursor = $this->validated('cursor');
        $query = ListEnvironmentsQuery::fromValues(
            is_string($status) ? $status : null,
            is_string($environmentType) ? $environmentType : null,
            is_string($slug) ? $slug : null,
            is_numeric($limit) ? (int) $limit : null,
        );

        if (! is_string($cursor)) {
            return $query;
        }

        try {
            return ListEnvironmentsQuery::fromValues(
                is_string($status) ? $status : null,
                is_string($environmentType) ? $environmentType : null,
                is_string($slug) ? $slug : null,
                is_numeric($limit) ? (int) $limit : null,
                CreatedAtIdCursor::fromEncoded($cursor, $query->filterHash()),
            );
        } catch (InvalidArgumentException|JsonException) {
            throw ValidationException::withMessages(['cursor' => ['The cursor is invalid.']]);
        }
    }
}
