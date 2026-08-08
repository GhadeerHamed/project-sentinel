# Current Task

Review Milestone 3 completion against the canonical documentation.

## Required

- Do not implement or modify application code.
- Review `STATUS.md` and only the documentation directly relevant to:
    - IdentityAccess
    - Organizations
    - Memberships
    - Roles
    - Permissions
    - Milestone 3 / MVP scope
- Compare documented required capabilities against what is marked completed.
- Identify only capabilities that are actually required for Milestone 3.
- Separate findings into:
    - required before Milestone 3 can close
    - explicitly deferred / later scope
    - documentation ambiguity requiring an architectural decision
- Check specifically whether Milestone 3 still requires:
    - Organization lifecycle operations such as suspend, reactivate, or archive
    - Membership listing/read operations
    - Role listing/read operations
    - Role creation/update/deletion
    - permission assignment management
    - User or Membership read endpoints
    - any remaining authentication/IdentityAccess operations
- Do not invent capabilities because they are conventional CRUD operations.
- Check whether the current owner/member rules create any documented invariant gap, including last-owner or administrative-access protection.
- Recommend exactly one next bounded implementation task.

## Rules

- ADRs and canonical module/domain documentation take precedence over assumptions.
- Do not expand MVP scope.
- Do not change `STATUS.md`.
- Do not change `NEXT.md`.
- Do not modify code or documentation.
- Keep the review concise.

## Reporting

Return only:

- Milestone 3 required and still missing
- deferred capabilities
- ambiguities or invariant gaps
- recommended next task

Maximum 25 lines.