# Implementation Status

## Current Milestone

**Milestone 5 — Event Ingestion**

## Completed Capabilities

- [x] Laravel application initialization.
- [x] Docker development environment.
- [x] Nginx and PHP-FPM.
- [x] PostgreSQL integration.
- [x] Redis integration.
- [x] Queue Worker.
- [x] Scheduler.
- [x] GitHub Actions CI.
- [x] Laravel Pint.
- [x] Larastan and PHPStan.
- [x] Composer quality scripts.
- [x] UUIDv7 Resource ID foundation.
- [x] UUIDv7 generator contract.
- [x] Laravel UUIDv7 generator adapter.
- [x] UUIDv7 service-container binding and tests.
- [x] Clock contract.
- [x] UTC system-clock adapter.
- [x] Clock service-container binding and tests.
- [x] Correlation identifier.
- [x] Causation identifier.
- [x] Identity Reference.
- [x] Tenant Context.
- [x] Domain Event contract.
- [x] Resource Version.
- [x] Optimistic concurrency primitives.
- [x] Transaction Manager contract.
- [x] Outbox message foundation.
- [x] Common domain and application errors.
- [x] Audit-recording contract.
- [x] Identity and Organization domain foundations.
- [x] Create Organization application slice with founding active Membership, owner-role provisioning, `role.assigned` event, Outbox, and Audit.
- [x] PostgreSQL persistence for the Create Organization slice, including repositories, transactions, Outbox, Audit, and integration tests.
- [x] Secure Organization creation authorization through `ActiveHumanUserMayCreateOrganizationPolicy`.
- [x] Laravel password-based human User session authentication backed by `identity_users` and hashed credentials.
- [x] Tenant resolution and Membership-based access foundation, including request-scoped `TenantContext` injection.
- [x] Create Organization HTTP endpoint with session authentication, CSRF, rate limiting, validation, correlation metadata, and documented API error mapping.
- [x] Centralized API exception rendering through `ApiExceptionRenderer`, with generic typed-error mappings and consistent optimistic-concurrency responses.
- [x] Update Organization application slice with Organization-scoped authorization, optimistic concurrency, event/Outbox, Audit, persistence, and authenticated HTTP endpoint.
- [x] Invite Membership vertical slice with Organization-scoped authorization, invited lifecycle state, event/Outbox, Audit, persistence, and authenticated HTTP endpoint.
- [x] Suspend Membership vertical slice with Organization-scoped authorization, optimistic concurrency, event/Outbox, Audit, persistence, and authenticated HTTP endpoint.
- [x] Reactivate Membership vertical slice with Organization-scoped authorization, optimistic concurrency, event/Outbox, Audit, persistence, and authenticated HTTP endpoint.
- [x] Accept Membership Invitation vertical slice with authenticated ownership verification, explicit `invited` → `active` lifecycle transition, optimistic concurrency, event/Outbox, Audit, persistence, and authenticated HTTP endpoint.
- [x] Shared tenant-scoped operation recorder for identical Outbox and Audit recording across membership invitation, suspension, reactivation, and organization update handlers.
- [x] Remove Membership vertical slice with Organization-scoped authorization, soft-removal lifecycle transition, optimistic concurrency, event/Outbox, Audit, persistence, and authenticated HTTP endpoint.
- [x] Shared Organization permission authorizer for active Membership and effective-permission evaluation across membership-management and organization-update policies.
- [x] Core permission catalog for the canonical Organization update and membership-management authorization codes.
- [x] Assign Role vertical slice with tenant-scoped authorization, Organization-owned role and Membership validation, duplicate prevention, role-assigned event/Outbox, Audit, persistence, and authenticated HTTP endpoint.
- [x] Revoke Role vertical slice with tenant-scoped authorization, soft-revocation lifecycle, role-revoked event/Outbox, Audit, persistence, and authenticated HTTP endpoint.
- [x] Suspend Organization vertical slice with tenant-scoped authorization, active-to-suspended lifecycle enforcement, optimistic concurrency, event/Outbox, Audit, persistence, and tenant-status enforcement.
- [x] List Accessible Organizations vertical slice using an application read model and dedicated API resource, with active-Membership visibility, suspended Organization status visibility, optimized projection queries, and no TenantContext requirement.
- [x] Bootstrap the built-in `organization_owner` Role with the current Core administrative permissions during Organization creation.
- [x] Administrative-access protection invariant preserving an active human identity with effective `organization.member.manage`, including PostgreSQL Organization-row serialization for access-reducing Membership and Role-assignment mutations.
- [x] Update Role Permissions vertical slice with Core-catalog validation, tenant-scoped authorization, soft grant revocation/restoration, administrative-recovery protection, Outbox, Audit, and authenticated HTTP API.
- [x] Milestone 3 completion review completed; Organization reactivation explicitly deferred because it is not required by the MVP scope.
- [x] Project Registry documentation reconciliation with canonical Milestone 4 naming, Project fields, MVP lifecycle, and Create Project contract.
- [x] Create Project vertical slice with `project.create` authorization, canonical Project domain model, transactional idempotency, PostgreSQL persistence, owner permission bootstrap, `project.created` Outbox event, Audit, and authenticated HTTP endpoint.
- [x] List Projects vertical slice with `project.read` authorization, active-by-default archived visibility, tenant-scoped PostgreSQL read projection, and authenticated HTTP endpoint.
- [x] Get Project vertical slice with `project.read` authorization, tenant-scoped projection lookup, archived historical visibility, dedicated read model/resource, and authenticated HTTP endpoint.
- [x] Archive Project vertical slice with `project.archive` authorization, canonical active-to-archived lifecycle, optimistic concurrency, Outbox, Audit, and authenticated HTTP endpoint.
- [x] Application Registry documentation reconciled around the canonical Organization → Project → Application hierarchy, required criticality, slug uniqueness, MVP lifecycle, `application.create`, idempotency, and Create Application API contract.
- [x] Create Application vertical slice with `application.create` authorization, active tenant-scoped Project validation, transactional idempotency, PostgreSQL persistence, owner permission bootstrap, `application.created` Outbox event, Audit, and authenticated HTTP endpoint.
- [x] List Applications vertical slice with `application.read` authorization, tenant-scoped Project validation, active-by-default archived visibility, PostgreSQL read projection, cursor pagination, and authenticated Project-scoped HTTP endpoint.
- [x] Get Application vertical slice with `application.read` authorization, tenant-scoped Project and Application ownership enforcement, historical archived visibility, PostgreSQL read projection, and authenticated Project-scoped HTTP endpoint.
- [x] Archive Application vertical slice with `application.archive` authorization, canonical active-to-archived lifecycle, optimistic concurrency, Outbox, Audit, PostgreSQL persistence, owner-permission backfill, and authenticated HTTP endpoint.
- [x] Environment Registry documentation reconciled around canonical ownership, `environment_type`, slug uniqueness, MVP lifecycle, `environment.create`, idempotency, nested routes, and historical read semantics.
- [x] Create Environment vertical slice with `environment.create` authorization, active Project and Application ownership validation, transactional idempotency, PostgreSQL persistence, owner permission bootstrap, `environment.created` Outbox event, Audit, and authenticated HTTP endpoint.
- [x] List Environments vertical slice with `environment.read` authorization, Project and Application ownership validation, active-by-default archived visibility, PostgreSQL read projection, cursor pagination, and authenticated HTTP endpoint.
- [x] Get Environment vertical slice with `environment.read` authorization, tenant-scoped Project, Application, and Environment projection lookups, historical archived visibility, and authenticated nested HTTP endpoint.
- [x] Archive Environment vertical slice with `environment.archive` authorization, canonical active-to-archived lifecycle, optimistic concurrency, Outbox, Audit, PostgreSQL persistence, owner-permission backfill, and authenticated nested HTTP endpoint.
- [x] Milestone 4 — Project and Application Registry completed, including final reconciliation of Project and Application API/module contracts and Event Ingestion's current Environment assumptions.
- [x] Event Ingestion contract reconciled around immutable canonical Events, active hierarchy validation, authenticated `api_client` Event Sources, schema version `1`, bounded JSON payloads, independent idempotency/deduplication, and transactional `event.accepted` publication.

