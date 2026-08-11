<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class IngestEventBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:100'],
            'events.*.event_type' => ['required', 'string'],
            'events.*.schema_version' => ['required', 'string'],
            'events.*.occurred_at' => ['required', 'string'],
            'events.*.payload' => ['present', 'array'],
            'events.*.external_event_id' => ['sometimes', 'nullable', 'string'],
            'events.*.severity' => ['sometimes', 'nullable', 'string'],
            'events.*.attributes' => ['sometimes', 'array'],
            'events.*.correlation_id' => ['sometimes', 'nullable', 'string'],
            'events.*.causation_id' => ['sometimes', 'nullable', 'string'],
            'events.*.trace_id' => ['sometimes', 'nullable', 'string'],
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return [...$this->all(), 'idempotency_key' => $this->header('Idempotency-Key')];
    }
}
