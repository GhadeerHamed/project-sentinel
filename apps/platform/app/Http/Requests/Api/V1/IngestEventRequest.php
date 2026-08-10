<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use JsonException;

final class IngestEventRequest extends FormRequest
{
    private const MAX_ENCODED_BYTES = 65_536;

    /** @var list<string> */
    private const ALLOWED_FIELDS = [
        'event_type',
        'schema_version',
        'occurred_at',
        'payload',
        'external_event_id',
        'severity',
        'attributes',
        'correlation_id',
        'causation_id',
        'trace_id',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['payload', 'attributes'] as $field) {
            $value = $this->input($field);

            if ($value instanceof \stdClass) {
                $normalized[$field] = (array) $value;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'event_type' => ['bail', 'required', 'string', 'in:application.error,application.health_failed,application.health_recovered,deployment.completed,custom.operational_event'],
            'schema_version' => ['bail', 'required', 'string', 'in:1'],
            'occurred_at' => ['bail', 'required', 'string', 'max:64', 'regex:/(?:Z|[+-]\\d{2}:\\d{2})\\z/'],
            'payload' => ['bail', 'present', 'array'],
            'external_event_id' => ['bail', 'sometimes', 'nullable', 'string', 'max:255', 'regex:/\\S/'],
            'severity' => ['bail', 'sometimes', 'nullable', 'string', 'in:critical,high,medium,low,informational,unknown'],
            'attributes' => ['bail', 'sometimes', 'array'],
            'correlation_id' => ['bail', 'sometimes', 'nullable', 'string', 'regex:/\\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\\z/i'],
            'causation_id' => ['bail', 'sometimes', 'nullable', 'string', 'regex:/\\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\\z/i'],
            'trace_id' => ['bail', 'sometimes', 'nullable', 'string', 'max:255', 'regex:/\\S/'],
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
            foreach (array_diff(array_keys($this->all()), self::ALLOWED_FIELDS) as $field) {
                $validator->errors()->add($field, 'The field is not supported.');
            }

            $document = $this->jsonDocument();

            if ($document === null || ! property_exists($document, 'payload') || ! is_object($document->payload)) {
                $validator->errors()->add('payload', 'The payload must be a JSON object.');
            }

            if (property_exists($document ?? new \stdClass, 'attributes') && ! is_object($document->attributes)) {
                $validator->errors()->add('attributes', 'The attributes must be a JSON object.');
            }

            $attributes = $this->input('attributes', []);

            if (is_array($attributes)) {
                foreach ($attributes as $attribute) {
                    if (! is_scalar($attribute)) {
                        $validator->errors()->add('attributes', 'The attributes must contain only scalar values.');

                        break;
                    }
                }
            }

            $payload = $this->input('payload');

            if (! is_array($payload) || ! is_array($attributes)) {
                return;
            }

            try {
                $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
                $encodedAttributes = json_encode($attributes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            } catch (JsonException) {
                $validator->errors()->add('payload', 'The payload must be JSON-compatible.');

                return;
            }

            if (strlen($encodedPayload) + strlen($encodedAttributes) > self::MAX_ENCODED_BYTES) {
                $validator->errors()->add('payload', 'The payload and attributes must not exceed 64 KiB.');
            }
        }];
    }

    private function jsonDocument(): ?\stdClass
    {
        try {
            $decoded = json_decode($this->getContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return $decoded instanceof \stdClass ? $decoded : null;
    }
}
