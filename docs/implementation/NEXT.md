# Current Task

Implement the List Events vertical slice.

## Required

Follow the reconciled Event module, Events API, Event schema, security, and Milestone 5 documentation.

This is a read-only human API.

Do not modify Event ingestion behavior.

## Permission

Use:

`event.read`

Requirements:

- add it to `CorePermissions` if not already present;
- ensure new `organization_owner` Roles receive it;
- backfill existing `organization_owner` Roles;
- authorize through an operation-specific List Events Policy;
- reuse `OrganizationPermissionAuthorizer`.

Tenant membership alone is insufficient.

Do not authorize by Role name.

## Authentication

Require:

- human browser/session authentication;
- resolved `TenantContext`;
- `event.read`.

Do not use Event Source bearer authentication for Event browsing.

## Route

Expose:

`GET /api/v1/organizations/{organization}/events`

The route Organization must match `TenantContext`.

Keep the controller thin.

## Read Model

Events are immutable and this operation is read-only.

Do not hydrate Event aggregates.

Implement the minimum framework-independent:

- Event list DTO;
- query/read contract;
- filter input;
- paginated result where required.

Use a PostgreSQL projection selecting only public API fields.

Avoid N+1 queries.

Keep persistence aggregate mapping separate from read projection mapping.

## Public Representation

Return only canonical documented Event fields.

Include where documented:

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
- correlation/causation/trace identifiers where public

Follow the reconciled API contract for payload/attributes visibility in list responses.

Do not expose:

- Event Source credential data;
- credential identifiers unless explicitly part of the public Event contract;
- secret hashes;
- internal idempotency records;
- Outbox internals.

## Tenant Scope

Every query must be scoped by:

`organization_id`

Never perform an unscoped Event query followed by tenant filtering.

Hierarchy filters must also be tenant-safe.

When filtering by Project/Application/Environment/Event Source:

- validate ownership according to the documented read contract;
- archived Registry resources remain valid historical filters;
- cross-Organization or invalid parent relationships must not expose existence.

Historical Event visibility must not depend on the current active state of archived Project, Application, or Environment resources.

## Filters

Support exactly the filters defined by the reconciled Events API.

At minimum verify and implement the documented contract for:

- Project;
- Application;
- Environment;
- Event Source;
- `event_type`;
- `category`;
- severity;
- `occurred_at` range;
- `received_at` range.

Do not invent additional filtering.

Validate:

- supported Event types;
- supported categories;
- supported severity values;
- timestamp format and range semantics.

If hierarchy filters are combined, enforce their relationships.

## Historical Visibility

Accepted Events are immutable historical evidence.

Therefore:

- Events remain readable when their Project is archived;
- Events remain readable when their Application is archived;
- Events remain readable when their Environment is archived;
- revoked Event Source credentials do not hide previously accepted Events.

The current Organization must still be accessible through normal `TenantContext` rules.

## Ordering

Canonical ordering:

`occurred_at DESC, id DESC`

This is not `created_at` ordering.

Do not reuse `CreatedAtIdCursor` if doing so changes the canonical ordering semantics.

Reuse shared cursor primitives only where genuinely compatible.

If a dedicated Event cursor is required, keep it minimal and based on:

- `occurred_at`
- Event UUID

## Cursor Pagination

Use stable keyset pagination.

Requirements:

- no offset pagination;
- cursor preserves canonical ordering;
- cursor is opaque;
- malformed cursors are rejected;
- cursor scope includes the active filter set;
- a cursor generated for one filter set cannot be reused with another;
- identical `occurred_at` timestamps paginate deterministically through Event ID.

Do not duplicate generic cursor machinery unnecessarily.

## Payload and Attributes

Follow the reconciled List Events API contract exactly.

If list responses intentionally omit or reduce payload/attributes for efficiency, preserve that decision.

Do not automatically return full Event payloads merely because Get Event does.

Do not implement Policy-filtered detail behavior intended only for Get Event unless the List contract requires it.

## Security

Verify:

- unauthenticated human rejected;
- unauthorized member rejected;
- Event Source bearer credential cannot use the human browsing endpoint;
- cross-tenant Events never appear;
- cross-tenant hierarchy filters do not disclose resources;
- archived parents preserve historical visibility;
- source credential revocation does not remove historical Event visibility;
- credential secrets/hashes never appear.

## Tests

Cover at minimum:

- authorized listing;
- unauthorized member;
- unauthenticated request;
- Event Source bearer auth rejected for browsing;
- only tenant Events returned;
- Project filter;
- Application filter;
- Environment filter;
- Event Source filter;
- event type filter;
- category filter;
- severity filter;
- occurred-at range;
- received-at range;
- invalid filter values;
- invalid cross-parent hierarchy filters;
- archived Project historical visibility;
- archived Application historical visibility;
- archived Environment historical visibility;
- Events from a source with revoked credential remain visible;
- canonical `occurred_at DESC, id DESC` ordering;
- equal `occurred_at` values order deterministically by ID;
- cursor pagination;
- malformed cursor rejection;
- cursor/filter mismatch rejection;
- canonical public representation;
- no credential or internal idempotency data exposed;
- no aggregate hydration on the read path;
- owner receives `event.read` through provisioning/backfill.

## Excluded

Do not add:

- Get Event.
- Event update/delete.
- Event timeline API.
- batch ingestion.
- Event Source list/get.
- Signal derivation.
- Alert evaluation.
- Outbox dispatcher.
- search projections outside this Event read contract.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- endpoint
- tenant/hierarchy behavior
- filters implemented
- historical visibility
- read-model approach
- ordering/cursor behavior
- response behavior
- owner permission bootstrap
- files changed
- tests
- blockers

Maximum 15 lines.