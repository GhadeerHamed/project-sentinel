# API Errors

## Purpose

This document defines the standard error contract used by Project Sentinel APIs.

All endpoints must return errors in a consistent, secure, and machine-readable format.

---

## Error Response

```json
{
  "error": {
    "code": "incident.invalid_transition",
    "message": "The incident cannot be resolved from its current state.",
    "details": {
      "current_status": "declared"
    }
  },
  "meta": {
    "request_id": "0198f3b6-91c8-7c30-b718-6f85c83e3875",
    "correlation_id": "0198f3b7-41e9-7763-b662-d8d9434deac5"
  }
}
```

Fields:

| Field                 | Purpose                            |
| --------------------- | ---------------------------------- |
| `error.code`          | Stable machine-readable identifier |
| `error.message`       | Safe human-readable explanation    |
| `error.details`       | Optional structured context        |
| `meta.request_id`     | Identifies the HTTP request        |
| `meta.correlation_id` | Connects related operations        |

---

## Error Codes

Error codes use lowercase dot-separated names.

Format:

```text
{domain}.{condition}
```

Examples:

```text
authentication.required
authorization.denied
request.validation_failed
resource.not_found
incident.invalid_transition
workflow.already_completed
concurrency.version_conflict
rate_limit.exceeded
```

Published error codes must remain stable within an API major Version.

---

## Standard HTTP Mapping

| Status | Use                                                 |
| -----: | --------------------------------------------------- |
|  `400` | Malformed or unsupported request                    |
|  `401` | Authentication missing or invalid                   |
|  `403` | Authenticated identity lacks permission             |
|  `404` | Resource does not exist or is not visible           |
|  `409` | Business state, uniqueness, or idempotency conflict |
|  `412` | Expected Resource Version does not match            |
|  `422` | Request is valid JSON but violates business rules   |
|  `429` | Rate limit exceeded                                 |
|  `500` | Unexpected internal error                           |
|  `502` | Upstream provider returned an invalid response      |
|  `503` | Required dependency is unavailable                  |
|  `504` | Upstream operation timed out                        |

---

## Validation Errors

Field validation failures use:

```json
{
  "error": {
    "code": "request.validation_failed",
    "message": "The request contains invalid fields.",
    "details": {
      "fields": {
        "severity": [
          "The value must be one of: critical, high, medium, low."
        ],
        "title": [
          "The field is required."
        ]
      }
    }
  }
}
```

Rules:

* Field names use API field paths.
* Multiple errors may be returned per field.
* Unknown write fields must be rejected.
* Validation messages must not expose internal implementation details.

---

## Authentication Errors

Common codes:

```text
authentication.required
authentication.invalid_credential
authentication.expired
authentication.revoked
authentication.mfa_required
authentication.session_expired
```

Authentication failures normally return:

```http
401 Unauthorized
```

Responses must not reveal credential or identity existence unnecessarily.

---

## Authorization Errors

Common codes:

```text
authorization.denied
authorization.scope_denied
authorization.approval_required
authorization.authentication_strength_insufficient
```

Authorization failures normally return:

```http
403 Forbidden
```

Sentinel may return `404` instead when revealing the Resource would expose unauthorized information.

---

## Resource Errors

Common codes:

```text
resource.not_found
resource.archived
resource.deleted
resource.unavailable
```

Resource lookup must always include Organization scope.

A Resource belonging to another Organization must not be disclosed.

---

## Conflict Errors

Use `409 Conflict` for conditions such as:

* Duplicate business identifiers.
* repeated idempotency key with a different request.
* invalid lifecycle state.
* Resource already linked.
* operation already completed.

Example:

```json
{
  "error": {
    "code": "idempotency.key_conflict",
    "message": "The idempotency key was already used with a different request."
  }
}
```

---

## Concurrency Errors

Version conflicts use:

```http
412 Precondition Failed
```

Example:

```json
{
  "error": {
    "code": "concurrency.version_conflict",
    "message": "The resource was modified by another operation.",
    "details": {
      "expected_version": 8,
      "current_version": 9
    }
  }
}
```

