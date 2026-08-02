# Current Task

Implement Laravel authentication for human Users.

## Required

- Confirm the documented authentication method before coding.
- Authenticate against the existing `identity_users` persistence model.
- Keep password credentials and hashes out of the Domain Entity.
- Add a Laravel `Authenticatable` adapter mapped to `identity_users`.
- Resolve an authenticated User into the existing `IdentityReference`.
- Reject suspended, disabled, and compromised Users.
- Implement session login, logout, and authentication tests.
- Keep authentication separate from Organization Membership authorization.

## Excluded

Do not add:

- tenant-resolution middleware
- Organization authorization
- API tokens
- registration
- password reset
- social login
- controllers unrelated to authentication
- changes to the Create Organization workflow

## Verification

Run targeted authentication tests and:

```bash
docker compose exec app composer quality
```

Update STATUS.md; do not modify NEXT.md.

Reporting

Return only:

authentication model selected
files changed
security behavior
test result
blocker

Maximum 15 lines.