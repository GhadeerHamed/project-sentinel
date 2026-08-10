# Events API

## Purpose

This document defines the API for issuing MVP Event Sources and ingesting and reading canonical Events.

Events are immutable operational observations submitted by authenticated `api_client` Event Sources. Plugin, Agent, SDK, webhook, and other producer integrations are deferred.

---

## Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "event",
  "attributes": {
    "project_id": "0198f3b5-e2f0-7831-9a33-fb486fa32684",
    "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
    "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
    "event_source_id": "0198f3b5-f38b-777f-a68c-68e0cfe06e87",
    "external_event_id": "evt-98421",
    "event_type": "application.error",
    "category": "error",
    "severity": "high",
    "schema_version": "1",
    "occurred_at": "2026-08-01T16:30:00Z",
    "received_at": "2026-08-01T16:30:02Z"
  }
}
```

Events are append-only and cannot be updated or deleted through the API. The `202 Accepted` ingestion response returns this resource without payload or processing state; it confirms durable acceptance, not downstream processing completion.

---

## Endpoints

| Method | Endpoint                                             | Purpose                |
| ------ | ---------------------------------------------------- | ---------------------- |
| `POST` | `/organizations/{organization}/projects/{project}/applications/{application}/environments/{environment}/event-sources` | Issue an Event Source |
| `POST` | `/organizations/{organization}/events`               | Ingest one Event       |
| `GET`  | `/organizations/{organization}/events`               | List Events            |
| `GET`  | `/organizations/{organization}/events/{event}`       | Read one Event         |

Batch ingestion is a later Milestone 5 capability and has no current API contract.

---

## Issue Event Source

```http
POST /api/v1/organizations/{organization}/projects/{project}/applications/{application}/environments/{environment}/event-sources
Idempotency-Key: {key}
```

The caller is a session-authenticated Human User with an active Organization Membership, resolved Tenant Context, and effective `event_source.create` permission. Authorization is permission-based, not Role-name-based; the built-in `organization_owner` Role receives that Core permission. The route parents must form one active Organization → Project → Application → Environment hierarchy. Invalid or cross-Organization parent identifiers return the tenant-safe `404` response.

Request:

```json
{
  "name": "checkout-api-production"
}
```

`name` is required and human-readable. The server owns the Event Source UUIDv7 identifier, Organization, Project, Application, and Environment ownership, `api_client` source type, active status, timestamps, version, credential identifier, and credential secret. The request must reject all other write fields and does not use `If-Match`.

Response:

```http
201 Created
```

```json
{
  "data": {
    "id": "0198f3b5-f38b-777f-a68c-68e0cfe06e87",
    "type": "event_source",
    "attributes": {
      "project_id": "0198f3b5-e2f0-7831-9a33-fb486fa32684",
      "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
      "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
      "source_type": "api_client",
      "name": "checkout-api-production",
      "status": "active",
      "created_at": "2026-08-01T16:30:00Z",
      "updated_at": "2026-08-01T16:30:00Z",
      "version": 1
    }
  },
  "meta": {
    "request_id": "0198f3b5-f634-7a8b-81c8-4dfb7e951c5f",
    "issued_credential": {
      "id": "0198f3b5-f4d9-708f-bbc3-0f7a9e7f5f21",
      "bearer_token": "esc_0198f3b5-f4d9-708f-bbc3-0f7a9e7f5f21.2b7cc44b11f5c86c8b5b05c564eb683d61b43a6ca5ed24c4334f9d6a91c10ef2",
      "shown_once": true
    }
  }
}
```

`issued_credential` is not part of the reusable Event Source resource and is present only in the initial successful response. Its bearer token must be copied immediately; it cannot later be retrieved. The server stores only its hash and never includes the secret in Logs, Audit records, domain events, or idempotency records.

Idempotency is required and scoped by Organization, acting Human identity, operation `event_source.create`, key, and normalized request hash. A matching retry returns the original public Event Source but omits `issued_credential`; reuse with changed input returns `409 Conflict` and `idempotency.key_conflict`. Creation atomically persists the Event Source, its one credential, the idempotency result, Audit action `event_source.create`, and companion Audit action `event_source.credential.issue`; neither Audit action contains the secret. No Event Source list, get, update, credential retrieval, rotation, revocation, or deletion route is an MVP endpoint.

---

## Ingest Event

```http
POST /api/v1/organizations/{organization}/events
Authorization: Bearer esc_{credential_id}.{secret}
Idempotency-Key: {key}
```

Request:

```json
{
  "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
  "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
  "external_event_id": "evt-98421",
  "event_type": "application.error",
  "schema_version": "1",
  "severity": "high",
  "occurred_at": "2026-08-01T16:30:00Z",
  "payload": {
    "message": "Checkout request failed",
    "status_code": 500
  },
  "attributes": {
    "route": "/checkout"
  },
  "correlation_id": "0198f3b6-454b-73d4-b5ad-e1de1fa6cc86",
  "causation_id": "0198f3b6-45a6-798d-817e-4a943dbd782e",
  "trace_id": "4bf92f3577b34da6a3ce929d0e0e4736"
}
```

Response:

```http
202 Accepted
```

```json
{
  "data": {
    "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
    "type": "event",
    "attributes": {
      "project_id": "0198f3b5-e2f0-7831-9a33-fb486fa32684",
      "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
      "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
      "event_source_id": "0198f3b5-f38b-777f-a68c-68e0cfe06e87",
      "external_event_id": "evt-98421",
      "event_type": "application.error",
      "category": "error",
      "severity": "high",
      "schema_version": "1",
      "occurred_at": "2026-08-01T16:30:00Z",
      "received_at": "2026-08-01T16:30:02Z"
    }
  }
}
```

Accepted ingestion means the Event was durably persisted and `event.accepted` was committed to the Outbox. Signal detection, Alert creation, Search indexing, and Agent analysis may occur asynchronously and are not implied by this response. An idempotent replay or matching source duplicate returns the same logical Event resource with `202 Accepted`.

Source identity is derived from the bearer credential; the request must not contain source identity fields.

---

## Required Fields

Required fields:

* `application_id`
* `environment_id`
* `event_type`
* `schema_version`
* `occurred_at`
* `payload`

Optional fields:

* `severity`
* `external_event_id`
* `attributes`
* `correlation_id`
* `causation_id`
* `trace_id`

The Application and Environment must belong to the Organization in the route, to the same active Project, and to the authenticated Event Source scope. The server derives and persists the Project identifier; clients do not provide it.

---

## Event Types

Event types must already use lowercase dot-separated canonical names and must be one of the initial catalog; the MVP does not translate provider aliases:

```text
application.error
application.health_failed
application.health_recovered
deployment.completed
custom.operational_event
```

The server derives the canonical category: `application.error` maps to `error`; `application.health_failed` and `application.health_recovered` map to `availability`; `deployment.completed` maps to `deployment`; and `custom.operational_event` maps to `platform`. Unknown Event types are rejected with `event.unsupported_type`. Provider-specific type translation and a broader catalog are deferred.

`schema_version` versions the canonical Sentinel Event envelope, not an event-type-specific payload schema. The MVP accepts only the string `"1"`; other values are rejected with `event.unsupported_schema_version`. A schema registry is deferred.

When supplied, severity must be one of `critical`, `high`, `medium`, `low`, `informational`, or `unknown`.

---

## Later Milestone 5 Batch Ingestion

Batch ingestion is deferred beyond the first Event ingestion slice.

---

## Duplicate Detection

`Idempotency-Key` is required and scoped by Organization, authenticated Event Source, operation `event.ingest`, key, and normalized request hash. Reuse with the same normalized request returns the original Event; reuse with different input returns `409 Conflict` with `idempotency.key_conflict`.

`external_event_id` is optional and is independent of the idempotency key. When supplied, it is unique for `(organization_id, event_source_id, external_event_id)`. A matching normalized source duplicate returns the original Event with `202 Accepted`; a changed request using the same source external identifier returns `409 Conflict` with `event.external_id_conflict`. Concurrent matching requests create only one Event.

---

## List Events

```http
GET /api/v1/organizations/{organization}/events
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
GET /api/v1/organizations/{organization}/events/{event}
```

The response contains the Event resource shown above. The first slice does not expose Signal, Alert, Incident, Plugin, or relationship references. Raw or sensitive attributes must be filtered according to Policy.

---

## Timestamp Rules

`occurred_at` represents when the source says the Event happened.

`received_at` represents when Sentinel accepted it.

All timestamps are normalized to UTC. The MVP rejects invalid or timezone-less timestamps but does not impose future- or age-skew thresholds.

---

## Payload Rules

`payload` is required and must be a JSON object encoded in at most 64 KiB. The root may not be an array or scalar. Nested objects and arrays are allowed within that byte limit, and an empty object is valid.

Optional `attributes` must be a flat JSON object with scalar values; their encoded bytes count toward the same 64 KiB limit. Sentinel normalizes accepted attributes for later searchable use. There is no separate `metadata` or `tags` request field in the MVP; provider-specific extension data belongs in `payload`.

Payload and attributes must be:

* JSON-compatible.
* free of credentials and secrets.
* treated as untrusted input.

Large files or evidence should use dedicated storage and references.

---

## Authentication

The first Event Source type is an active `api_client` with one active opaque bearer credential. The credential format is `esc_{credential_id}.{secret}`; its secret is shown once and stored only as a one-way hash. Revoked, disabled, expired, or replaced credentials fail authentication immediately. Multiple active credentials, credential rotation overlap, human ingestion, Plugins, and Agents are deferred.

Typical permissions:

```text
event.ingest
```

The first credential is restricted to:

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
event.unsupported_type
event.unsupported_schema_version
event.external_id_conflict
idempotency.key_conflict
application.not_found
environment.not_found
organization.suspended
project.archived
application.archived
environment.archived
event_source.inactive
authentication.invalid_credential
authorization.denied
rate_limit.exceeded
```

---

## Business Rules

* Events are immutable.
* Every accepted Event belongs to one active Organization, Project, Application, and Environment.
* Event ingestion is idempotent.
* Source duplicate detection is separate from request idempotency.
* Event attributes are untrusted input.
* Secrets must not be accepted in Event payloads.
* Accepted ingestion commits `event.accepted` transactionally and may be processed asynchronously.
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
