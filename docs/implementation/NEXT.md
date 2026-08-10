# Current Task

Implement the Archive Application vertical slice.

## Required

- Follow the canonical Application module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use the canonical Application archive permission from `CorePermissions`.
- Add the permission only if canonically required and not already present.
- Ensure the built-in `organization_owner` Role receives the permission.
- Reuse `OrganizationPermissionAuthorizer` through an operation-specific Archive Application Policy.

## Ownership

Canonical hierarchy:

Organization → Project → Application

- Resolve the Project within the Organization from `TenantContext`.
- Resolve the Application using Organization, Project, and Application identifiers.
- Cross-Organization or cross-Project mutation must be impossible.
- The Application must belong to the route Project.
- Do not accept `organization_id`, `project_id`, or status from the request body.

## Lifecycle

Allow only:

`active` → `archived`

The Application domain model must:

- enforce the transition;
- set `archived_at` using the existing Clock;
- increment `ResourceVersion`;
- emit the canonical `application.archived` Domain Event.

Already archived Applications must follow the canonical invalid-transition/conflict semantics.

Do not add suspended, reactivation, deletion, restoration, provisioning, or restricted states.

## Concurrency

Require the existing optimistic-concurrency contract.

- Require `If-Match`.
- Persist using `ExpectedVersion`.
- Stale versions return the canonical concurrency response.
- Successful mutation returns the quoted updated ETag.

## Application Layer

Add the framework-independent:

- command;
- result;
- Policy;
- handler.

The handler must:

1. authorize the archive operation;
2. resolve the tenant-scoped Project and Application;
3. execute the Application domain transition;
4. persist the Application;
5. append Domain Events to Outbox;
6. record Audit action `application.archive`;
7. commit atomically.

Reuse `TenantScopedOperationRecorder` where appropriate.

## HTTP

Expose the canonical Application archive endpoint from the reconciled API documentation.

Require:

- session authentication;
- resolved `TenantContext`;
- Application archive authorization;
- `If-Match`.

Keep the controller thin.

## Security

- Tenant access alone does not authorize Application archive.
- Never perform a global Application lookup followed by tenant validation.
- Cross-Project Application IDs must return tenant-safe not-found behavior.
- Archived Projects may remain historically readable, but verify canonical rules before allowing mutations beneath an archived Project.
- Suspended Organizations remain blocked by TenantContext resolution.
- Do not authorize using Role names.

## Tests

Cover at minimum:

- authorized archive succeeds;
- unauthorized actor is rejected;
- unauthenticated request is rejected;
- missing Application is rejected;
- cross-Organization Project is rejected;
- Application from another Project is rejected;
- active Application becomes archived;
- `archived_at` is populated;
- version increments;
- already archived Application is rejected;
- missing or stale `If-Match` is rejected;
- success returns quoted ETag;
- `application.archived` Outbox record is written;
- `application.archive` Audit record is written;
- failed mutation writes neither Outbox nor Audit;
- newly created Organization owner has the required archive permission without manual permission seeding.

## Excluded

Do not add:

- Application update.
- Application reactivation.
- Application deletion/restoration.
- criticality mutation.
- Application settings.
- runtime/framework metadata.
- Environment implementation.
- unrelated Milestone 4 functionality.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- lifecycle transition
- parent ownership behavior
- concurrency behavior
- endpoint
- event/audit behavior
- files changed
- tests
- blockers

Maximum 12 lines.