The client should reload the Resource before retrying.

---

## Business Rule Errors

Use `422 Unprocessable Entity` when the request is structurally valid but the command violates domain rules.

Example codes:

```text
alert.cannot_acknowledge
incident.invalid_transition
knowledge.review_required
workflow.approval_missing
plugin.capability_not_granted
agent.execution_not_allowed
```

Business errors should explain the condition without exposing protected Policy details.

---

## Rate-Limit Errors

Rate-limit failures return:

```http
429 Too Many Requests
```

Example:

```json
{
  "error": {
    "code": "rate_limit.exceeded",
    "message": "The request rate limit has been exceeded.",
    "details": {
      "retry_after_seconds": 30
    }
  }
}
```

The response should include `Retry-After` when possible.

---

## Dependency Errors

External provider failures should be translated into Sentinel error codes.

Examples:

```text
dependency.unavailable
dependency.timeout
dependency.invalid_response
plugin.provider_unavailable
notification.provider_rejected
```

Raw provider messages must not be returned directly when they may contain sensitive information.

---

## Unexpected Errors

Unexpected failures return:

```http
500 Internal Server Error
```

Example:

```json
{
  "error": {
    "code": "platform.unexpected_error",
    "message": "An unexpected error occurred."
  },
  "meta": {
    "request_id": "0198f3b6-91c8-7c30-b718-6f85c83e3875"
  }
}
```

Internal details belong in protected Logs and traces, linked by `request_id`.

---

## Bulk Errors

Bulk operations must define whether they are atomic or allow partial success.

For partial success:

```json
{
  "data": {
    "succeeded": 2,
    "failed": 1,
    "items": [
      {
        "index": 0,
        "status": "succeeded",
        "resource_id": "0198f3b6-48ae-7a44-9192-6f8029df5f10"
      },
      {
        "index": 1,
        "status": "failed",
        "error": {
          "code": "alert.invalid_state",
          "message": "The alert cannot be acknowledged."
        }
      }
    ]
  }
}
```

Each failed item must preserve its own error code.

---

## Asynchronous Operation Errors

A request accepted with `202` may later fail.

The operation Resource should expose:

```json
{
  "status": "failed",
  "error": {
    "code": "plugin.synchronization_failed",
    "message": "The synchronization could not be completed."
  }
}
```

The original request should not later change its HTTP response.

---

## Retry Guidance

Errors may be classified as retryable or non-retryable.

Usually retryable:

* `429`
* `502`
* `503`
* `504`
* temporary provider failures
* concurrency conflicts after reloading state

Usually non-retryable without request changes:

* `400`
* `401`
* `403`
* `404`
* validation errors
* unsupported lifecycle transitions

Clients must use idempotency protection when retrying commands.

---

## Security Rules

Error responses must not expose:

* Stack traces.
* SQL statements.
* database schema details.
* credentials or secret references.
* internal network addresses.
* unauthorized Resource existence.
* another Organization’s data.
* private Policy implementation.
* unrestricted external provider responses.

Error details must contain only information the acting identity is authorized to view.

---

## Logging

Server-side error Logs should include:

* Error code.
* request identifier.
* correlation identifier.
* Organization identifier where permitted.
* identity type.
* endpoint.
* exception classification.
* retryability.

Logs must not contain plaintext credentials or unrestricted request payloads.

---

## Business Rules

* Every API error uses the standard error structure.
* Error codes are stable and machine-readable.
* HTTP status codes reflect the failure category.
* Validation errors identify affected fields.
* Version conflicts do not silently overwrite data.
* Unauthorized Resource existence is not disclosed.
* Provider failures are translated into Sentinel errors.
* Unexpected failures expose only a request identifier.
* Bulk operations preserve per-item errors.
* Sensitive data must not appear in errors or Logs.

---

## Related Documents

* `docs/api/00-api-overview.md`
* `docs/api/01-api-conventions.md`
* `docs/api/02-authentication.md`
* `docs/api/04-pagination-filtering.md`
* `docs/api/05-idempotency-concurrency.md`
* `docs/architecture/08-security-architecture.md`
