# Current Task

Implement the Archive Project vertical slice.

## Required

- Follow the canonical Project module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use the canonical Project archive permission from `CorePermissions`; do not invent a permission code.
- Ensure the built-in `organization_owner` Role receives that permission if required by the canonical permission catalog.
- Reuse `OrganizationPermissionAuthorizer` through an operation-specific Archive Project Policy.
- Load the target Project through the tenant-scoped Project repository.
- Ensure the Project belongs to the Organization from `TenantContext`.
- Allow only the canonical lifecycle transition:
    - `active` → `archived`
- Perform the transition through the Project domain model.
- Set `archived_at` using the existing Clock.
- Require optimistic concurrency using the existing `ExpectedVersion` contract.
- Persist through the Project repository.
- Emit the canonical `project.archived` Domain Event.
- Append the event to the Outbox in the same transaction.
- Record Audit action `project.archive`.
- Reuse `TenantScopedOperationRecorder` where appropriate.
- Expose the operation through the canonical authenticated Organization-scoped HTTP endpoint.
- Return the updated version through the existing mutation ETag contract.

## Security

- Tenant access alone does not authorize Project archive.
- Cross-Organization Project mutation must be impossible.
- Project lookup must be scoped by Organization and Project ID.
- Do not accept `organization_id`, status, archived timestamp, or version from the request body.
- Do not authorize through Role names.
- Suspended Organizations remain blocked by existing TenantContext resolution.

## Lifecycle

- `active` → `archived` is allowed.
- Archiving an already archived Project must follow the canonical invalid-transition/conflict semantics.
- Do not add:
    - Project suspension;
    - Project reactivation;
    - deletion;
    - restoration;
    - provisioning/restricted states.

## HTTP

Use the canonical Project archive endpoint defined by the reconciled API documentation.

Require:

- session authentication;
- resolved TenantContext;
- Project archive authorization;
- `If-Match`.

Keep the controller thin.

## Tests

Cover at minimum:

- authorized archive succeeds;
- unauthorized actor is rejected;
- unauthenticated request is rejected;
- cross-Organization Project returns tenant-safe not found;
- active Project becomes archived;
- `archived_at` is populated;
- already archived Project is rejected;
- missing or stale `If-Match` is rejected;
- successful mutation returns quoted ETag;
- `project.archived` Outbox record is written;
- `project.archive` Audit record is written;
- failed mutation writes neither Outbox nor Audit;
- newly created Organization owner has the required archive permission without manual permission seeding.

## Excluded

Do not add:

- Project update.
- Project reactivation.
- Project deletion/restoration.
- Project settings.
- Project criticality.
- Application or Environment capabilities.
- unrelated Milestone 4 functionality.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- lifecycle transition
- tenant-isolation behavior
- concurrency behavior
- endpoint
- event/audit behavior
- files changed
- tests
- blockers

Maximum 12 lines.