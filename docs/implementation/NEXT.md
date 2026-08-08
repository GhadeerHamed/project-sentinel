# Current Task

Implement the Assign Role vertical slice.

## Required

- Follow the canonical IdentityAccess, role, permission, API, and database documentation.
- Use the existing authenticated `IdentityReference`.
- Require the existing resolved `TenantContext`.
- Use the canonical role-management permission from existing documentation; do not invent a permission code.
- Require an active acting Membership.
- Authorize through the existing tenant-scoped effective-permission mechanism.
- Load the target Membership or identity according to the canonical RoleAssignment model.
- Load or validate the target Role through its Organization-owned identity.
- Ensure both the target Membership/identity and Role belong to the resolved Organization.
- Create the assignment through the existing `RoleAssignment` domain model.
- Enforce duplicate and incompatible assignment rules.
- Persist through the existing repository contract.
- Emit the canonical `role.assigned` Domain Event.
- Append the event to the Outbox in the same transaction.
- Record the corresponding Audit record.
- Reuse `TenantScopedOperationRecorder` where it naturally fits.
- Expose the operation through the conventional authenticated Organization-scoped HTTP endpoint.
- Add focused application, persistence, authorization, cross-tenant, and HTTP tests.

## Security Rules

- Tenant access alone does not authorize role assignment.
- A Role from another Organization must never be assignable.
- A Membership or identity from another Organization must never receive the assignment.
- Never authorize using role display names.
- Do not accept `organization_id` from request payload.
- Do not allow arbitrary permission assignment as part of this task.
- Do not implicitly create Roles.

## Excluded

Do not add:

- Revoke Role.
- Role CRUD endpoints.
- permission-management endpoints.
- custom Role creation.
- invitation changes.
- Membership lifecycle changes.
- API tokens.
- unrelated Organization operations.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- assignment model
- duplicate behavior
- tenant-isolation behavior
- files changed
- event/audit behavior
- tests
- blockers

Maximum 12 lines.