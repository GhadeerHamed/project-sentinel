# Current Task

Implement the Get Application vertical slice.

## Required

- Follow the canonical Application module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use `CorePermissions::readApplications()`.
- Authorize through an operation-specific Policy using `OrganizationPermissionAuthorizer`.
- Reuse the existing Application read model/query infrastructure.
- Do not hydrate the Application aggregate for this read-only operation.

## Ownership

Canonical hierarchy:

Organization → Project → Application

- Resolve the Project within the Organization from `TenantContext`.
- Resolve the Application using Organization, Project, and Application identifiers.
- Never perform a global Application lookup followed by tenant validation.
- Cross-Organization or cross-Project Application IDs must return the existing tenant-safe not-found response.
- Archived Projects remain readable according to the existing canonical historical-read behavior.

## Visibility

- Active Applications are readable.
- Archived Applications remain readable for authorized historical access.
- Do not require a `status` query parameter for direct retrieval.
- Suspended Organizations remain blocked by existing `TenantContext` resolution.

## HTTP

Expose:

`GET /api/v1/organizations/{organization}/projects/{project}/applications/{application}`

Require:

- session authentication;
- resolved `TenantContext`;
- `application.read`;
- valid tenant-scoped parent Project.

Keep the controller thin.

Reuse the existing Application API Resource/Presenter where possible.

Return only the canonical public Application representation.

Do not expose:

- creator identity;
- internal archival metadata;
- Roles or permissions;
- unrelated tenant data.

## Read Model

- Reuse the existing Application read DTO if it contains all required fields.
- Add only the smallest lookup-query capability required.
- Select only API-required columns.
- Keep aggregate persistence mapping separate from projection mapping.
- Avoid unnecessary joins or N+1 queries.

## Tests

Cover at minimum:

- authorized Application retrieval;
- unauthorized actor;
- unauthenticated request;
- missing Application;
- cross-Organization Project rejection;
- Application belonging to another Project returns tenant-safe not found;
- active Application retrieval;
- archived Application historical retrieval;
- archived parent Project remains readable;
- canonical criticality and public fields;
- internal fields are not exposed;
- read path does not hydrate the aggregate.

## Excluded

Do not add:

- Application update.
- Application archive.
- criticality mutation.
- Application settings.
- runtime/framework metadata.
- Environment capabilities.
- new permission codes.
- unrelated Milestone 4 functionality.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- parent ownership behavior
- archived visibility behavior
- read-model/query approach
- endpoint
- files changed
- tests
- blockers

Maximum 12 lines.