# Current Task

Implement the Create Application vertical slice.

## Required

- Follow the reconciled canonical Application module, API, database, security, and MVP documentation.
- Use the existing authenticated `IdentityReference`.
- Require the resolved `TenantContext`.
- Use the canonical `application.create` permission.
- Add it to `CorePermissions` if not already present.
- Ensure the built-in `organization_owner` Role receives `application.create`.
- Reuse `OrganizationPermissionAuthorizer` through an operation-specific Create Application Policy.

## Ownership

Canonical hierarchy:

Organization → Project → Application

The handler must:

- derive Organization from `TenantContext`;
- load the parent Project within that Organization;
- require the Project to exist and belong to the resolved Organization;
- require the Project to be active;
- never accept `organization_id` or `project_id` from the request body.

The route Project is authoritative.

## Domain

Implement the framework-independent Application domain foundation required for this slice.

Canonical fields:

- `id`
- `organization_id`
- `project_id`
- `name`
- `slug`
- optional `description`
- `criticality`
- `status`
- `created_by_identity_id`
- `created_at`
- `updated_at`
- nullable `archived_at`
- `version`

Initial status:

`active`

Canonical criticality values:

- `critical`
- `high`
- `medium`
- `low`

Use UUIDv7 and the existing `ResourceVersion` foundation.

Emit:

`application.created`

Do not introduce deferred lifecycle states or metadata.

## Application Layer

Add the framework-independent:

- command;
- result;
- Policy;
- handler;
- repository contracts required by this slice.

The handler must:

1. authorize `application.create`;
2. validate the active parent Project within the tenant;
3. enforce idempotency;
4. create the Application through the Domain model;
5. persist it;
6. append Domain Events to Outbox;
7. record Audit action `application.create`;
8. commit atomically.

Reuse existing shared infrastructure where appropriate:

- `TransactionManager`
- UUIDv7 generator
- Clock
- `TenantScopedOperationRecorder`
- existing idempotency abstraction

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

- same key + same normalized request returns the original successful result;
- same key + different request returns the existing conflict semantics;
- concurrent identical requests create only one Application.

Do not create a second idempotency implementation.

## Persistence

Add canonical PostgreSQL Application persistence.

Required:

- native UUID ID;
- Organization foreign key;
- Project foreign key;
- nullable description;
- required criticality;
- canonical status;
- creator identity;
- timestamps;
- nullable `archived_at`;
- version;
- unique `(organization_id, project_id, slug)`.

Repository operations must preserve Organization and Project ownership.

Cross-Organization or cross-Project access must be impossible.

## HTTP

Expose:

`POST /api/v1/organizations/{organization}/projects/{project}/applications`

Require:

- session authentication;
- resolved `TenantContext`;
- `application.create`;
- `Idempotency-Key`.

Request body:

- `name` — required;
- `slug` — required;
- `description` — optional;
- `criticality` — required.

Do not accept client-controlled:

- `organization_id`;
- `project_id`;
- status;
- version;
- creator identity;
- timestamps;
- archived metadata.

Success:

`201 Created`

Return the canonical Application representation using a dedicated API Resource/Presenter.

Keep the controller thin.

## Security

- Tenant access alone does not authorize Application creation.
- Parent Project lookup must be scoped to the resolved Organization.
- Archived Projects must not accept new Applications.
- Do not authorize through Role names.
- Do not trust parent or tenant IDs from the payload.
- Suspended Organizations remain blocked by existing tenant resolution.

## Tests

Cover at minimum:

- authorized creation;
- unauthorized actor;
- unauthenticated request;
- Project from another Organization is rejected;
- missing Project is rejected;
- archived Project cannot receive a new Application;
- canonical fields and active initial status;
- all four criticality values are accepted;
- invalid criticality is rejected;
- server-owned fields cannot be supplied;
- slug uniqueness is scoped by Organization + Project;
- same slug may exist in different Projects;
- same idempotency key + same request replays the result;
- same key + different request conflicts;
- concurrent identical requests create only one Application;
- `application.created` Outbox record;
- `application.create` Audit record;
- newly created Organization owner has `application.create` without manual permission seeding.

## Excluded

Do not add:

- Application update.
- Application archive.
- Application listing/get endpoints.
- criticality mutation.
- Application settings.
- runtime/framework metadata.
- repository/source metadata.
- Environment implementation.
- unrelated Milestone 4 capabilities.

## Verification

Run targeted tests and:

`docker compose exec app composer quality`

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

- permission used
- Application domain model
- parent Project enforcement
- criticality behavior
- idempotency behavior
- persistence/uniqueness behavior
- owner bootstrap change
- endpoint
- event/audit behavior
- tests
- blockers

Maximum 15 lines.