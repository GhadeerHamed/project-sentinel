# Current Task

Expose the completed Create Organization application slice through an authenticated HTTP endpoint.

## Required

- Follow the existing Organization API documentation and route conventions.
- Require the existing Laravel session authentication.
- Resolve the authenticated `IdentityReference`.
- Validate Organization name and slug using a Form Request.
- Generate server-owned correlation metadata when it is not already supplied by trusted infrastructure.
- Invoke `CreateOrganizationHandler`.
- Return HTTP `201 Created` with:
    - Organization ID.
    - founding Membership ID.
    - initial RoleAssignment ID.
- Map typed application and domain errors to the documented HTTP responses.
- Apply appropriate CSRF and rate-limiting protection.
- Add focused feature tests.

## Rules

- Creating an Organization does not require `TenantContext`.
- Continue using `ActiveHumanUserMayCreateOrganizationPolicy`.
- Keep the controller thin.
- Do not access Eloquent or repositories directly from the controller.
- Do not duplicate handler business logic.

## Excluded

Do not add:

- Organization listing or update endpoints.
- Organization-scoped permission Policies.
- API tokens.
- registration or password reset.
- unrelated IdentityAccess use cases.

## Verification

Run targeted feature tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- route and response contract
- files changed
- error mapping
- test result
- remaining blocker

Maximum 15 lines.