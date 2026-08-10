# Current Task

Define and reconcile the Event Source issuance HTTP contract.

## Required

This is a documentation and architecture task.

Do not modify application code, migrations, or tests.

Review documentation directly relevant to:

- Event Sources;
- Event ingestion;
- Identity/Authorization;
- API conventions;
- Application/Environment ownership;
- API credential/security handling.

The Event Source authentication foundation is already implemented and must not be redesigned unless documentation proves a conflict.

## Existing Foundation

Preserve:

- Event Source type: `api_client`;
- UUIDv7 Event Source ID;
- active/inactive lifecycle;
- one active credential per Event Source;
- bearer credentials with public credential UUID + secret;
- indexed credential lookup;
- only secret hash persisted;
- plaintext credential returned only at issuance;
- source hierarchy validation;
- authenticated `EventSourceIdentity`.

## Resolve Authorization

Define the human permission required to issue an Event Source.

Requirements:

- use one canonical permission code;
- authorization is based on effective Organization permissions;
- do not authorize by Role name;
- built-in `organization_owner` must receive the permission;
- tenant membership alone must not authorize issuance.

Determine whether canonical naming should be:

- `event_source.create`;
- `event_source.manage`;
- or another already-documented permission.

Do not create multiple permissions unless required.

## Resolve Resource Ownership

The canonical hierarchy is:

Organization
→ Project
→ Application
→ Environment
→ Event Source

Confirm exactly which hierarchy identifiers Event Source persists.

The existing implementation must remain compatible with:

- Organization ownership;
- Project → Application → Environment validation;
- active hierarchy required at issuance.

Do not permit client-controlled Organization ownership.

## Resolve Issuance Route

Define exactly one MVP HTTP route for Event Source issuance.

Prefer consistency with the Registry hierarchy.

Determine whether the canonical route is nested under:

Organization → Project → Application → Environment

The route must make hierarchy ownership explicit and tenant-safe.

Do not add Event Source list/get/update/delete endpoints unless MVP documentation explicitly requires them.

## Request Contract

Define the minimum request body.

Only include fields genuinely required to create an `api_client` Event Source.

Determine whether a human-readable source name is required.

Do not allow the request to control:

- Event Source ID;
- Organization ID;
- hierarchy ownership outside route parameters;
- source type if only `api_client` is supported;
- status;
- credential ID;
- credential secret;
- timestamps.

## Response Contract

The successful issuance response must:

- return the Event Source public representation;
- return the plaintext bearer credential exactly once;
- clearly distinguish the credential from normal reusable resource fields;
- never imply that the plaintext credential can later be retrieved.

Determine canonical success status:

`201 Created`

unless existing API conventions explicitly require otherwise.

## Security

Document:

- session-authenticated human issuance;
- TenantContext requirement;
- effective permission authorization;
- active Project/Application/Environment requirement;
- tenant-safe 404 for invalid hierarchy;
- secret returned exactly once;
- secret excluded from Audit and logs;
- no plaintext persistence;
- no credential retrieval endpoint.

## Audit

Define the canonical Audit action for successful issuance.

Prefer one of:

- `event_source.create`;
- `event_source.issue`;

Use the name consistent with the final capability/permission terminology.

The secret must never appear in Audit metadata.

## Idempotency

Determine whether Event Source issuance requires `Idempotency-Key`.

Apply the existing platform rule for creation operations consistently.

If required, define scope using:

- Organization;
- acting human identity;
- operation;
- key;
- normalized request hash.

Do not invent a second idempotency mechanism.

## Verification

After editing, verify that directly relevant docs agree on:

- permission;
- route;
- hierarchy ownership;
- request fields;
- response;
- one-time credential behavior;
- Audit action;
- idempotency requirement.

## Implementation Sequencing

If the contract is fully resolved:

1. Event Source issuance HTTP vertical slice.
2. Single-event ingestion vertical slice.

Do not start Event ingestion yet.

## Reporting

Return only:

- documents changed
- permission decision
- route decision
- ownership decision
- request contract
- response contract
- Audit decision
- idempotency decision
- remaining conflicts
- recommended implementation task
- blockers

Maximum 15 lines.