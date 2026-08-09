# Current Task

Implement the administrative-access protection invariant.

## Invariant

Every active Organization must retain at least one active human Membership with effective:

`organization.member.manage`

This is an administrative-recovery capability invariant.

It must not depend on Role names such as `organization_owner`.

## Required

- Add a framework-independent IdentityAccess application service representing the invariant.
- Use `CorePermissions::manageOrganizationMembers()` as the administrative-recovery capability.
- Evaluate effective permissions, not Role names.
- Count only:
    - human identities;
    - active Memberships;
    - effective, non-revoked Role assignments;
    - effective, non-revoked permission grants.
- Reject a mutation if its committed result would leave an active Organization without a qualifying administrative identity.
- Return an explicit typed conflict/invariant error.

## Concurrency

The invariant must be transaction-safe.

- Serialize administrative-access-reducing mutations for the same Organization.
- Prefer a PostgreSQL row lock on the Organization record or an equivalent existing transaction-safe mechanism.
- Acquire the Organization-level mutation lock before evaluating and applying an administrative-access-reducing mutation.
- Do not rely only on:
    - `COUNT(*)`;
    - application-level prechecks;
    - optimistic concurrency on the Membership or RoleAssignment being changed.
- Concurrent requests must not both succeed if their combined result removes the final administrative recovery capability.

## Retrofit Existing Operations

Apply the invariant to:

- `SuspendMembershipHandler`
- `RemoveMembershipHandler`
- `RevokeRoleHandler`

Do not apply it to operations that cannot reduce administrative access.

The mutation should conceptually remain:

1. begin transaction;
2. serialize administrative-access mutation for the Organization;
3. load and authorize;
4. perform the domain mutation;
5. persist the mutation;
6. verify the post-mutation administrative-access invariant;
7. record Outbox and Audit;
8. commit.

If the invariant fails, the entire transaction must roll back.

## Tests

Add focused coverage proving:

- suspending the final administrative Membership is rejected;
- removing the final administrative Membership is rejected;
- revoking the final administrative RoleAssignment is rejected;
- the same operations succeed when another qualifying administrator remains;
- a Role name alone does not satisfy the invariant;
- an inactive Membership does not satisfy the invariant;
- a revoked RoleAssignment does not satisfy the invariant;
- a revoked `organization.member.manage` grant does not satisfy the invariant;
- cross-Organization identities do not satisfy the invariant;
- concurrent administrative-access-reducing mutations cannot both remove the final recovery path.

## Documentation

Record the invariant concisely in the existing canonical IdentityAccess/security documentation.

Keep business ownership semantics separate:

- this task protects administrative recovery capability;
- it does not introduce ownership transfer or a special last-owner Role rule.

## Excluded

Do not add:

- Role-permission management endpoints.
- ownership transfer.
- special `organization_owner` restrictions.
- new Roles.
- new Permission codes.
- Organization reactivation.
- unrelated lifecycle changes.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- invariant implementation
- locking strategy
- handlers retrofitted
- rejection semantics
- files changed
- concurrency coverage
- tests
- blockers

Maximum 15 lines.