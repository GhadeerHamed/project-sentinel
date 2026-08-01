# Events API

## Purpose

This document defines the API for ingesting and reading canonical Events.

Events are immutable operational observations received from Applications, Plugins, SDKs, or external systems.

---

## Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "event",
  "attributes": {
    "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
    "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
    "event_type": "application.request_failed",
    "severity": "high",
    "title": "Checkout request failed",
    "occurred_at": "2026-08-01T16:30:00Z",
    "recorded_at": "2026-08-01T16:30:02Z"
  }
}
```

Events are append-only and cannot be updated through the API.

---

## Endpoints

| Method | Endpoint                                             | Purpose                |
| ------ | ---------------------------------------------------- | ---------------------- |
| `POST` | `/organizations/{organization_id}/events`            | Ingest one Event       |
| `POST` | `/organizations/{organization_id}/events/batch`      | Ingest multiple Events |
| `GET`  | `/organizations/{organization_id}/events`            | List Events            |
| `GET`  | `/organizations/{organization_id}/events/{event_id}` | Read one Event         |

---

## Ingest Event

```http
POST /api/v1/organizations/{organization_id}/events
Idempotency-Key: {key}
```

Request:

```json
{
  "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
  "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
  "event_type": "application.request_failed",
  "severity": "high",
  "title": "Checkout request failed",
  "occurred_at": "2026-08-01T16:30:00Z",
  "source": {
    "system": "checkout-api",
    "external_id": "evt-98421"
  },
  "attributes": {
    "route": "/checkout",
    "status_code": 500
  }
}
```

Response:

```http
202 Accepted
```

Accepted ingestion means the Event was durably received.

Signal detection, Alert creation, Search indexing, and Agent analysis may occur asynchronously.

---

## Required Fields

Required fields:

* `application_id`
* `environment_id`
* `event_type`
* `occurred_at`

Recommended fields:

* `severity`
* `title`
* source identifier
* correlation identifier
* safe structured attributes

The Application and Environment must belong to the Organization in the route.

---

## Event Types

Event types use stable dot-separated names.

Examples:

```text
application.request_failed
application.deployment_completed
database.connection_exhausted
queue.processing_delayed
security.authentication_failed
```

Event type names describe observed facts, not requested Actions.

---

## Batch Ingestion

```http
POST /api/v1/organizations/{organization_id}/events/batch
Idempotency-Key: {key}
```

Request:

```json
{
  "events": [
    {
      "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
      "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
      "event_type": "application.request_failed",
      "occurred_at": "2026-08-01T16:30:00Z"
    }
  ]
}
```

The endpoint must define and enforce a maximum batch size.

Partial failures should return an outcome for each item.

---

## Duplicate Detection

Duplicate protection may use:

* `Idempotency-Key`.
* source system.
* external Event identifier.
* Organization scope.

A duplicate Event must not create duplicate downstream effects.

Duplicate ingestion may return the original Event reference or an explicit duplicate outcome.

---

## List Events

```http
GET /api/v1/organizations/{organization_id}/events
```

Supported filters:

* `project_id`
* `application_id`
* `environment_id`
* `event_type`
* `severity`
* `correlation_id`
* `occurred_from`
* `occurred_to`

Default ordering:

```text
occurred_at DESC, id DESC
```

Cursor pagination is required.

---

## Read Event

```http
GET /api/v1/organizations/{organization_id}/events/{event_id}
```

The response may include authorized references to:

* related Signal.
* related Alert.
* related Incident.
* source Plugin.
* correlation chain.

Raw or sensitive attributes must be filtered according to Policy.

---

## Timestamp Rules

`occurred_at` represents when the source says the Event happened.

`recorded_at` represents when Sentinel accepted it.

Sentinel may reject or flag Events that are:

* Too far in the future.
* older than the allowed ingestion window.
* missing a valid timezone.
* inconsistent with source Policy.

---

## Payload Rules

Event attributes must be:

* JSON-compatible.
* size-limited.
* schema-validated where a schema exists.
* free of credentials and secrets.
* treated as untrusted input.

Large files or evidence should use dedicated storage and references.

---

## Authentication

Event sources should use scoped non-human credentials.

Typical permissions:

```text
event.ingest
event.read
```

An ingestion credential should be restricted to approved:

* Organization.
* Project.
* Application.
* Environment.
* Event Types.

---

## Rate Limits

Event ingestion limits may apply by:

* Organization.
* credential.
* Application.
* Environment.
* subscription plan.

Rate limiting must protect shared capacity without mixing tenant traffic.

---

## Errors

Common errors:

```text
event.invalid_schema
event.payload_too_large
event.timestamp_invalid
event.duplicate
application.not_found
environment.not_found
environment.suspended
authentication.invalid_credential
authorization.denied
rate_limit.exceeded
```

---

## Business Rules

* Events are immutable.
* Every Event belongs to one Organization.
* Application and Environment ownership must be validated.
* Event ingestion is idempotent.
* Event attributes are untrusted input.
* Secrets must not be accepted in Event payloads.
* Accepted ingestion may be processed asynchronously.
* Duplicate Events must not create duplicate downstream effects.
* Event reads remain authorization-filtered.
* Cross-Organization ingestion and access are prohibited.

---

## Related Documents

* `docs/api/01-api-conventions.md`
* `docs/api/05-idempotency-concurrency.md`
* `docs/api/08-applications-api.md`
* `docs/api/09-environments-api.md`
* `docs/database/05-event-signal-schema.md`
* `docs/architecture/05-event-model.md`
