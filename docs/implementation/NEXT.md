# Current Task

Implement the first Organization-scoped authorization slice through Update Organization.

## Required

- Follow the existing Organization API and module documentation.
- Add the framework-independent application command, result, Policy, and handler for updating Organization profile fields that are already documented.
- Require the existing `TenantContext`.
- Authorize using the authenticated identity's active Membership and effective permissions for the resolved Organization.
- Implement the smallest permission-checking contract required by this use case.
- Use the canonical permission code from existing documentation; do not invent a new permission name.
- Keep tenant access and action authorization separate.
- Enforce optimistic concurrency using `ExpectedVersion`.
- Persist through `OrganizationRepository`.
- Record the appropriate Domain Event, Outbox message, and Audit record.
- Expose the use case through the documented authenticated Organization-scoped HTTP endpoint.
- Add focused application, integration, and feature tests.

## Security Rules

- Membership alone must not grant update permission.
- Permission evaluation must be scoped to the current Organization.
- A Role or permission from another Organization must never authorize the action.
- Suspended or removed Memberships must be rejected before action authorization.
- Do not authorize based on role display names.
- Do not trust Organization IDs from request data outside the resolved `TenantContext`.

## Excluded

Do not add:

- Organization deletion.
- Organization suspension/reactivation unless required by the selected update use case.
- role-management endpoints.
- Membership-management endpoints.
- API tokens.
- unrelated IdentityAccess handlers.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- Policy behavior
- files changed
- concurrency/event/audit behavior
- test result
- remaining blocker

Maximum 15 lines.