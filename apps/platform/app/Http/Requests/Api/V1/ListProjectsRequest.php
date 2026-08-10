<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use JsonException;
use Sentinel\Project\Application\Query\ListProjectsQuery;
use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;

final class ListProjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'status' => ['bail', 'nullable', 'string', 'in:active,archived'],
            'name' => ['bail', 'nullable', 'string', 'max:255'],
            'slug' => ['bail', 'nullable', 'string', 'max:255', 'regex:/\\A[a-z0-9]+(?:-[a-z0-9]+)*\\z/'],
            'limit' => ['bail', 'nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['bail', 'nullable', 'string', 'max:2048'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return $this->query();
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->query()), ['status', 'name', 'slug', 'limit', 'cursor']) as $filter) {
                $validator->errors()->add($filter, 'The filter is not supported.');
            }
        }];
    }

    public function projectListQuery(): ListProjectsQuery
    {
        $status = $this->validated('status');
        $name = $this->validated('name');
        $slug = $this->validated('slug');
        $limit = $this->validated('limit');
        $cursor = $this->validated('cursor');

        $query = ListProjectsQuery::fromValues(
            is_string($status) ? $status : null,
            is_string($name) ? $name : null,
            is_string($slug) ? $slug : null,
            is_numeric($limit) ? (int) $limit : null,
        );

        if (! is_string($cursor)) {
            return $query;
        }

        try {
            return ListProjectsQuery::fromValues(
                is_string($status) ? $status : null,
                is_string($name) ? $name : null,
                is_string($slug) ? $slug : null,
                is_numeric($limit) ? (int) $limit : null,
                CreatedAtIdCursor::fromEncoded($cursor, $query->filterHash()),
            );
        } catch (InvalidArgumentException|JsonException) {
            throw ValidationException::withMessages(['cursor' => ['The cursor is invalid.']]);
        }
    }
}
