# Current Task

Review and reconcile the canonical Event Ingestion contract before implementation.

## Required

This is a documentation and architecture review only.

Do not modify application code, migrations, tests, `STATUS.md`, or `NEXT.md`.

Review only documentation directly relevant to:

- Event domain/module;
- Event functional requirements;
- Event API;
- Event database schema;
- canonical event model;
- event-driven architecture;
- tenant isolation;
- idempotency and duplicate handling;
- Event → Signal processing boundary;
- Milestone 5 / MVP Event Ingestion scope.

## Canonical Event Model

Determine the exact canonical Event representation.

Compare documented fields including where present:

- Event ID;
- Organization ID;
- Project ID;
- Application ID;
- Environment ID;
- source identity;
- source event ID / external ID;
- event type;
- name;
- category;
- severity;
- occurred-at timestamp;
- received/ingested-at timestamp;
- payload;
- metadata;
- tags;
- schema version;
- correlation ID;
- causation ID;
- trace/request identifiers;
- status or processing state;
- created timestamp.

For every conflicting or optional field:

- identify the relevant sources;
- determine whether it is required for MVP ingestion;
- recommend the canonical decision.

Do not invent fields.

## Ownership Hierarchy

Confirm how Events are scoped to:

Organization → Project → Application → Environment

Determine:

- which hierarchy IDs are persisted directly on Event;
- whether parent ownership is validated at ingestion;
- whether archived Project/Application/Environment resources may accept new Events;
- how suspended Organizations behave;
- whether the Event stores hierarchy snapshots or only resource IDs.

## Source Identity

Determine the canonical source model.

Review whether an Event may originate from:

- authenticated human User;
- Agent;
- Plugin;
- API client;
- application integration;
- internal platform process.

Determine:

- what identifies the source;
- what is persisted as source attribution;
- whether source identity differs from acting identity;
- which source types are required for the first MVP ingestion slice.

Do not implement future Plugin or Agent authentication merely because they are valid future sources.

## Event Identity and Duplicate Safety

Distinguish clearly between:

- Sentinel Event ID;
- source/external Event ID;
- HTTP `Idempotency-Key`;
- duplicate-event detection.

Determine:

- whether Sentinel Event IDs are server-generated UUIDv7;
- whether source event IDs are required or optional;
- canonical duplicate scope;
- duplicate behavior;
- whether exact duplicate ingestion replays the original result or is rejected;
- how concurrent duplicates are serialized.

Do not conflate request idempotency with source-event deduplication unless canonical documentation explicitly does so.

## Time Semantics

Determine canonical meanings for:

- `occurred_at`;
- `ingested_at` / `received_at`;
- database creation timestamp.

Clarify:

- which timestamp is supplied by the producer;
- which timestamps are server-owned;
- UTC normalization;
- future timestamps;
- excessively old timestamps;
- whether clock-skew validation belongs in MVP.

## Payload and Schema

Determine:

- canonical payload representation;
- whether payload must be JSON object;
- payload size limits if documented;
- metadata shape;
- schema-version behavior;
- whether event-type-specific validation exists in MVP;
- whether unknown event types are accepted.

Do not introduce a schema registry unless required by the canonical MVP.

## Immutability

Confirm whether accepted Events are immutable.

Determine:

- whether Event update/delete APIs exist in MVP;
- whether processing state belongs on the Event row or in derived records;
- whether downstream processing may mutate canonical Event data.

Prefer append-only evidence semantics where supported by canonical ADRs.

## Ingestion API

Determine the canonical first vertical slice.

At minimum resolve:

- authorization/authentication model;
- HTTP route;
- request fields;
- server-owned fields;
- hierarchy validation;
- source attribution;
- idempotency requirements;
- duplicate-event handling;
- success status;
- response representation;
- validation/error semantics.

Do not implement batch ingestion unless explicitly required for the first MVP slice.

## Persistence

Determine the minimum PostgreSQL Event schema required for the first slice.

Review:

- UUID type;
- hierarchy foreign keys;
- source identity columns;
- source-event identity;
- payload storage type;
- metadata storage;
- indexes;
- deduplication constraint;
- occurred/ingested timestamp indexes;
- tenant-scoped query requirements.

Do not design retention/partitioning beyond what Milestone 5 explicitly requires.

## Event Publication

Determine the canonical post-acceptance publication contract.

Resolve:

- canonical Domain Event / Outbox event name;
- whether it is `event.accepted`, `event.ingested`, or another documented name;
- payload carried to downstream processing;
- transaction boundary;
- whether publication occurs only after durable Event persistence.

The accepted Event and its Outbox publication must not diverge transactionally.

## Event → Signal Boundary

Confirm what ingestion owns versus what later processing owns.

Ingestion should not prematurely implement:

- signal extraction;
- alert evaluation;
- incident creation;
- AI analysis;
- notification dispatch.

Determine the exact handoff point.

## First Vertical Slice

Recommend the smallest end-to-end ingestion slice.

Prefer:

- one authenticated Event;
- one active Organization → Project → Application → Environment hierarchy;
- durable immutable persistence;
- source attribution;
- duplicate safety;
- transactional Outbox publication.

Do not expand into batch ingestion or processing workers unless required.

## Decision Precedence

When documents conflict, use:

1. ADR / explicit architectural decision.
2. Module/domain contract.
3. Architecture/canonical Event model.
4. API contract.
5. Database documentation.
6. Functional/roadmap examples.

State material conflicts explicitly.

## Reporting

Return only:

- canonical Event field set
- hierarchy and lifecycle rules
- source-identity model
- Event ID / source ID / idempotency / deduplication rules
- time semantics
- payload/schema rules
- immutability decision
- ingestion API contract
- persistence requirements
- publication contract
- Event → Signal boundary
- documentation corrections required
- recommended first vertical slice
- blockers

Maximum 30 lines.