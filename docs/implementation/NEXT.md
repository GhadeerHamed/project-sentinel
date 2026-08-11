# Current Task

Implement the Get Event vertical slice.

## Required

Follow the reconciled Event module, Events API, Event schema, security, and Milestone 5 documentation.

This is a read-only human API.

Do not modify Event ingestion behavior.

## Permission

Use:

`event.read`

Requirements:

- reuse the existing Core permission;
- authorize through an operation-specific Get Event Policy;
- reuse `OrganizationPermissionAuthorizer`.

Do not introduce another Event read permission.

## Authentication

Require:

- human browser/session authentication;
- resolved `TenantContext`;
- `event.read`.

Do not allow Event Source bearer authentication to use this endpoint.

## Route

Expose:

`GET /api/v1/organizations/{organization}/events/{event}`

The route Organization must match `TenantContext`.

Do not require Project/Application/Environment route nesting for direct Event retrieval.

Keep the controller thin.

## Tenant Scope

Resolve the Event directly using:

- `organization_id`;
- Event ID.

Never perform a global Event lookup followed by tenant validation.

A valid Event ID belonging to another Organization must return tenant-safe not found.

## Historical Visibility

Accepted Events are immutable historical evidence.

Therefore an authorized Event remains readable when:

- its Project is archived;
- its Application is archived;
- its Environment is archived;
- its Event Source credential has been revoked.

Do not require current parent resources to remain active for historical retrieval.

The Organization itself must still be accessible through normal `TenantContext` rules.

## Read Model

Do not hydrate the Event aggregate.

Reuse existing Event read infrastructure where appropriate.

Implement only the smallest framework-independent detail capability required:

- Event detail DTO if the list DTO is insufficient;
- tenant-scoped query contract;
- PostgreSQL projection.

Select only fields required by the canonical detail API.

Keep aggregate persistence mapping separate from read projection mapping.

## Public Detail Representation

Return the documented immutable Event fields, including where canonical:

- `id`
- `project_id`
- `application_id`
- `environment_id`
- `event_source_id`
- `event_type`
- `category`
- `schema_version`
- `severity`
- `external_event_id`
- `occurred_at`
- `received_at`
- `created_at`
- `correlation_id`
- `causation_id`
- `trace_id`
- `payload`
- `attributes`

Do not expose:

- bearer credentials;
- credential IDs unless explicitly part of the Event contract;
- credential hashes;
- idempotency records;
- normalized-event hashes used internally for duplicate detection;
- Outbox internals.

## Payload and Attributes

The reconciled Get Event contract allows detail payload and attributes using Policy-filtered visibility.

Implement the documented Policy boundary explicitly.

Do not simply expose raw persistence columns from the controller.

The application/presentation layer must control which Event detail fields are returned.

If the current MVP Policy permits full payload/attributes to any actor with `event.read`, represent that rule explicitly rather than bypassing the Policy abstraction.

Do not mutate or transform the stored canonical Event evidence.

## Immutability

Get Event is read-only.

Do not add:

- ETag mutation semantics;
- Event update;
- Event delete;
- processing-state mutation.

The retrieved Event represents the originally accepted evidence.

## Security

Verify:

- unauthenticated human rejected;
- unauthorized member rejected;
- Event Source bearer auth rejected;
- cross-tenant Event ID returns not found;
- archived parents do not hide the Event;
- revoked Event Source credentials do not hide historical Events;
- payload/attributes follow the documented visibility Policy;
- credential or idempotency internals never appear.

## Tests

Cover at minimum:

- authorized Event retrieval;
- `event.read` authorization;
- unauthenticated request;
- unauthorized member;
- Event Source bearer auth rejected;
- missing Event;
- cross-Organization Event returns tenant-safe not found;
- canonical Event public fields;
- payload detail visibility;
- attributes detail visibility;
- internal duplicate hash is not exposed;
- idempotency data is not exposed;
- Outbox data is not exposed;
- credential data is not exposed;
- archived Project historical retrieval;
- archived Application historical retrieval;
- archived Environment historical retrieval;
- Event remains visible after source credential revocation;
- read path does not hydrate the Event aggregate.

## Excluded

Do not add:

- Event update/delete.
- Event timeline.
- additional Event filters.
- batch ingestion.
- Event Source management.
- Signal processing.
- Alert evaluation.
- Outbox dispatcher.
- generic Search integration.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- endpoint
- tenant lookup behavior
- historical visibility
- read-model approach
- payload/attributes visibility
- response behavior
- files changed
- tests
- blockers

Maximum 12 lines.