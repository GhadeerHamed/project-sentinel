# Current Task

Implement the Single Event Ingestion vertical slice.

## Required

Follow the reconciled Event module, API, database, functional, and security documentation.

This is the first production Event ingestion path.

Reuse:

- Event Source bearer authentication;
- `EventSourceIdentity`;
- active hierarchy validation;
- shared idempotency foundation;
- UUIDv7 generation;
- Clock;
- TransactionManager;
- Outbox infrastructure.

Do not implement downstream Event processing.

## Authentication

Use Event Source bearer authentication.

Do not use:

- human browser/session authentication;
- Membership authorization;
- human `TenantContext`.

The authenticated Event Source determines:

- `event_source_id`;
- Organization ownership;
- Project/Application/Environment scope.

The client must never submit or override source identity.

## Route

Expose:

`POST /api/v1/organizations/{organization}/events`

Require:

`Authorization: Bearer <event-source-credential>`

Require:

`Idempotency-Key`

The route Organization must match the authenticated Event Source Organization.

Cross-Organization use must fail safely.

## Active Hierarchy

Before accepting an Event, validate the source's complete hierarchy:

Organization
→ Project
→ Application
→ Environment

All must be active.

Reject ingestion when:

- Organization is suspended or archived;
- Project is archived;
- Application is archived;
- Environment is archived;
- persisted source hierarchy is inconsistent.

Do not trust request-supplied parent relationships.

## Canonical Event

Create an immutable framework-independent Event model.

Required persisted fields:

- `id`
- `organization_id`
- `project_id`
- `application_id`
- `environment_id`
- `event_source_id`
- `event_type`
- `category`
- `schema_version`
- `occurred_at`
- `received_at`
- `payload`
- `created_at`

Optional documented fields:

- `severity`
- `external_event_id`
- `correlation_id`
- `causation_id`
- `trace_id`
- normalized `attributes`

Do not add undocumented fields.

Sentinel Event ID:

- server-generated UUIDv7.

Accepted Event data is immutable.

No update/delete lifecycle.

## Request Contract

Accept only fields defined by the reconciled Events API.

At minimum:

- `event_type`
- `schema_version`
- `occurred_at`
- `payload`

Accept optional documented fields where supported:

- `external_event_id`
- `severity`
- `attributes`
- `correlation_id`
- `causation_id`
- `trace_id`

Do not accept client-controlled:

- Event ID;
- Organization ID;
- Project ID;
- Application ID;
- Environment ID;
- Event Source ID;
- category;
- received timestamp;
- created timestamp;
- processing state.

Hierarchy and category are server-derived.

## Schema Version

Support exactly:

`1`

Reject unsupported schema versions.

Do not implement schema registry behavior.

## Event Type

Use the documented fixed lowercase Event-type catalog.

Requirements:

- reject unknown Event types;
- normalize according to the canonical contract;
- derive canonical `category` server-side;
- do not accept client-controlled category.

Do not expand the Event taxonomy.

## Payload

Require `payload` to be a JSON object.

Rules:

- nested objects are allowed;
- arrays may appear inside the object where JSON permits;
- empty object behavior follows canonical docs;
- payload plus normalized attributes must respect the documented 64 KiB limit;
- reject oversized requests using canonical validation semantics.

Do not add metadata or tags.

## Time Semantics

Producer provides:

`occurred_at`

Server assigns:

`received_at`

and:

`created_at`

Normalize timestamps to UTC.

Do not invent age or future-skew thresholds.

## Idempotency

Require:

`Idempotency-Key`

Scope:

- Organization;
- authenticated Event Source;
- operation `event.ingest`;
- key;
- normalized request hash.

Behavior:

- first request creates one Event;
- same key + same normalized request returns the same accepted Event;
- same key + changed request returns conflict;
- concurrent identical requests create exactly one Event.

Reuse the shared idempotency implementation.

Do not conflate this mechanism with source duplicate detection.

## Source Duplicate Detection

When `external_event_id` is supplied, enforce uniqueness across:

`organization_id`
+ `event_source_id`
+ `external_event_id`

This is independent from `Idempotency-Key`.