## Milestone 3 Deferred

- [ ] Organization reactivation (`suspended` → `active`) — documented complete-platform capability, deferred beyond MVP Milestone 3.
- [ ] Ownership transfer / distinct business-ownership invariant — deferred.
- [ ] Custom Role CRUD and broader Role/Permission listing — deferred.

## Milestone 4 Deferred

- [ ] Project and Application profile, metadata, settings, ownership, and scoped-access capabilities.
- [ ] Project and Application lifecycle transitions other than `active` → `archived`, including suspension, reactivation, deletion, and restoration.
- [ ] Application criticality mutation and Environment settings, criticality, and advanced lifecycle capabilities.

## Current Milestone

**Milestone 5 — Event Ingestion**

## Completed Milestone 5 Foundation

- [x] Event Ingestion contract reconciliation: canonical immutable Event envelope, active hierarchy, `api_client` source credentials, schema/type/payload rules, distinct idempotency and source duplicate handling, `202 Accepted`, and transactional `event.accepted` publication.
- [x] Event Source issuance contract reconciliation: `event_source.create` authorization, nested active-hierarchy route, server-owned fields, one-time credential response, Audit actions, and idempotency without secret replay.
- [x] Event Source issuance HTTP vertical slice with `event_source.create` authorization, owner-permission bootstrap/backfill, nested session-authenticated route, active hierarchy validation, idempotent one-time credential issuance, Audit, and focused coverage.
- [x] Single Event ingestion vertical slice with Event Source bearer authentication, active hierarchy validation, immutable canonical Event persistence, independent idempotency and source-duplicate safety, transactional `event.accepted` Outbox publication, and focused coverage.
- [x] Remaining Milestone 5 contract reconciliation: credential-only revocation, source-derived ingestion hierarchy, bearer-only authentication, Event reads, atomic bounded batches, and Outbox publisher boundary.
- [x] Event Source credential revocation vertical slice with effective-permission authorization, tenant-safe hierarchy validation, atomic `active` → `revoked` persistence, secret-free Audit, and immediate bearer invalidation.
- [x] List Events vertical slice with human `event.read` authorization, tenant-scoped public projection, canonical filters, and `occurred_at DESC, id DESC` cursor pagination.
- [x] Get Event vertical slice with human `event.read` authorization, tenant-scoped detail projection, historical visibility, explicit payload-and-attributes Policy, and authenticated HTTP endpoint.

## In Progress

- [x] Batch Event Ingestion — Part 2B2A: independently booted PostgreSQL worker-process harness with deterministic pipe barrier and structured results.
- [ ] Batch Event Ingestion — Part 2B2B: deterministic batch race verification.

## Remaining Milestone 5

- [ ] Batch Event Ingestion — Part 2B2B: deterministic batch race verification.
- [ ] Milestone 5 final completion review.

## Known Verification Gap

- [ ] Batch concurrency hardening is implemented, but deterministic multi-process PostgreSQL verification remains pending.

## Next Recommended Task

Build a small reusable multi-process PostgreSQL concurrency test harness that boots independent Laravel processes and provides deterministic synchronization without using the quarantined forked-PDO approach.

## Status Update Rules

After completing a capability:

1. Run targeted tests.
2. Run `docker compose exec app composer quality`.
3. Mark the capability completed only when all checks pass.
4. Move the next capability to `In Progress`.
5. Update `Next Recommended Task`.
6. Keep this file concise.
7. Omit temporary debugging details.
8. Do not modify `AGENTS.md` unless a permanent rule changed.

Never claim a capability is completed without confirming the implementation and tests.
