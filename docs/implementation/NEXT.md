# Current Task

Implement the Accept Membership Invitation vertical slice.

## Required

- Follow the canonical IdentityAccess and Membership documentation.
- Use the existing authenticated human `IdentityReference`.
- Do not require the normal active-Membership `TenantContext` middleware.
- Load the invited Membership directly through the repository using the route Organization and Membership identifiers.
- Verify the Membership belongs to the route Organization.
- Verify the invited Membership belongs to the authenticated User.
- Allow only the explicit `invited` → `active` transition through `Membership::acceptInvitation()`.
- Require optimistic concurrency using the existing version contract.
- Emit the canonical invitation-acceptance/activation Domain Event.
- Append the event to the Outbox in the same transaction.
- Record the corresponding Audit record.
- Persist through the existing Membership repository.
- Return the updated resource version through an ETag.
- Expose the operation through an authenticated HTTP endpoint.
- Add focused application, persistence, authorization/ownership, and HTTP tests.

## Security Rules

- The authenticated User may accept only his own invitation.
- Do not use `organization.member.manage` for invitation acceptance.
- Do not require an active Membership before acceptance.
- Cross-Organization Membership acceptance must be impossible.
- Do not accept `organization_id` or identity ownership from request payload.
- Suspended, active, or removed Memberships must not be accepted as invitations.

## Excluded

Do not add:

- invitation email delivery
- invitation tokens
- unauthenticated acceptance
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

- endpoint selected
- ownership verification
- lifecycle/event semantics
- files changed
- concurrency/audit behavior
- tests
- blockers

Maximum 12 lines.