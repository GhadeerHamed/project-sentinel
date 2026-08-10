# Current Task

Implement the Archive Environment vertical slice.

## Required

- Follow the reconciled Environment module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use the canonical Environment archive permission from `CorePermissions`.
- Add the permission only if canonically required and not already present.
- Ensure the built-in `organization_owner` Role receives the permission.
- Reuse `OrganizationPermissionAuthorizer` through an operation-specific Archive Environment Policy.

## Ownership

Canonical hierarchy:

Organization → Project → Application → Environment

- Resolve the Project within the Organization from `TenantContext`.
- Resolve the Application within the Organization and route Project.
- Resolve the Environment within the Organization and Application.
- Validate Project ownership through the Application relationship.
- Cross-Organization, cross-Project, and cross-Application mutations must be impossible.
- Never perform a global Environment lookup followed by tenant validation.

## Parent Lifecycle

Environment archive is allowed for historical cleanup beneath active or archived parents where canonical documentation permits it.

Verify the reconciled contract before enforcing parent lifecycle restrictions.

Do not allow archived parent state to bypass tenant or authorization checks.

## Lifecycle

Allow only:

`active` → `archived`

The Environment domain model must:

- enforce the transition;
- set `archived_at` using the existing Clock;
- increment `ResourceVersion`;
- emit the canonical `environment.archived` Domain Event.

Already archived Environments must follow canonical invalid-transition/conflict semantics.

Do not introduce:

- disabled;
- maintenance;
- suspended;
- reactivation;
- provisioning;
- restoration;
- deletion.

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
2. resolve Project, Application, and Environment within the tenant hierarchy;
3. execute the Environment domain transition;
4. persist the Environment;
5. append Domain Events to Outbox;
6. record Audit action `environment.archive`;
7. commit atomically.

Reuse `TenantScopedOperationRecorder` where appropriate.

## HTTP

Expose the canonical Environment archive endpoint from the reconciled API documentation.

Require:

- session authentication;
- resolved `TenantContext`;
- Environment archive authorization;
- `If-Match`.

Keep the controller thin.

## Security

- Tenant access alone does not authorize Environment archive.
- Cross-parent Environment mutation must return tenant-safe not-found behavior.
- `environment_type = production` must not bypass or replace permission authorization.
- Do not accept tenant, parent, status, version, or archive metadata from the request body.
- Suspended Organizations remain blocked by `TenantContext`.
- Do not authorize through Role names.

## Tests

Cover at minimum:

- authorized archive succeeds;
- unauthorized actor is rejected;
- unauthenticated request is rejected;
- missing Environment is rejected;
- cross-Organization Project is rejected;
- Application belonging to another Project is rejected;
- Environment belonging to another Application is rejected;
- active Environment becomes archived;
- `archived_at` is persisted;
- version increments;
- already archived Environment is rejected;
- missing `If-Match` is rejected;
- stale `If-Match` returns the canonical concurrency response;
- success returns quoted ETag;
- `environment.archived` Outbox record is written;
- `environment.archive` Audit record is written;
- failed mutation writes neither Outbox nor Audit;
- newly created Organization owner receives the required archive permission without manual seeding.

## Excluded

Do not add:

- Environment update.
- Environment reactivation.
- Environment deletion/restoration.
- Environment criticality.
- region.
- settings.
- endpoint/runtime metadata.
- downstream Event/Alert/Incident integration.
- unrelated Milestone 4 functionality.

## Verification

Run targeted tests and `docker compose exec app composer quality`.

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