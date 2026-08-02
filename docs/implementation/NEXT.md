# Current Task

Replace the insecure Organization creation authorization adapter.

## Required

- Remove `AllowAllOrganizationCreationPolicy`.
- Confirm the canonical Organization-creation rule from existing documentation.
- If every active human User may create an Organization, implement a clearly named policy reflecting that rule.
- If `organization.create` is a platform capability, add the smallest framework-independent capability-checking port.
- Reject non-user, inactive, suspended, disabled, and compromised identities.
- Keep authentication eligibility separate from authorization.
- Add focused policy and handler tests.
- Preserve existing persistence and transaction behavior.

## Excluded

Do not add controllers, routes, middleware, sessions, API tokens, or unrelated use cases.

## Verification

Run targeted tests and `docker compose exec app composer quality`.

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only decisions, files changed, tests, and blockers. Maximum 15 lines.