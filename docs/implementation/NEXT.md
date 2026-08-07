# Current Task

Implement the Remove Membership vertical slice.

## Required

- Follow the canonical IdentityAccess and Membership documentation.
- Use the existing authenticated `IdentityReference`.
- Require the existing resolved `TenantContext`.
- Use the canonical `organization.member.manage` permission.
- Require an active acting Membership.
- Authorize through the existing tenant-scoped effective-permission mechanism.
- Load the target Membership through `MembershipRepository`.
- Ensure the target Membership belongs to the resolved Organization.
- Use the existing Membership domain lifecycle operation for removal.
- Allow only lifecycle transitions supported by canonical documentation.
- Require optimistic concurrency using the existing version contract.
- Emit the canonical Membership removal Domain Event.
- Append the event to the Outbox in the same transaction.
- Record the corresponding Audit record.
- Persist through the existing repository contract.
- Expose the operation through the conventional authenticated Organization-scoped HTTP endpoint.
- Return the updated resource version through an ETag when applicable.
- Add focused application, persistence, authorization, and HTTP tests.

## Security Rules

- Tenant access alone does not authorize Membership removal.
- Cross-Organization Membership mutation must be impossible.
- Suspended or removed acting Memberships must not authorize the operation.
- Do not accept `organization_id` from request payload.
- Do not physically delete Membership history unless canonical documentation explicitly requires it.
- Do not invent special owner/self-removal restrictions; derive them from canonical documentation.

## Excluded

Do not add:

- Membership reactivation changes
- invitation acceptance changes
- role-management endpoints
- permission-management endpoints
- API tokens
- unrelated Organization lifecycle operations

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- lifecycle transition
- self/owner rule selected
- authorization behavior
- files changed
- concurrency/event/audit behavior
- tests
- blockers

Maximum 12 lines.