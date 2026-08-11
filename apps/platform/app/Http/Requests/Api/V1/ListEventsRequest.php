<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Sentinel\Event\Application\Query\ListEventsQuery;

final class ListEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['project_id' => ['nullable', 'uuid'], 'application_id' => ['nullable', 'uuid'], 'environment_id' => ['nullable', 'uuid'], 'event_source_id' => ['nullable', 'uuid'], 'event_type' => ['nullable', 'in:application.error,application.health_failed,application.health_recovered,deployment.completed,custom.operational_event'], 'category' => ['nullable', 'in:error,availability,deployment,platform'], 'severity' => ['nullable', 'in:critical,high,medium,low,informational,unknown'], 'occurred_from' => ['nullable', 'date_format:c'], 'occurred_to' => ['nullable', 'date_format:c'], 'received_from' => ['nullable', 'date_format:c'], 'received_to' => ['nullable', 'date_format:c'], 'limit' => ['nullable', 'integer', 'min:1', 'max:100'], 'cursor' => ['nullable', 'string', 'max:2048']];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->query()), ['project_id', 'application_id', 'environment_id', 'event_source_id', 'event_type', 'category', 'severity', 'occurred_from', 'occurred_to', 'received_from', 'received_to', 'limit', 'cursor']) as $key) {
                $validator->errors()->add($key, 'The filter is not supported.');
            }
        }];
    }

    public function queryModel(): ListEventsQuery
    {
        $filters = [];
        foreach (['project_id', 'application_id', 'environment_id', 'event_source_id', 'event_type', 'category', 'severity', 'occurred_from', 'occurred_to', 'received_from', 'received_to'] as $key) {
            $value = $this->validated($key);
            $filters[$key] = is_string($value) ? $value : null;
        }
        $base = new ListEventsQuery($filters, (int) ($this->validated('limit') ?? 50));
        $cursor = $this->validated('cursor');
        if (! is_string($cursor)) {
            return $base;
        }
        try {
            $decoded = base64_decode(strtr($cursor, '-_', '+/').str_repeat('=', (4 - strlen($cursor) % 4) % 4), true);
            $payload = $decoded === false ? null : json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($payload) || ! isset($payload['occurred_at'], $payload['id'], $payload['filter_hash']) || ! hash_equals($base->filterHash(), (string) $payload['filter_hash'])) {
                throw new \InvalidArgumentException;
            } $at = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:s.uP', (string) $payload['occurred_at']);
            if ($at === false) {
                throw new \InvalidArgumentException;
            }

            return new ListEventsQuery($filters, $base->limit(), $at, (string) $payload['id']);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['cursor' => ['The cursor is invalid.']]);
        }
    }
}
