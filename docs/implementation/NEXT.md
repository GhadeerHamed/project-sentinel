# Current Task

Implement the Create Project vertical slice.

## Required

- Follow the reconciled canonical Project module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the existing resolved `TenantContext`.
- Use the canonical `project.create` permission.
- Add `project.create` to `CorePermissions` only if it is not already defined.
- Ensure the built-in `organization_owner` Role receives `project.create` during Organization bootstrap.
- Reuse `OrganizationPermissionAuthorizer` through an operation-specific Create Project Policy.

## Domain

Implement the framework-independent Project domain foundation required for this slice.

Canonical Project fields:

- `id`
- `organization_id`
- `name`
- `slug`
- optional `description`
- `status`
- `created_by_identity_id`
- `created_at`
- `updated_at`
- `archived_at`
- `version`

Rules:

- initial status is `active`;
- `slug` is unique within the Organization;
- Project settings are outside this slice;
- Project criticality is outside this slice;
- do not introduce unsupported lifecycle states.

Use UUIDv7 and the existing `ResourceVersion` foundation.

Emit:

`project.created`

## Application

Add the framework-independent:

- command;
- result;
- Policy;
- handler;
- repository contracts required by this slice.

The handler must:

1. authorize `project.create`;
2. derive Organization from `TenantContext`;
3. enforce idempotency;
4. create the Project through the Domain model;
5. persist it;
6. append Domain Events to Outbox;
7. record Audit action `project.create`;
8. commit atomically.

Reuse existing shared infrastructure such as:

- `TransactionManager`
- UUIDv7 generator
- Clock
- `TenantScopedOperationRecorder`

where it naturally fits.

## Idempotency

Create Project requires:

`Idempotency-Key`

Idempotency scope:

- Organization;
- acting identity;
- operation;
- idempotency key;
- normalized request hash.

Required behavior:

- same key + same normalized request returns the original successful result;
- same key + different normalized request is rejected;
- concurrent requests with the same idempotency key must not create duplicate Projects;
- idempotency state must participate in the same transactional workflow where required by the canonical contract.

Implement the smallest reusable framework-independent idempotency contract needed by this operation.

Do not build a generic workflow engine.

## Persistence

Add the canonical PostgreSQL Project persistence.

Required:

- native UUID Project ID;
- Organization foreign key;
- unique `(organization_id, slug)`;
- nullable description;
- canonical status;
- creator identity;
- timestamps;
- nullable `archived_at`;
- version.

Repository reads and writes must always preserve Organization ownership.

Do not allow cross-Organization Project access.

## HTTP

Expose:

`POST /api/v1/organizations/{organization}/projects`

Require:

- session authentication;
- resolved `TenantContext`;
- `Idempotency-Key`.

Request body:

- `name` — required;
- `slug` — required;
- `description` — optional.

Do not accept:

- `organization_id`;
- status;
- version;
- creator identity;
- timestamps.

Success:

`201 Created`

Return the canonical Project API representation.

Keep the controller thin.

Use a dedicated API Resource/Presenter rather than inline field-array serialization.

## Security

- Tenant access alone does not authorize Project creation.
- `project.create` must be evaluated through effective Organization permissions.
- Cross-Organization creation must be impossible.
- Do not trust tenant identity from request payload.
- Do not bypass idempotency on retries.
- Do not authorize through Role names.

## Tests

Cover at minimum:

- authorized Project creation;
- unauthorized actor;
- cross-Organization route/access rejection;
- canonical fields and initial active status;
- server-owned fields cannot be supplied by client;
- Organization-scoped slug uniqueness;
- same idempotency key + same request returns the original result;
- same idempotency key + different request is rejected;
- concurrent duplicate requests create only one Project;
- `project.created` Outbox record;
- `project.create` Audit record;
- owner of a newly created Organization has effective `project.create` without manual permission seeding.

## Excluded

Do not add:

- Project update.
- Project archive.
- Project list/get endpoints.
- Project settings.
- Project criticality.
- Application or Environment implementation.
- custom Roles or permissions.
- unrelated Milestone 4 capabilities.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- Project domain model
- idempotency behavior
- persistence/uniqueness behavior
- owner bootstrap change
- endpoint
- event/audit behavior
- tests
- blockers

Maximum 15 lines.