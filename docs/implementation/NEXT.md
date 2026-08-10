# Current Task

Implement the Get Project vertical slice.

## Required

- Follow the canonical Project module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use `CorePermissions::readProjects()`.
- Authorize through an operation-specific Policy using `OrganizationPermissionAuthorizer`.
- Resolve the Project only within the Organization from `TenantContext`.
- Cross-Organization Project IDs must return the existing tenant-safe not-found response.
- Follow canonical archived-Project visibility rules.
- Do not hydrate the full Project aggregate for this read-only operation.
- Reuse the existing Project read-model/projection infrastructure where appropriate.

## Read Model

- Return the existing canonical Project read representation where possible.
- Do not create another DTO if the List Projects read model already contains the required fields.
- Query only the required Project columns.
- Keep persistence aggregate mapping separate from read projection mapping.

## HTTP

Expose:

`GET /api/v1/organizations/{organization}/projects/{project}`

Requirements:

- session authentication;
- resolved `TenantContext`;
- `project.read` authorization;
- thin controller;
- dedicated Project API Resource/Presenter.

Return the canonical Project representation.

If the existing API concurrency contract exposes resource versions through ETag on reads, return the quoted Project version consistently.

## Security

- Tenant access alone does not authorize Project reads.
- Project lookup must be scoped by both Project ID and resolved Organization.
- Never perform a global Project-ID lookup followed by a tenant check.
- Do not accept `organization_id` from request input.
- Suspended Organizations remain blocked by existing tenant resolution.
- Do not expose creator credentials, Roles, permissions, or unrelated tenant data.

## Tests

Cover at minimum:

- authorized Project retrieval;
- unauthorized actor;
- unauthenticated request;
- Project from another Organization returns tenant-safe not found;
- missing Project returns not found;
- active Project retrieval;
- archived Project behavior follows canonical documentation;
- canonical response fields;
- ETag/version behavior where required;
- read path does not require aggregate hydration.

## Excluded

Do not add:

- Project update.
- Project archive.
- Project settings.
- Project criticality.
- Application or Environment capabilities.
- new permission codes.
- unrelated Milestone 4 functionality.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- archived visibility behavior
- read-model/query approach
- endpoint
- ETag behavior
- files changed
- tests
- blockers

Maximum 12 lines.