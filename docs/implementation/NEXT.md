# Current Task

Implement the List Environments vertical slice.

## Required

- Follow the reconciled Environment module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use the canonical Environment read permission from existing documentation; do not invent a permission code.
- Add it to `CorePermissions` only if canonically required and not already present.
- Ensure the built-in `organization_owner` Role receives the permission.
- Authorize through an operation-specific Policy using `OrganizationPermissionAuthorizer`.

## Ownership

Canonical hierarchy:

Organization → Project → Application → Environment

- Resolve the Project within the Organization from `TenantContext`.
- Resolve the Application within the Organization and route Project.
- Environment queries must be scoped by Organization and Application.
- Project ownership is validated through the Application relationship.
- Do not accept tenant or parent identifiers from query input.
- Cross-Organization and cross-Project parents must return tenant-safe not-found behavior.

## Parent Historical Access

Follow the reconciled historical-read rules.

- Archived Projects remain available for authorized historical reads.
- Archived Applications remain available for authorized historical reads.
- Listing Environments beneath archived parents must remain read-only.
- Suspended Organizations remain blocked by existing `TenantContext` resolution.

## Read Model

- Do not hydrate Environment aggregates for this read-only operation.
- Add a framework-independent Environment read DTO containing only API-required fields.
- Add the smallest query/read contract required.
- Implement a scoped PostgreSQL projection selecting only required columns.
- Avoid N+1 queries.
- Reuse `CreatedAtIdCursor` for cursor pagination where compatible.
- Do not introduce another resource-specific cursor implementation if the shared cursor already satisfies the contract.

Canonical public fields should include only documented values such as:

- `id`
- `application_id` where required
- `name`
- `slug`
- `environment_type`
- `status`
- `version`
- `created_at`
- `updated_at`

Do not expose creator identity or internal archival metadata unless explicitly required.

## Filtering

Support only canonical documented filters.

At minimum verify the reconciled API contract for:

- `status`
- `environment_type`
- `slug`

Rules:

- active Environments are the default list view;
- archived Environments are available for authorized historical reads according to the documented status filter;
- reject unsupported filter values;
- filter state must participate in cursor scope so a cursor cannot be reused with incompatible filters.

## Ordering and Pagination

Use canonical ordering:

- `created_at DESC`
- `id DESC`

Use stable keyset cursor pagination.

Reuse the existing shared pagination infrastructure.

## HTTP

Expose:

`GET /api/v1/organizations/{organization}/projects/{project}/applications/{application}/environments`

Require:

- session authentication;
- resolved `TenantContext`;
- canonical Environment read permission;
- valid Project/Application ownership.

Keep the controller thin.

Use a dedicated Environment API Resource/Presenter.

## Security

- Tenant access alone does not authorize Environment reads.
- Applications belonging to another Project must never expose Environments.
- Environments belonging to another Application must never be returned.
- `environment_type = production` does not alter authorization.
- Do not authorize through Role names.
- Do not expose unrelated tenant, Role, permission, or credential data.

## Tests

Cover at minimum:

- authorized listing;
- unauthorized actor;
- unauthenticated request;
- cross-Organization Project rejection;
- Application belonging to another Project rejection;
- only Environments belonging to the requested Application are returned;
- active Environments are returned by default;
- archived Environment historical filtering;
- filtering by `environment_type`;
- filtering by slug where canonically supported;
- invalid filters are rejected;
- canonical `created_at DESC, id DESC` ordering;
- cursor pagination;
- cursor/filter mismatch rejection;
- archived Project historical read behavior;
- archived Application historical read behavior;
- canonical public fields;
- read path does not hydrate aggregates;
- owner receives the read permission without manual seeding.

## Excluded

Do not add:

- Get Environment.
- Environment update.
- Environment archive.
- Environment criticality.
- region.
- settings.
- endpoint/runtime metadata.
- downstream Event/Alert/Incident integration.
- new lifecycle states.
- unrelated Milestone 4 functionality.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission/visibility rule used
- parent ownership behavior
- archived visibility behavior
- filters implemented
- read-model/pagination approach
- endpoint
- files changed
- tests
- blockers

Maximum 12 lines.