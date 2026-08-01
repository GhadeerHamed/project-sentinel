# Deployment Overview

## Purpose

This document defines the initial deployment model for Project Sentinel.

The deployment must support:

* Low-cost development.
* secure production operation.
* background workers.
* scheduled tasks.
* database reliability.
* horizontal scaling.
* observable releases.
* future provider portability.

---

## Initial Deployment Model

Sentinel should begin as a modular monolith with separate runtime processes:

```text
Web Application
Queue Workers
Scheduler
PostgreSQL
Redis
Object Storage
```

All processes may use the same application codebase while scaling independently.

Microservices are not required for the initial product.

---

## Runtime Components

### Web Application

Handles:

* Public API requests.
* administrative UI.
* authentication.
* synchronous commands and queries.
* webhook reception.

The web runtime should remain stateless.

### Queue Workers

Handle:

* Event processing.
* Alert evaluation.
* Notifications.
* Agent executions.
* Plugin synchronization.
* Workflow Steps.
* Search Projections.
* Outbox publishing.
* retention jobs.

Worker groups should be independently scalable.

### Scheduler

Triggers:

* Due Workflow Timers.
* scheduled synchronization.
* credential-expiration checks.
* retention evaluation.
* health checks.
* maintenance jobs.

Only one logical scheduler execution should process each scheduled task.

---

## Data Services

### PostgreSQL

PostgreSQL is the authoritative relational database.

It stores:

* Domain state.
* history.
* Outbox messages.
* Audit Records.
* configuration.
* execution metadata.

### Redis

Redis may support:

* Queues.
* cache.
* rate limiting.
* short-lived locks.
* temporary coordination.

Redis must not become the authoritative store for business state.

### Object Storage

Object storage holds:

* Evidence files.
* exports.
* archive packages.
* large execution artifacts.
* temporary uploads.

Files must remain encrypted and Organization-scoped.

---

## Environments

Required deployment Environments:

* `local`
* `testing`
* `staging`
* `production`

Each Environment must use separate:

* Databases.
* credentials.
* queues.
* caches.
* storage paths.
* provider configuration.

Production data and credentials must not be used in lower Environments.

---

## Containerization

Application processes should use container images.

Each release should produce one immutable image that can run as:

* Web process.
* Worker process.
* scheduler process.
* maintenance command.

Runtime behavior is selected through the container command.

---

## Configuration

Configuration must come from:

* Environment variables.
* deployment configuration.
* approved secret management.

Configuration must not be hard-coded into images.

Tenant credentials must remain in the secret-management boundary, not general deployment variables.

---

## Stateless Application Design

Web and Worker instances should not rely on local persistent storage.

Persistent state belongs in:

* PostgreSQL.
* Redis where appropriate.
* object storage.
* approved external providers.

Temporary local files must be disposable.

---

## Release Flow

A production release should follow:

```text
Build
↓
Test
↓
Security checks
↓
Create immutable image
↓
Run database migrations
↓
Deploy application processes
↓
Verify health
↓
Monitor release
```

Deployments must be traceable to:

* Source commit.
* build.
* image digest.
* migration Version.
* deployment timestamp.

---

## Database Migrations

Migrations must be:

* Version-controlled.
* reviewed.
* forward-compatible during rolling deployment.
* tested against production-like data.
* safe for existing records.

Large indexes and data migrations should use separate controlled operations.

Application deployment must not depend on long blocking migrations.

---

## Health Checks

Each process should expose appropriate health information.

### Liveness

Confirms the process is running.

### Readiness

Confirms the process can safely accept work.

Readiness may validate:

* Database connectivity.
* Redis connectivity.
* required configuration.
* critical dependency availability.

Optional external providers should not always make the whole application unready.

---

## Scaling

Scale components independently:

* Web instances by request load.
* ingestion workers by Event volume.
* notification workers by delivery backlog.
* Agent workers by execution demand.
* Plugin workers by synchronization demand.
* Workflow workers by pending Steps.

Scaling decisions should use queue depth, latency, and resource usage.

---

## Failure Handling

The deployment must tolerate:

* Process restart.
* temporary provider failure.
* duplicate message delivery.
* Worker crash.
* Redis interruption.
* rolling application deployment.

Authoritative writes must remain protected through:

* Database transactions.
* idempotency.
* optimistic concurrency.
* Outbox processing.
* safe retries.

---

## Deployment Security

Production deployment requires:

* TLS.
* private database and Redis access.
* least-privilege workload identities.
* encrypted storage.
* managed secrets.
* restricted administrative access.
* image and dependency scanning.
* Audit of privileged changes.

Production database access should not be publicly exposed.

---

## Backups

Production PostgreSQL requires:

* Automated backups.
* point-in-time recovery.
* encrypted backup storage.
* restoration testing.
* documented recovery objectives.

Object storage and secret-management recovery must also be considered.

---

## Observability

Each deployment should provide:

* Structured Logs.
* metrics.
* distributed traces where practical.
* queue-depth monitoring.
* error reporting.
* health dashboards.
* deployment markers.

Telemetry must include request and correlation identifiers without exposing secrets.

---

## Initial Low-Cost Option

For early development and MVP validation, Sentinel may run on:

* One small application server.
* managed or self-hosted PostgreSQL.
* Redis on the same host or a low-cost managed service.
* compatible object storage.
* multiple application processes managed by containers.

The architecture must still preserve separation between Web, Worker, and scheduler responsibilities.

---

## Evolution Path

Sentinel may later introduce:

* Multiple application nodes.
* managed PostgreSQL with replicas.
* dedicated Redis.
* separate Worker pools.
* container orchestration.
* regional deployments.
* dedicated Search infrastructure.
* specialized Agent execution infrastructure.

These changes should not require redesigning domain contracts.

---

## Business Rules

* The initial system is a modular monolith.
* Web, Worker, and scheduler processes scale independently.
* PostgreSQL is authoritative.
* Redis is not authoritative business storage.
* Application processes remain stateless.
* Releases use immutable artifacts.
* migrations are safe for rolling deployment.
* production credentials remain outside source code and images.
* deployments are observable and traceable.
* infrastructure evolution must not change domain ownership.

---

## Related Documents

- `docs/deployment/01-local-development.md`
- `docs/deployment/02-docker-environment.md`
- `docs/deployment/03-ci-cd-pipeline.md`
- `docs/deployment/04-environment-configuration.md`
- `docs/deployment/05-production-topology.md`
- `docs/deployment/06-workers-queues.md`
- `docs/deployment/07-monitoring-observability.md`
- `docs/deployment/08-backup-recovery.md`
- `docs/deployment/09-disaster-recovery.md`
- `docs/deployment/10-release-strategy.md`
- `docs/deployment/11-scaling.md`
- `docs/architecture/09-scaling-strategy.md`
