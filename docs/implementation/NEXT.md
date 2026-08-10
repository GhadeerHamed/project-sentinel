# Current Task

Implement the Event Source credential revocation vertical slice.

## Required

Follow the reconciled Event Source, Event API, credential-security, and Milestone 5 documentation.

Implement only credential revocation.

Do not implement Event Source deactivation or credential rotation.

## Permission

Use:

`event_source.credential.revoke`

Requirements:

- add it to `CorePermissions` if not already present;
- ensure new `organization_owner` Roles receive it;
- backfill existing `organization_owner` Roles;
- authorize using effective Organization permissions;
- use an operation-specific credential-revocation Policy;
- reuse `OrganizationPermissionAuthorizer`.

Do not authorize by Role name.

## Authentication

This is a human administrative operation.

Require:

- browser/session authentication;
- authenticated human identity;
- resolved `TenantContext`;
- `event_source.credential.revoke`.

Do not allow Event Source bearer authentication to authorize credential revocation.

## Hierarchy

Validate the complete canonical hierarchy:

Organization
→ Project
→ Application
→ Environment
→ Event Source
→ Credential

Requirements:

- Project belongs to Organization;
- Application belongs to Project;
- Environment belongs to Application;
- Event Source belongs to the Environment hierarchy;
- Credential belongs to the Event Source;
- every lookup is tenant scoped;
- cross-Organization or cross-parent references return tenant-safe not found.

Do not perform global credential lookup followed by ownership validation.

## Lifecycle

Canonical credential transition:

`active` → `revoked`

On successful revocation:

- set `status = revoked`;
- set server-owned `revoked_at`;
- prevent the credential from authenticating immediately.

Already revoked credentials must follow the documented invalid-transition/conflict behavior.

Do not introduce:

- credential reactivation;
- credential rotation;
- credential replacement;
- multiple credentials;
- source deactivation.

## Application Layer

Implement the minimum framework-independent:

- command;
- result where needed;
- Policy;
- handler;
- repository capability required by this slice.

The operation must:

1. authorize the human actor;
2. validate tenant and hierarchy ownership;
3. resolve the active credential;
4. revoke it;
5. persist the transition;
6. record Audit action `event_source.credential.revoke`;
7. commit atomically.

Use the existing Clock for `revoked_at`.

Do not create an Outbox record.

## Persistence

Reuse the existing Event Source credential table.

Do not create a second credential model or table.

Ensure revocation persistence is race-safe.

Two concurrent attempts must never produce inconsistent state.

Use the smallest persistence mechanism consistent with the existing repository architecture.

## HTTP

Expose exactly the canonical nested revocation route defined by the reconciled documentation.

Do not invent a different route.

Require:

- session authentication;
- TenantContext;
- `event_source.credential.revoke`.

Keep the controller thin.

Do not accept client-controlled:

- status;
- revoked_at;
- Organization or parent ownership;
- credential secret.

Return the documented success representation/status.

Do not return the bearer credential or credential hash.

## Authentication Invalidation

After revocation, the existing Event Source bearer middleware must reject that credential.

Verify this through an integration/feature test using the actual authentication path.

Do not add caching that can keep revoked credentials valid.

## Audit

Record:

`event_source.credential.revoke`

Audit must include only safe identifiers/context.

Never include:

- plaintext bearer secret;
- secret hash;
- Authorization header.

A failed revocation must not leave a successful Audit record.

## Security

Verify:

- unauthenticated human rejected;
- unauthorized member rejected;
- Event Source bearer credential cannot revoke itself;
- cross-Organization references return 404;
- cross-Project/Application/Environment/Event Source references return 404;
- credential belonging to another Event Source cannot be revoked;
- plaintext credential is never exposed;
- credential hash is never exposed;
- revoked credential immediately fails Event Source authentication.

## Tests

Cover at minimum:

- authorized revocation;
- owner permission bootstrap;
- existing owner permission backfill;
- unauthorized human;
- unauthenticated request;
- Event Source bearer auth cannot authorize revocation;
- cross-Organization hierarchy;
- wrong Project/Application/Environment ownership;
- credential belonging to another Event Source;
- active → revoked transition;
- `revoked_at` persisted;
- already revoked credential rejected according to canonical semantics;
- revoked credential fails bearer authentication;
- Audit record written;
- Audit contains no secret/hash;
- no Outbox record written;
- persistence failure rolls back Audit/state;
- concurrent revoke attempts remain consistent.

## Excluded

Do not add:

- Event Source deactivation;
- Event Source list/get;
- credential rotation;
- credential replacement;
- credential retrieval;
- multiple credentials;
- List Events;
- Get Event;
- batch ingestion;
- Event processing;
- Signals;
- Alerts.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- route
- hierarchy behavior
- lifecycle behavior
- authentication invalidation
- Audit behavior
- Outbox behavior
- owner permission bootstrap
- files changed
- tests
- blockers

Maximum 15 lines.