# Implementation Status

## Current Milestone

**Milestone 3 — Identity and Organization**

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

## In Progress

- [ ] Define the last-administrative-access protection invariant.

## Remaining Milestone 3

- [ ] Implement the minimum Role-permission management workflow.
- [ ] Decide whether Organization reactivation is required in Milestone 3.

## Next Recommended Task

Define the canonical invariant that prevents an Organization from losing all effective administrative access before implementing Role-permission mutation.

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
