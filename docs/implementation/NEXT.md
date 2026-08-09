# Current Task

Define the last-administrative-access protection invariant.

## Required

- This is a documentation and architecture decision only.
- Do not modify application code yet.
- Review only the canonical Organization, IdentityAccess, Membership, Role, Permission, Security, ADR, and MVP documentation relevant to administrative access.
- Determine whether Sentinel must guarantee that every active Organization retains at least one active human identity with effective administrative access.
- Evaluate the existing lockout paths:
    - suspending an administrative Membership;
    - removing an administrative Membership;
    - revoking its administrative RoleAssignment;
    - revoking administrative Permissions from its Role.
- Determine whether protection should be based on:
    - a special `organization_owner` Role;
    - at least one active owner Membership;
    - effective Permissions;
    - or a broader administrative-capability invariant.
- Prefer effective capability semantics over Role display names unless canonical documentation explicitly requires owner-role semantics.
- Determine which Core permission or permission set represents sufficient administrative recovery capability.
- Define where the invariant belongs architecturally:
    - Domain rule;
    - application Policy/service;
    - persistence constraint;
    - or a combination.
- Identify which already-implemented operations will require retrofitting once the invariant is decided.
- Recommend one concrete invariant and one bounded implementation task.

## Rules

- Do not invent a large owner hierarchy.
- Do not add new Roles or Permission codes unless absolutely required by canonical documentation.
- Do not modify `STATUS.md`.
- Do not modify `NEXT.md`.
- Do not modify application code.
- Keep the decision compatible with future custom Roles.

## Reporting

Return only:

- canonical evidence
- recommended invariant
- administrative capability definition
- affected existing operations
- enforcement location
- recommended implementation task
- unresolved ambiguity

Maximum 20 lines.