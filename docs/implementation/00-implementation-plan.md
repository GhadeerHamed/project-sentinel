# Implementation Plan

## Purpose

This document defines the order and rules for implementing Project Sentinel.

It converts the completed architecture into incremental production-ready code.

---

## Implementation Goal

The first implementation should deliver a usable operational flow:

```text
Organization
→ Project
→ Application
→ Environment
→ Event
→ Signal
→ Alert
→ Incident
→ Notification
```

Advanced capabilities are introduced only after this core flow is stable.

---

## Core Principles

* Build one deployable modular monolith.
* preserve documented module boundaries.
* keep domain rules independent from Laravel.
* implement tenant isolation from the first migration.
* use PostgreSQL as the authoritative store.
* use UUIDv7 for primary Resource identifiers.
* use explicit commands, queries, Policies, and repositories.
* publish domain events through the Outbox pattern.
* test security and ownership with every module.
* avoid speculative infrastructure and abstraction.

---

## Implementation Layers

Each business module should be implemented through:

```text
Domain
Application
Infrastructure
Laravel Runtime Adapter
Tests
```

### Domain

Contains:

* Entities.
* Value Objects.
* lifecycle rules.
* domain services.
* domain events.
* repository interfaces.

### Application

Contains:

* Commands.
* Queries.
* handlers.
* authorization Policies.
* transaction orchestration.
* DTOs.

### Infrastructure

Contains:

* PostgreSQL repository implementations.
* Eloquent persistence models.
* provider adapters.
* Outbox implementation.
* Search and storage adapters.

### Runtime Adapter

Contains:

* Controllers.
* Form Requests.
* API Resources.
* middleware.
* Queue Jobs.
* scheduled commands.
* dependency bindings.

---

## Foundation First

Before implementing business modules, establish:

* Laravel application.
* Docker Compose.
* PostgreSQL.
* Redis.
* test environment.
* UUIDv7 identifiers.
* Shared Kernel.
* Tenant Context.
* authentication foundation.
* authorization Policy contract.
* transaction abstraction.
* Outbox foundation.
* Audit foundation.
* structured logging.
* CI pipeline.

Business development should not begin on an unstable foundation.

---

## Implementation Milestones

## Milestone 1 — Repository Foundation

Deliver:

* Laravel project initialization.
* Docker development environment.
* Composer and frontend setup.
* PostgreSQL and Redis connectivity.
* coding standards.
* static analysis.
* test suites.
* CI validation.
* initial repository structure.

Exit criteria:

* Application starts locally.
* migrations run.
* tests run.
* CI passes.
* Web, Worker, and scheduler processes start independently.

---

## Milestone 2 — Shared Platform Foundation

Deliver:

* Shared Kernel identifiers.
* UUIDv7 generation and persistence.
* Clock abstraction.
* correlation and causation identifiers.
* Tenant Context.
* common domain errors.
* transaction manager.
* optimistic concurrency primitives.
* Outbox Records and publisher.
* Audit recording contract.

Exit criteria:

* Shared primitives are framework-independent.
* Outbox publication is testable.
* Tenant Context is required by protected operations.
* concurrency conflicts are handled consistently.

---

## Milestone 3 — Identity and Organization

Deliver:

* User and Identity.
* authentication.
* Sessions or API credentials.
* Organization.
* Membership.
* Role and permission assignments.
* Organization creation.
* tenant-resolution middleware.
* authorization foundation.

Exit criteria:

* A User can authenticate.
* a User can create or access an Organization.
* cross-Organization access is rejected.
* suspended Memberships lose access.
* authorization tests cover protected operations.

---

## Milestone 4 — Project and Application Registry

Deliver:

* Project module.
* Application module.
* Environment module.
* lifecycle commands.
* repository implementations.
* APIs.
* tenant-aware listing and retrieval.

Exit criteria:

* Authorized Users can create the hierarchy.
* parent ownership is validated.
* archived Resources reject normal writes.
* optimistic concurrency is enforced.

---

## Milestone 5 — Event Ingestion

Deliver:

* canonical Event model.
* ingestion authentication.
* single and batch ingestion.
* idempotency.
* Event normalization.
* immutable Event storage.
* Event Source credential revocation.
* Event read APIs.

Exit criteria:

* Valid Events are accepted once.
* duplicate ingestion is safe.
* invalid ownership is rejected.
* accepted Events publish Outbox messages.
* source attribution is preserved.
* authorized Event browsing is available.

---

## Milestone 6 — Alert Management

Deliver:

* Outbox publisher before the first `event.accepted` consumer.
* Signal derivation.
* Alert Rules.
* Signal evaluation.
* Alert creation.
* fingerprinting and deduplication.
* Alert occurrences.
* acknowledgement.
* suppression.
* resolution.
* Alert APIs.

Exit criteria:

* Signals can create or update Alerts.
* duplicate Signals do not create uncontrolled Alerts.
* lifecycle transitions are validated.
* Alert history and evidence remain traceable.

---

## Milestone 7 — Incident Management

Deliver:

* Incident creation.
* Incident creation from Alerts.
* lifecycle transitions.
* commander and responder assignment.
* timeline.
* evidence references.
* Incident Actions.
* Incident APIs.

Exit criteria:

* Alerts can be escalated into Incidents.
* Incident timeline is append-only.
* responder and severity changes are authorized.
* resolution and closure are separate operations.

---

## Milestone 8 — Notifications

