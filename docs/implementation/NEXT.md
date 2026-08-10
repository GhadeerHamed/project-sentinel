# Current Task

Implement the Create Environment vertical slice.

## Required

- Follow the reconciled Environment module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use the canonical `environment.create` permission.
- Add it to `CorePermissions` if not already present.
- Ensure the built-in `organization_owner` Role receives `environment.create`.
- Reuse `OrganizationPermissionAuthorizer` through an operation-specific Create Environment Policy.

## Ownership

Canonical hierarchy:

Organization → Project → Application → Environment

Environment persists:

- `organization_id`
- `application_id`

Environment does not persist `project_id`.

The handler must:

- derive Organization from `TenantContext`;
- resolve the route Project within the Organization;
- resolve the route Application within the Organization and Project;
- require both Project and Application to be active;
- reject missing, cross-Organization, cross-Project, or archived parents;
- never accept tenant or parent IDs from the request body.

## Domain

Implement the framework-independent Environment domain foundation required by this slice.

Canonical fields:

- `id`
- `organization_id`
- `application_id`
- `name`
- `slug`
- `environment_type`
- `status`
- `created_by_identity_id`
- `created_at`
- `updated_at`
- nullable `archived_at`
- `version`

Initial status:

`active`

Canonical `environment_type` values:

- `production`
- `staging`
- `testing`
- `development`
- `sandbox`
- `other`

Use UUIDv7 and the existing `ResourceVersion` foundation.

Emit:

`environment.created`

Do not add deferred fields or lifecycle states.

## Application Layer

Add the framework-independent:

- command;
- result;
- Policy;
- handler;
- repository contracts required by this slice.

The handler must:

1. authorize `environment.create`;
2. validate active Project and Application ownership;
3. enforce idempotency;
4. create the Environment through the Domain model;
5. persist it;
6. append Domain Events to Outbox;
7. record Audit action `environment.create`;
8. commit atomically.

Reuse existing shared infrastructure where appropriate:

- `TransactionManager`
- UUIDv7 generator
- Clock
- `TenantScopedOperationRecorder`
- existing creation-idempotency abstraction

## Idempotency

Require:

`Idempotency-Key`

Reuse the existing creation-idempotency infrastructure.

Scope:

- Organization;
- acting identity;
- operation;
- idempotency key;
- normalized request hash.

Required behavior:

- same key + same normalized request replays the original successful result;
- same key + different request conflicts;
- concurrent identical requests create only one Environment.

Do not create a second idempotency implementation.

## Persistence

Add canonical PostgreSQL Environment persistence.

Required:

- native UUID ID;
- Organization foreign key;
- Application foreign key;
- name;
- slug;
- required `environment_type`;
- canonical status;
- creator identity;
- timestamps;
- nullable `archived_at`;
- version;
- unique `(organization_id, application_id, slug)`.

Repository operations must preserve Organization and Application ownership.

Do not persist `project_id`.

## HTTP

Expose:

`POST /api/v1/organizations/{organization}/projects/{project}/applications/{application}/environments`

Require:

- session authentication;
- resolved `TenantContext`;
- `environment.create`;
- `Idempotency-Key`.

Request body:

- `name` — required;
- `slug` — required;
- `environment_type` — required.

Do not accept client-controlled:

- `organization_id`;
- `project_id`;
- `application_id`;
- status;
- version;
- creator identity;
- timestamps;
- archival metadata.

Success:

`201 Created`

Return the canonical Environment representation through a dedicated API Resource/Presenter.

Keep the controller thin.

## Security

- Tenant access alone does not authorize Environment creation.
- Project and Application ownership must be validated before creation.
- Archived Projects or Applications must reject creation.
- Cross-Organization and cross-Project parent references must use tenant-safe not-found behavior.
- `environment_type = production` must not grant or bypass authorization.
- Do not authorize through Role names.
- Suspended Organizations remain blocked by `TenantContext`.

## Tests

Cover at minimum:

- authorized creation;
- unauthorized actor;
- unauthenticated request;
- missing Project;
- cross-Organization Project;
- missing Application;
- Application belonging to another Project;
- archived Project rejects creation;
- archived Application rejects creation;
- canonical active initial status;
- all canonical environment types are accepted;
- invalid environment type is rejected;
- server-owned fields cannot be supplied;
- slug uniqueness is scoped by Organization + Application;
- same slug may exist under different Applications;
- matching idempotency replay;
- mismatched idempotency conflict;
- concurrent identical requests create only one Environment;
- `environment.created` Outbox record;
- `environment.create` Audit record;
- newly created Organization owner receives `environment.create` without manual permission seeding.

## Excluded

Do not add:

- Environment update.
- Environment archive.
- Environment list/get endpoints.
- Environment criticality.
- region.
- settings.
- endpoint/runtime metadata.
- deferred lifecycle states.
- downstream Event/Alert/Incident integration.
- unrelated Milestone 4 functionality.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- Environment domain model
- parent ownership enforcement
- environment-type behavior
- idempotency behavior
- persistence/uniqueness behavior
- owner bootstrap change
- endpoint
- event/audit behavior
- tests
- blockers

Maximum 15 lines.