Rules:

- the same external Event must never create a second Sentinel Event;
- concurrent duplicate submissions must remain safe;
- use the documented normalized-event hash to distinguish equivalent duplicate submissions from conflicting reuse of the same external Event ID;
- follow the reconciled API conflict/replay semantics.

Do not rely only on application-level pre-checks; persistence must enforce duplicate safety.

## Persistence

Implement PostgreSQL Event persistence according to the reconciled schema.

Requirements:

- UUID columns for identifiers;
- immutable Event row;
- persisted full accepted hierarchy;
- Event Source foreign ownership;
- JSONB payload;
- optional attributes in canonical representation;
- canonical timestamps;
- source duplicate uniqueness;
- appropriate tenant/time indexes.

Do not implement:

- partitions;
- retention jobs;
- Signal tables;
- mutable Event status.

## Transaction Boundary

The following must be atomic:

1. idempotency acquisition/completion;
2. duplicate validation;
3. Event persistence;
4. `event.accepted` Outbox persistence.

A failure in any step must leave no partially accepted Event.

## Publication

Emit exactly:

`event.accepted`

Do not emit:

`event.ingested`

The Outbox message must reference the accepted Event and contain only the documented downstream handoff data.

The Outbox record and Event must commit in the same transaction.

Do not execute downstream processing synchronously.

## Response

Return:

`202 Accepted`

The response represents a durably accepted Event.

Return the canonical public Event representation.

The response must not imply that:

- Signals were derived;
- Alerts were evaluated;
- Incidents were created;
- downstream processing completed.

Matching idempotency replay returns the same logical Event.

## Security

Verify:

- valid active Event Source can ingest;
- unknown bearer credential fails;
- revoked/inactive credential fails;
- inactive Event Source fails;
- route Organization mismatch fails;
- archived parent hierarchy rejects ingestion;
- source identity cannot be spoofed;
- category cannot be spoofed;
- server-owned fields cannot be overridden;
- cross-tenant Event creation is impossible.

Do not expose credential details anywhere in Event responses or Outbox payloads.

## Tests

Cover at minimum:

- valid Event ingestion;
- `202 Accepted`;
- canonical UUIDv7 Event ID;
- Event Source attribution;
- hierarchy IDs are derived from source;
- active hierarchy accepted;
- archived Project rejected;
- archived Application rejected;
- archived Environment rejected;
- suspended/archived Organization rejected;
- route Organization mismatch rejected;
- valid schema version `1`;
- unsupported schema version rejected;
- every supported Event type maps to canonical category;
- unknown Event type rejected;
- JSON-object payload accepted;
- invalid non-object payload rejected;
- 64 KiB boundary;
- oversized payload rejected;
- producer `occurred_at` retained in UTC;
- server-owned `received_at`;
- server-owned `created_at`;
- optional severity behavior;
- optional correlation/causation/trace behavior;
- source identity cannot be supplied;
- hierarchy IDs cannot be supplied;
- category cannot be supplied;
- first idempotent request creates Event;
- matching idempotency replay returns same Event;
- mismatched idempotency request conflicts;
- concurrent identical idempotent requests create one Event;
- external Event ID creates one Event;
- repeated equivalent external Event submission does not create a second Event;
- conflicting reuse of external Event ID follows canonical conflict semantics;
- concurrent external-ID duplicates create one Event;
- `event.accepted` Outbox record exists;
- no `event.ingested` Outbox record exists;
- failed persistence produces neither Event nor Outbox;
- Event row remains immutable.

## Excluded

Do not add:

- batch ingestion;
- Event update/delete;
- Event listing;
- Event get endpoint unless explicitly required by this slice;
- Signal processing;
- Alert evaluation;
- Incident creation;
- notification dispatch;
- AI analysis;
- Plugin ingestion;
- Agent ingestion;
- retention/partitioning.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- authentication model
- endpoint
- canonical Event model
- hierarchy behavior
- schema/type normalization
- payload/time behavior
- idempotency behavior
- source duplicate behavior
- persistence
- publication
- response
- tests
- blockers

Maximum 18 lines.