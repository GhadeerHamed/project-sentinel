# Current Task

Implement the Event Source authentication foundation required for Event ingestion.

## Required

Follow the reconciled Event module, API, database, and security documentation.

Implement only the minimum foundation required to authenticate an `api_client` Event Source.

## Event Source

Add the framework-independent Event Source domain model required by the canonical contract.

Use only fields defined by the reconciled documentation.

Required behavior:

- source type is `api_client`;
- source belongs to the canonical Organization/Application/Environment hierarchy defined by the Event contract;
- source identity is server-owned;
- source must be active to authenticate;
- source ownership must be tenant-safe;
- source identity must never come from an Event ingestion payload.

Do not add Plugin, Agent, webhook, manual, or internal-platform source types.

## Credential

The MVP supports one bearer credential per Event Source.

Requirements:

- generate the secret server-side using cryptographically secure randomness;
- return the plaintext secret only at issuance time;
- never persist the plaintext secret;
- persist only the canonical secure hash/lookup representation defined by the security documentation;
- credential authentication must resolve the Event Source;
- revoked/inactive credentials or sources must fail authentication;
- credential must not authenticate a source from another Organization;
- use constant-time-safe credential verification where applicable.

Do not log the bearer secret.

Do not expose it through Audit, Outbox, exceptions, or API resources.

## Issuance

Implement the smallest credential issuance capability required by the canonical documentation.

- Use the documented route and authorization permission.
- Do not invent a route or permission if the canonical docs already define them.
- If the exact issuance HTTP contract is genuinely absent, implement the framework-independent issuance/application foundation and report the missing HTTP contract as a blocker rather than inventing one.
- Issuance must be tenant-scoped.
- Audit source/credential issuance without recording the secret.

The response may return the plaintext credential exactly once when canonically required.

## Authentication Adapter

Add a Laravel authentication adapter/middleware for Event Source bearer credentials.

Conceptual flow:

Bearer credential
→ credential lookup/verification
→ active Event Source
→ source hierarchy ownership
→ authenticated EventSourceIdentity

Keep this separate from:

- human session authentication;
- `TenantContext`;
- Membership authorization.

The Event ingestion endpoint will later consume the authenticated Event Source identity.

Do not reuse human `IdentityReference` semantics unless the canonical identity contracts explicitly support non-human Event Sources.

## Persistence

Add only the Event Source and credential persistence required by this task.

Requirements:

- UUIDv7 identifiers;
- Organization/parent ownership according to the reconciled contract;
- source type;
- source status;
- credential hash/lookup data;
- issuance/revocation timestamps where canonical;
- appropriate tenant and credential lookup indexes;
- no plaintext credential column.

Preserve tenant isolation in every lookup.

## Security

Cover at minimum:

- valid active credential authenticates;
- unknown credential fails;
- malformed bearer credential fails;
- revoked credential fails;
- inactive source fails;
- cross-Organization source use fails;
- stored credential is not plaintext;
- plaintext secret appears only in the issuance response;
- secret does not appear in Audit or logs.

Do not use the normal browser session authentication for Event ingestion credentials.

## Excluded

Do not add:

- Event ingestion endpoint;
- Event persistence;
- `event.accepted`;
- batch ingestion;
- credential rotation beyond the canonical MVP requirement;
- multiple credentials per source;
- Plugins;
- Agents;
- Signals;
- Alerts;
- downstream processing.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- Event Source model
- credential format/storage approach
- issuance contract
- authentication flow
- tenant-isolation behavior
- files changed
- tests
- blockers

Maximum 15 lines.