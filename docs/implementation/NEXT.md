# Current Task

Implement the List Projects vertical slice.

## Required

- Follow the canonical Project module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Determine the canonical Project read/list permission from existing documentation; do not invent a permission code.
- Reuse `OrganizationPermissionAuthorizer` if Project listing requires an explicit permission.
- Return only Projects belonging to the resolved Organization.
- Do not accept `organization_id` from query parameters or request payload.
- Follow the canonical Project visibility rules for archived Projects.
- Follow existing API pagination, ordering, and filtering conventions where applicable.

## Read Model

- Do not hydrate full Project aggregates for a read-only list.
- Add a framework-independent Project read model/DTO containing only fields required by the API.
- Add the smallest read/query contract required by the application layer.
- Implement a tenant-scoped PostgreSQL projection query.
- Select only required columns.
- Avoid N+1 queries.

Keep aggregate persistence mapping separate from API/read-model projection mapping.

## HTTP

Expose:

`GET /api/v1/organizations/{organization}/projects`

- Require session authentication.
- Require the existing TenantContext middleware.
- Keep the controller thin.
- Use a dedicated Project API Resource/Presenter.
- Return the canonical Project collection representation.
- Do not expose creator credentials, Role data, permission data, or unrelated tenant information.

## Security

- Cross-Organization Projects must never be visible.
- TenantContext Organization is the authoritative tenant boundary.
- Do not trust tenant identifiers supplied elsewhere in the request.
- Suspended Organizations remain blocked by existing tenant resolution.
- Authentication, tenant access, and action authorization remain separate concerns.

## Tests

Cover at minimum:

- authenticated authorized listing;
- only Projects from the resolved Organization are returned;
- cross-Organization Projects are excluded;
- archived Project visibility follows the canonical documentation;
- suspended Organization cannot access the endpoint;
- unauthenticated request is rejected;
- response contains only documented fields;
- multiple Projects are returned in canonical order;
- implementation does not require aggregate hydration.

## Excluded

Do not add:

- Get Project.
- Project update.
- Project archive.
- Project settings.
- Project criticality.
- Application or Environment capabilities.
- new permission codes unless explicitly required by canonical documentation.
- unrelated Milestone 4 functionality.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission/visibility rule used
- archived visibility behavior
- read-model/query approach
- endpoint
- files changed
- tests
- blockers

Maximum 12 lines.