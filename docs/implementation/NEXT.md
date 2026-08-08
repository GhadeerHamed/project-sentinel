# Current Task

Implement the Revoke Role vertical slice.

## Required

- Follow the canonical IdentityAccess, role-assignment, permission, API, and database documentation.
- Use the existing authenticated `IdentityReference`.
- Require the existing resolved `TenantContext`.
- Use the canonical permission through `CorePermissions`; do not introduce a new permission code.
- Require an active acting Membership.
- Reuse `OrganizationPermissionAuthorizer` through an operation-specific Revoke Role Policy.
- Load the target RoleAssignment through the existing repository.
- Ensure the assignment belongs to the resolved Organization.
- Ensure the assigned identity belongs to the resolved Organization through the canonical Membership model.
- Enforce the canonical RoleAssignment revocation lifecycle.
- Preserve revocation metadata rather than physically deleting assignment history when required by the canonical model.
- Require optimistic concurrency if the RoleAssignment aggregate/version contract supports it.
- Emit the canonical `role.revoked` Domain Event.
- Append the event to the Outbox in the same transaction.
- Record the corresponding Audit record.
- Reuse `TenantScopedOperationRecorder` where it naturally fits.
- Expose the operation through an authenticated Organization-scoped HTTP endpoint.
- Return ETag/version metadata when the resource is versioned.
- Add focused application, authorization, persistence, tenant-isolation, lifecycle, and HTTP tests.

## Security Rules

- Tenant access alone does not authorize Role revocation.
- Cross-Organization RoleAssignment mutation must be impossible.
- Do not revoke assignments by Role display name.
- Do not accept `organization_id` from request payload.
- Already-revoked assignments must follow canonical conflict/lifecycle semantics.
- Do not modify Role permissions as part of this operation.
- Do not physically delete historical assignments unless canonical documentation explicitly requires it.

## Excluded

Do not add:

- Role CRUD endpoints.
- custom Role creation.
- permission-management endpoints.
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
- revocation lifecycle
- duplicate/already-revoked behavior
- tenant-isolation behavior
- files changed
- event/audit behavior
- tests
- blockers

Maximum 12 lines.