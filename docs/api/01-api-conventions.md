# API Conventions

## Purpose

This document defines the shared conventions used by all Project Sentinel HTTP APIs.

Resource-specific endpoints are documented separately.

---

## Base URL

Public API routes use:

```text
/api/v1
```

Tenant-owned Resources should include the Organization in the route:

```text
/api/v1/organizations/{organization_id}/incidents
```

---

## Protocol and Format

The API uses:

* HTTPS.
* JSON request and response bodies.
* UTF-8 encoding.
* UTC timestamps.
* standard HTTP methods and status codes.

JSON requests must include:

```http
Content-Type: application/json
Accept: application/json
```

---

## Naming

JSON field names and query parameters use:

```text
snake_case
```

Examples:

```text
organization_id
created_at
expected_version
next_cursor
```

Resource names in routes use plural nouns:

```text
/organizations
/applications
/alerts
/incidents
```

---

## Identifiers

Public Resource identifiers use UUIDv7.

Example:

```text
0198f3b6-48ae-7a44-9192-6f8029df5f10
```

Identifiers are opaque.

Clients must not infer:

* Organization ownership.
* permissions.
* Resource Type.
* business state.

Resource identifiers do not grant access.

---

## Timestamps

Timestamps use ISO 8601 with UTC.

Example:

```text
2026-08-01T16:30:00Z
```

Fields should describe their exact meaning:

```text
occurred_at
recorded_at
created_at
updated_at
resolved_at
```

---

## Request Headers

