# Current Task

Implement the Suspend Membership vertical slice.

## Required

- Follow the canonical IdentityAccess, Membership, API, and database documentation.
- Use the existing authenticated `IdentityReference`.
- Require the existing resolved `TenantContext`.
- Use the canonical `organization.member.manage` permission.
- Require an active acting Membership.
- Authorize through the existing tenant-scoped effective-permission mechanism.
- Load the target Membership through `MembershipRepository`.
- Ensure the target Membership belongs to the resolved Organization.
- Enforce the canonical Membership lifecycle transition to `suspended`.
- Use optimistic concurrency if Membership mutation contracts require `ExpectedVersion`.
- Emit the canonical Membership suspension Domain Event.
- Append the event to the Outbox in the same transaction.
- Record Audit data for the suspension.
- Persist through the existing repository contract.
- Expose the operation through the conventional authenticated Organization-scoped endpoint.
- Add focused application, persistence, authorization, and HTTP tests.

## Security Rules

- Tenant access alone does not authorize Membership suspension.
- Cross-Organization Membership mutation must be impossible.
- A suspended or removed acting Membership must never authorize the operation.
- Do not trust an `organization_id` from request payload.
- Do not bypass the Membership Domain lifecycle.
- Verify canonical rules for suspending the acting User's own Membership or the Organization owner before implementing those cases; do not invent behavior.

## Excluded

Do not add:

- invitation acceptance
- invitation tokens
- email delivery
- Membership reactivation
- Membership removal
- role-management endpoints
- permission-management endpoints
- API tokens
- unrelated Organization operations

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- lifecycle transition
- self/owner suspension rule selected
- authorization behavior
- files changed
- concurrency/event/audit behavior
- tests
- blockers

Maximum 15 lines.