# Current Task

Implement the Reactivate Membership vertical slice.

## Required

- Follow the canonical IdentityAccess, Membership, API, and database documentation.
- Use the existing authenticated `IdentityReference`.
- Require the existing resolved `TenantContext`.
- Use the canonical `organization.member.manage` permission.
- Require an active acting Membership.
- Authorize through the existing tenant-scoped effective-permission mechanism.
- Load the target Membership through `MembershipRepository`.
- Ensure the target Membership belongs to the resolved Organization.
- Allow only the canonical `suspended` → `active` Membership lifecycle transition.
- Use the existing Membership domain behavior rather than mutating status directly.
- Require optimistic concurrency using the existing version contract.
- Emit the canonical Membership reactivation Domain Event.
- Append the event to the Outbox in the same transaction.
- Record the corresponding Audit record.
- Persist through the existing repository contract.
- Expose the operation through the conventional authenticated Organization-scoped HTTP endpoint.
- Return the updated resource version through an ETag consistent with the existing concurrency contract.
- Add focused application, persistence, authorization, and HTTP tests.

## Security Rules

- Tenant access alone does not authorize Membership reactivation.
- Cross-Organization Membership mutation must be impossible.
- A suspended or removed acting Membership must never authorize the operation.
- Do not allow an invited or removed Membership to be reactivated unless canonical lifecycle documentation explicitly allows it.
- Do not accept `organization_id` from the request payload.
- Do not bypass Membership lifecycle validation.

## Excluded

Do not add:

- invitation acceptance
- invitation tokens
- email delivery
- Membership removal
- role-management endpoints
- permission-management endpoints
- API tokens
- unrelated Organization operations

## Verification

Run targeted tests and `docker compose exec app composer quality`.

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- lifecycle transition
- authorization behavior
- files changed
- concurrency/event/audit behavior
- tests
- blockers

Maximum 15 lines.