Common request headers include:

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
X-Correlation-ID: {correlation_id}
Idempotency-Key: {idempotency_key}
If-Match: "{version}"
```

Only headers relevant to the operation are required.

---

## Response Headers

Sentinel should return:

```http
X-Request-ID: {request_id}
X-Correlation-ID: {correlation_id}
```

Rate-limited responses may also include limit and retry information.

---

## Single Resource Response

A successful Resource response uses:

```json
{
  "data": {
    "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
    "type": "incident",
    "attributes": {
      "title": "Checkout failures",
      "status": "investigating",
      "severity": "high",
      "version": 4,
      "created_at": "2026-08-01T16:30:00Z"
    }
  },
  "meta": {
    "request_id": "0198f3b6-91c8-7c30-b718-6f85c83e3875"
  }
}
```

---

## Collection Response

Collections use:

```json
{
  "data": [],
  "pagination": {
    "next_cursor": null,
    "has_more": false
  },
  "meta": {
    "request_id": "0198f3b6-91c8-7c30-b718-6f85c83e3875"
  }
}
```

Large collections must use cursor pagination.

---

## Error Response

Errors use one consistent structure:

```json
{
  "error": {
    "code": "incident.invalid_transition",
    "message": "The incident cannot be closed from its current state.",
    "details": {
      "current_status": "investigating"
    }
  },
  "meta": {
    "request_id": "0198f3b6-91c8-7c30-b718-6f85c83e3875"
  }
}
```

Error codes must be stable and machine-readable.

Error messages must not expose:

* Stack traces.
* database details.
* credentials.
* internal service details.
* unauthorized Resource existence.

---

## HTTP Methods

| Method   | Usage                                        |
| -------- | -------------------------------------------- |
| `GET`    | Read Resources                               |
| `POST`   | Create Resources or execute commands         |
| `PATCH`  | Partially update mutable fields              |
| `DELETE` | Remove, revoke, archive, or request deletion |

`PUT` should be used only when complete replacement is explicitly supported.

---

## Command Endpoints

Important lifecycle operations use explicit commands.

Examples:

```text
POST /alerts/{alert_id}/acknowledge
POST /alerts/{alert_id}/resolve
POST /incidents/{incident_id}/resolve
POST /knowledge/{knowledge_id}/publish
POST /agents/{agent_id}/execute
POST /workflows/{workflow_id}/cancel
```

Commands are preferred when an operation has business rules beyond changing a field.

---

## Status Codes

|  Code | Meaning                                  |
| ----: | ---------------------------------------- |
| `200` | Successful request                       |
| `201` | Resource created                         |
| `202` | Accepted for asynchronous processing     |
| `204` | Successful request without response body |
| `400` | Invalid request format                   |
| `401` | Authentication failed                    |
| `403` | Operation not authorized                 |
| `404` | Resource unavailable or not visible      |
| `409` | State or uniqueness conflict             |
| `412` | Expected Version does not match          |
| `422` | Business validation failed               |
| `429` | Rate limit exceeded                      |
| `500` | Unexpected platform failure              |
| `503` | Required dependency unavailable          |

---

## Validation Errors

Field validation errors should identify invalid fields.

Example:

```json
{
  "error": {
    "code": "request.validation_failed",
    "message": "The request contains invalid fields.",
    "details": {
      "fields": {
        "severity": [
          "The value must be one of: critical, high, medium, low."
        ]
      }
    }
  }
}
```

Unknown write fields should be rejected.

---

## Organization Scope

Every tenant request must resolve exactly one Organization.

The route Organization must match the authenticated identity or API Client scope.

Correct:

```text
GET /organizations/{organization_id}/incidents/{incident_id}
```

Every query must use both:

```text
organization_id
resource_id
```

A Resource identifier alone is insufficient.

---

## Authentication and Authorization

Authentication determines the acting identity.

Authorization determines whether the identity may perform the requested operation.

Authorization must evaluate:

* Organization scope.
* permission.
* Resource scope.
* lifecycle state.
* Environment restrictions.
* current Policy.
* approval requirements.

Unauthorized data must not appear in responses, counts, facets, or error details.

---

## Idempotency

Retryable create and command endpoints should accept:

```http
Idempotency-Key: {client_generated_key}
```

The same key with the same request returns the original result.

The same key with a different request returns a conflict.

Idempotency keys are scoped by:

* Organization.
* identity or client.
* operation.

---

## Optimistic Concurrency

Mutable protected Resources use expected-Version checks.

Preferred header:

```http
If-Match: "4"
```

A Version mismatch returns:

```http
412 Precondition Failed
```

Responses for mutable Resources should include their current Version.

Silent last-write-wins behavior is prohibited.

---

## Pagination

Collection endpoints use:

```text
limit
cursor
```

Example:

```http
GET /incidents?limit=50&cursor={cursor}
```

Rules:

* Default and maximum limits are endpoint-specific.
* cursors are opaque.
* ordering must be stable.
* expired or invalid cursors are rejected.

---

## Filtering

Filters use query parameters.

Example:

```http
GET /alerts?status=open&severity=critical
```

Common filters include:

* `status`
* `severity`
* `priority`
* `project_id`
* `application_id`
* `environment_id`
* `created_from`
* `created_to`

Unsupported filters must be rejected.

---

## Sorting

Sorting uses:

```text
sort
```

Examples:

```text
sort=created_at
sort=-created_at
```

A leading `-` means descending order.

Only documented fields may be sorted.

---

## Includes

Related Resources may be requested through:

```text
include
```

Example:

```http
GET /incidents/{incident_id}?include=alerts,owners
```

Includes must be:

* Explicitly supported.
* bounded.
* independently authorized.

Recursive or unlimited expansion is prohibited.

---

## Bulk Requests

Bulk endpoints must define:

* Maximum item count.
* atomic or partial-success behavior.
* per-item errors.
* idempotency behavior.
* authorization behavior.

Each Resource in a bulk request must be authorized separately.

---

## Asynchronous Operations

Long-running operations return:

```http
202 Accepted
```

The response should reference an operation Resource:

```json
{
  "data": {
    "id": "0198f3b6-e27d-772c-aa82-d2784d7a9494",
    "type": "operation",
    "attributes": {
      "status": "queued"
    }
  }
}
```

Possible statuses:

* `queued`
* `running`
* `completed`
* `completed_with_failures`
* `failed`
* `cancelled`

---

## Null and Missing Fields

A missing field means the client did not provide it.

A `null` field means the client explicitly provided no value.

PATCH endpoints must preserve this distinction.

Fields that cannot be cleared must reject `null`.

---

## Boolean Values

Boolean fields use JSON booleans:

```json
{
  "enabled": true
}
```

Values such as `"true"`, `1`, and `"yes"` are not accepted as booleans.

---

## Enumerations

Enumeration values use lowercase snake case.

Examples:

```text
in_progress
pending_approval
partially_delivered
```

Clients must tolerate new response enum values unless the contract explicitly states otherwise.

Requests containing unsupported enum values are rejected.

---

## Monetary Values

Monetary amounts should use:

* Decimal strings or integer minor units.
* explicit currency codes.

Floating-point JSON values should not be used for authoritative financial calculations.

---

## Sensitive Fields

Sensitive fields must not be returned by default.

Examples:

* Credentials.
* tokens.
* secret references.
* private provider payloads.
* restricted evidence.
* internal security metadata.

Responses must apply field-level authorization where required.

---

## Rate Limiting

Rate limits may apply by:

* Organization.
* identity.
* API Client.
* endpoint.
* operation type.

Rate-limit responses use:

```http
429 Too Many Requests
```

The response should include retry guidance where possible.

---

## Versioning and Compatibility

The major API Version appears in the route:

```text
/api/v1
```

Compatible changes may add:

* Optional fields.
* new endpoints.
* new filters.
* new event types.

Breaking changes require a new major API Version.

Published field meanings must not change silently.

---

## Deprecation

Deprecated endpoints should communicate:

* Replacement endpoint.
* migration guidance.
* planned removal date.

Deprecation must not weaken security or authorization.

---

## Observability

Every request must be traceable through:

* Request identifier.
* Correlation identifier.
* acting identity type.
* Organization identifier.
* endpoint.
* status code.
* error code.

Request bodies and sensitive fields must not be logged by default.

---

## Business Rules

* All tenant Resources are accessed within Organization scope.
* Authentication and authorization remain separate.
* Resource identifiers do not grant access.
* JSON fields use snake case.
* timestamps use UTC ISO 8601.
* errors use stable machine-readable codes.
* important lifecycle changes use explicit commands.
* retryable commands use idempotency.
* protected updates use optimistic concurrency.
* large collections use cursor pagination.
* unsupported fields, filters, and sort values are rejected.
* long-running operations return asynchronous operation Resources.
* sensitive information is excluded from errors and telemetry.
* published API contracts remain backward compatible within a major Version.

---

## Related Documents

* `docs/api/00-api-overview.md`
* `docs/api/02-authentication.md`
* `docs/api/03-errors.md`
* `docs/api/04-pagination-filtering.md`
* `docs/api/05-idempotency-concurrency.md`
* `docs/functional/16-public-api.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/architecture/08-security-architecture.md`