Deliver:

* Notification requests.
* recipient resolution.
* Channels.
* Templates.
* delivery Workers.
* retries and failure classification.
* delivery history.
* initial email provider adapter.

Exit criteria:

* Alert and Incident events can request Notifications.
* provider failures retry safely.
* delivery remains tenant-scoped.
* source modules do not call providers directly.

---

## Milestone 9 — Search and Operational UI

Deliver:

* Search Projections.
* tenant-aware Search API.
* basic operational dashboard.
* Event, Alert, and Incident views.
* filtering and pagination.
* Worker and Queue visibility.

Exit criteria:

* Operators can discover operational Resources.
* Search is derived and rebuildable.
* Search never authorizes domain operations.
* sensitive fields remain filtered.

---

## Milestone 10 — Knowledge

Deliver:

* Knowledge Drafts.
* review and publication.
* immutable published Versions.
* Incident source references.
* Knowledge Search integration.

Exit criteria:

* Resolved Incidents can produce Knowledge Drafts.
* publication requires authorization.
* published Versions cannot be edited in place.

---

## Milestone 11 — Plugins

Deliver:

* Plugin Definitions and Versions.
* Installations.
* Plugin identities.
* capabilities.
* credential references.
* synchronization execution.
* first supported integration.

Exit criteria:

* One Plugin can ingest useful operational data.
* Plugins cannot access domain tables directly.
* capabilities and tenant scope are enforced.
* failing Installations can be suspended.

---

## Milestone 12 — Agents and Workflows

Deliver:

* Agent Definitions and Versions.
* Agent executions.
* findings and Action proposals.
* approved Tools.
* Workflow Definitions and Versions.
* Workflow Instances.
* approvals and Timers.

Exit criteria:

* Agents provide evidence-based recommendations.
* Agent output is non-authoritative.
* Workflows invoke owning module commands.
* protected Actions require runtime authorization.

---

## Vertical Slice Rule

Each milestone should deliver a complete vertical slice:

```text
Migration
→ Domain
→ Application
→ Infrastructure
→ API
→ Authorization
→ Audit
→ Events
→ Tests
→ Observability
```

A module is not complete when only its database tables or controllers exist.

---

## Per-Capability Definition of Done

Every capability must include:

* Documented ownership.
* domain invariants.
* commands and queries.
* authorization Policy.
* tenant-scoped persistence.
* optimistic concurrency where required.
* domain events.
* Outbox integration.
* Audit behavior.
* API validation.
* error mapping.
* automated tests.
* operational telemetry.
* updated documentation.

---

## Testing Strategy

Required test categories:

```text
Unit tests
Domain behavior tests
Application handler tests
Repository integration tests
API tests
Tenant-isolation tests
Authorization tests
Queue and Outbox tests
Architecture dependency tests
```

Business rules should be testable without booting Laravel where practical.

---

## Migration Strategy

Database work should follow:

```text
Additive migration
→ compatible code
→ optional backfill
→ behavior switch
→ later cleanup
```

Migrations must:

* include `organization_id` for tenant-owned tables.
* use native PostgreSQL `uuid`.
* include required tenant-aware indexes.
* avoid long blocking operations.
* preserve rolling deployment compatibility.

---

## Security Gate

No phase is complete without validating:

* Authentication.
* authorization.
* tenant isolation.
* secret handling.
* sensitive-field filtering.
* Audit requirements.
* Agent and Plugin capability boundaries where relevant.

Cross-tenant access is treated as a critical defect.

---

## Performance Gate

Before optimizing infrastructure:

1. Measure the operation.
2. inspect query plans.
3. fix inefficient queries.
4. add appropriate indexes.
5. bound pagination and batch size.
6. tune Worker concurrency.
7. add caching only where justified.

Microservices and sharding are not initial implementation goals.

---

## Change Control

Implementation that conflicts with existing architecture must result in one of:

* Code corrected to follow the documentation.
* documentation corrected because implementation revealed a valid issue.
* a new ADR for a significant architectural change.

Silent divergence is prohibited.

---

## Initial Delivery Target

The first usable internal release should include:

```text
Authentication
Organization and Membership
Project, Application, and Environment
Event ingestion
basic Signal derivation
basic Alert Rules
Alert lifecycle
Incident lifecycle
email Notification
Audit history
basic operational dashboard
```

Plugins, Agents, advanced Workflows, semantic Search, and multi-region deployment are deferred.

---

## Business Rules

* Foundation and tenant isolation are implemented before operational modules.
* modules are delivered as complete vertical slices.
* domain rules remain independent from Laravel.
* every tenant-owned operation requires Tenant Context.
* every side-effecting asynchronous operation is idempotent.
* authoritative state changes publish through the Outbox pattern.
* security and tests are part of implementation, not later hardening.
* advanced capabilities follow a stable core operational flow.
* architecture changes require explicit documentation or ADR updates.
* implementation proceeds incrementally toward a usable release.

---

## Related Documents

* `docs/implementation/01-repository-structure.md`
* `docs/implementation/02-mvp-roadmap.md`
* `docs/modules/00-module-architecture.md`
* `docs/modules/01-shared-kernel.md`
* `docs/modules/18-laravel-runtime-adapter.md`
* `docs/deployment/01-local-development.md`
* `docs/deployment/03-ci-cd-pipeline.md`
* `docs/security/02-tenant-isolation.md`
* `docs/database/00-data-architecture.md`
