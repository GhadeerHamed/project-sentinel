# Current Task

Implement Batch Event Ingestion — Part 3: HTTP endpoint and end-to-end API integration.

## Goal

Expose the already implemented batch-ingestion application flow through the canonical HTTP API.

Do not change batch domain or persistence semantics unless HTTP integration reveals a concrete defect.

## Authentication

Use existing Event Source bearer authentication.

Do not use:

- human session authentication;
- Membership authorization;
- human TenantContext.

The authenticated Event Source determines all hierarchy/source ownership.

## Route

Expose:

`POST /api/v1/organizations/{organization}/events/batch`

Require:

- `Authorization: Bearer <Event Source credential>`
- `Idempotency-Key`

The route Organization must match the authenticated Event Source Organization.

## Request

Accept a JSON object containing the documented ordered Event collection.

Support:

- minimum 1 Event;
- maximum 100 Events.

Each item uses the same canonical input contract as single-event ingestion.

Do not allow client-controlled:

- Event ID;
- Organization ID;
- Project ID;
- Application ID;
- Environment ID;
- Event Source ID;
- category;
- received_at;
- created_at.

## Validation

Reuse existing single/batch normalization rules.

Validate:

- batch cardinality;
- schema version `1`;
- supported event types;
- severity;
- occurred_at;
- JSON-object payload;
- flat scalar attributes;
- per-Event 64 KiB limit;
- optional external_event_id;
- optional correlation/causation/trace identifiers.

Do not duplicate domain validation in the controller.

## Application Integration

Invoke the existing `IngestEventBatchHandler`.

Preserve existing semantics:

- atomic batch behavior;
- batch-level idempotency;
- ordered replay result payload;
- equivalent external-ID reuse;
- conflicting external-ID rejection;
- immutable persistence;
- one `event.accepted` Outbox record per newly created Event.

Do not implement persistence logic inside HTTP classes.

## Response

Return:

`202 Accepted`

Return the canonical documented batch response.

Requirements:

- preserve original input order;
- one logical Event representation/result per input item;
- repeated logical Event IDs are allowed when equivalent duplicates occur;
- idempotent replay returns the same logical ordered result;
- response must not imply downstream processing completed.

Do not expose:

- credentials;
- hashes;
- idempotency internals;
- normalized duplicate hashes;
- Outbox internals.

## Error Behavior

Use existing canonical API exception rendering.

Verify appropriate behavior for:

- unauthenticated/invalid bearer credential;
- revoked credential;
- inactive Event Source;
- route Organization mismatch;
- empty batch;
- >100 Events;
- invalid Event item;
- unsupported schema/type;
- oversized Event;
- idempotency mismatch;
- conflicting external_event_id.

Do not expose raw PostgreSQL exceptions.

## Controller

Keep the controller thin.

Use dedicated:

- request validation;
- application command mapping;
- API resource/presenter.

Reuse existing Event resource mapping where genuinely compatible.

## Tests

Cover at minimum:

- successful one-item batch;
- successful multi-item batch;
- 100-item request;
- empty batch rejected;
- 101-item batch rejected;
- bearer authentication required;
- revoked credential rejected;
- route Organization mismatch rejected;
- canonical validation failures;
- `202 Accepted`;
- response preserves input order;
- equivalent repeated external IDs map to the same Event ID;
- idempotency replay returns the same ordered logical result;
- replay creates no duplicate Events;
- replay creates no duplicate Outbox records;
- idempotency mismatch conflicts;
- conflicting external ID returns canonical conflict;
- one invalid item causes no partial writes;
- canonical response exposes no internal/security fields;
- existing single-event ingestion endpoint remains green.

## Concurrency Verification

Do not attempt to build a new concurrency test harness in this task.

The existing deterministic multi-process PostgreSQL concurrency verification gap remains explicitly tracked in `STATUS.md`.

Do not mark Milestone 5 complete.

## Excluded

Do not add:

- new batch persistence semantics;
- partial success;
- streaming ingestion;
- concurrency harness;
- Outbox dispatcher;
- Signals;
- Alerts.

## Verification

Run focused API tests and:

`docker compose exec app composer quality`

After success:

- mark Part 3 complete;
- keep deterministic batch-concurrency verification open;
- set Milestone 5 final review as next only after explicitly accounting for that verification gap.

Do not modify `NEXT.md`.

## Reporting

Return only:

- endpoint
- authentication
- request validation
- handler integration
- response behavior
- idempotency replay
- duplicate behavior
- atomic failure behavior
- files changed
- tests
- remaining verification gap
- blockers

Maximum 15 lines.