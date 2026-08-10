# Current Task

Implement the List Applications vertical slice.

## Required

- Follow the canonical Application module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use the canonical Application read permission from existing documentation; do not invent a permission code.
- Add the permission to `CorePermissions` only if canonically required and not already present.
- Ensure the built-in `organization_owner` Role receives the read permission when required.
- Authorize through an operation-specific Policy using `OrganizationPermissionAuthorizer`.

## Parent Ownership

Canonical hierarchy:

Organization → Project → Application

- Resolve the Project only within the Organization from `TenantContext`.
- Ensure the route Project belongs to the resolved Organization.
- Do not accept `organization_id` or `project_id` from query parameters or payload.
- Follow the canonical historical-read behavior for archived Projects.
- Application queries must always remain scoped by both Organization and Project.

## Read Model

- Do not hydrate Application aggregates for the read-only list.
- Add a framework-independent Application read model/DTO containing only API-required fields.
- Add the smallest query/read contract required.
- Implement a PostgreSQL projection query selecting only required columns.
- Avoid N+1 queries.
- Keep persistence aggregate mapping separate from read projection mapping.

The canonical public representation should expose only documented fields such as:

- id
- project_id where required by the API
- name
- slug
- description
- criticality
- status
- version
- created_at
- updated_at

Do not expose creator identity or internal archival metadata unless explicitly required by the API contract.

## Visibility

Determine archived Application visibility from the reconciled canonical documentation.

- Preserve authorized historical access where documented.
- Apply canonical default filtering.
- Follow existing API pagination, ordering, filtering, and cursor conventions.
- Do not invent filters that are not documented.

## HTTP

Expose the canonical endpoint:

`GET /api/v1/organizations/{organization}/projects/{project}/applications`

Require:

- session authentication;
- resolved `TenantContext`;
- canonical Application read authorization;
- valid tenant-scoped parent Project.

Keep the controller thin.

Use a dedicated Application API Resource/Presenter.

## Security

- Tenant access alone does not authorize Application reads.
- Cross-Organization Projects must return the existing tenant-safe not-found response.
- Applications from another Project must never be returned.
- Suspended Organizations remain blocked by TenantContext resolution.
- Do not authorize using Role names.
- Do not expose unrelated tenant, Role, permission, or credential data.

## Tests

Cover at minimum:

- authorized listing;
- unauthorized actor;
- unauthenticated request;
- cross-Organization Project rejection;
- only Applications belonging to the requested Project are returned;
- Applications from another Project are excluded;
- archived Application visibility follows canonical documentation;
- archived parent Project behavior follows canonical documentation;
- criticality and canonical fields are represented correctly;
- canonical pagination/order behavior;
- response does not expose internal fields;
- read path does not hydrate Application aggregates;
- newly created Organization owner has the required read permission without manual permission seeding.

## Excluded

Do not add:

- Get Application.
- Application update.
- Application archive.
- criticality mutation.
- Application settings.
- runtime/framework metadata.
- Environment implementation.
- unrelated Milestone 4 capabilities.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission/visibility rule used
- parent Project behavior
- archived visibility behavior
- read-model/query approach
- endpoint
- files changed
- tests
- blockers

Maximum 12 lines.