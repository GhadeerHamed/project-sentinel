# Current Task

Implement the Update Role Permissions vertical slice.

## API Contract

Use:

`PATCH /api/v1/organizations/{organization}/roles/{role}/permissions`

Request body:

- `grant`: list of Core permission codes to grant.
- `revoke`: list of Core permission codes to revoke.

At least one list must contain a permission.

The same permission must not appear in both lists.

## Required

- Follow the canonical IdentityAccess, Role, Permission, API, Security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use `CorePermissions::manageOrganizationMembers()` for authorization.
- Reuse `OrganizationPermissionAuthorizer` through an operation-specific Policy.
- Resolve the target Role as an Organization-owned Role.
- Ensure the Role belongs to the resolved Organization.
- Accept only permissions defined by the current `CorePermissions` catalog.
- Reject unknown/free-form permission codes.
- Grant and revoke permissions through the existing permission-assignment repository.
- Preserve existing soft-revocation semantics.
- Re-granting a previously revoked permission must restore the grant consistently.
- Emit the canonical `role.permissions_updated` Domain Event.
- Record Outbox and Audit data in the same transaction.
- Expose the operation through the authenticated Organization-scoped HTTP API.
- Keep the controller thin.

## Administrative-Access Invariant

Role-permission mutation can reduce administrative access.

Therefore:

- serialize the mutation using the existing Organization-level administrative-access lock;
- apply all requested grants and revocations inside the transaction;
- evaluate `AdministrativeAccessInvariant` against the post-mutation state;
- reject and roll back if the Organization would lose its final effective `organization.member.manage` capability.

Do not special-case `organization_owner` by Role name.

## Validation

- `grant` and `revoke` must be arrays of unique permission codes.
- Reject permissions appearing in both lists.
- Reject unsupported permission codes.
- Reject a request where both lists are empty.
- Do not accept `organization_id` from the payload.

## Tenant Isolation

- Cross-Organization Role mutation must return the existing tenant-safe not-found behavior.
- Permission evaluation and mutation must remain scoped to the resolved Organization.
- A Role ID from another Organization must never be mutated.

## Tests

Cover at minimum:

- authorized grant succeeds;
- authorized revoke succeeds;
- revoked permission can be re-granted;
- unknown permission is rejected;
- overlapping grant/revoke is rejected;
- empty mutation is rejected;
- cross-Organization Role is rejected;
- unauthorized actor is rejected;
- revoking `organization.member.manage` succeeds when another administrative identity remains;
- revoking the final administrative-recovery capability is rejected and rolled back;
- successful mutation writes `role.permissions_updated`, Outbox, and Audit;
- failed invariant validation writes neither mutation, Outbox, nor Audit.

## Documentation

Document the API endpoint and request/response contract in the appropriate existing API documentation.

Do not create unnecessary new documentation files.

## Excluded

Do not add:

- custom Role CRUD.
- Role listing.
- Permission listing.
- additional default Roles.
- new permission codes.
- ownership-transfer semantics.
- Organization reactivation.
- unrelated IdentityAccess operations.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- endpoint
- authorization permission
- grant/revoke semantics
- invariant behavior
- tenant-isolation behavior
- files changed
- event/audit behavior
- tests
- blockers

Maximum 15 lines.