# Current Task

Implement the List Accessible Organizations vertical slice.

## Required

- Follow the canonical Organization, IdentityAccess, API, and MVP documentation.
- Use the existing authenticated human `IdentityReference`.
- Do not require `TenantContext`; this operation spans Organizations and is used before tenant selection.
- Return only Organizations associated with the authenticated User through canonical Membership relationships.
- Determine from canonical documentation which Membership statuses grant list visibility; do not invent behavior.
- Determine from canonical documentation whether suspended Organizations remain visible in the list or are excluded.
- Keep tenant-entry eligibility separate from list visibility.
- Do not use Organization-scoped permission authorization merely to list the caller's own accessible Organizations.
- Add the smallest framework-independent query/handler and repository read contract required.
- Expose the documented authenticated HTTP endpoint.
- Follow existing API pagination and response conventions where applicable.
- Avoid N+1 queries.
- Return only fields allowed by the canonical Organization API representation.
- Add focused application, persistence, isolation, and HTTP tests.

## Security Rules

- A User must never see an Organization with which he has no qualifying Membership.
- Memberships belonging to another identity must not grant visibility.
- Do not accept `organization_id` or identity ID from request input.
- Do not expose Role, permission, credential, or unrelated tenant data.
- Do not create a `TenantContext` as part of this operation.
- Authentication eligibility remains enforced by the existing session-authentication layer.

## Excluded

Do not add:

- Organization switching/session state.
- Organization reactivation.
- Organization archive/delete.
- Membership listing.
- Role listing.
- permission-management endpoints.
- last-owner protection.
- unrelated Organization mutations.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- endpoint selected
- visibility rules selected
- Membership/Organization status behavior
- query/persistence approach
- files changed
- tests
- blockers

Maximum 12 lines.