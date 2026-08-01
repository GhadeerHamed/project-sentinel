# Production Topology

## Purpose

This document defines the initial production infrastructure topology for Project Sentinel.

The topology must remain secure, low-cost, observable, and capable of scaling without changing domain architecture.

---

## Initial Topology

Sentinel should initially run as a modular monolith with separately managed processes:

```text
Internet
↓
Reverse Proxy / Load Balancer
↓
Web Application
↓
PostgreSQL
Redis
Object Storage

Queue Workers
Scheduler
Observability
```

Web, Worker, and scheduler processes use the same immutable application image.

---

## Primary Components

The production environment includes:

* Reverse proxy or managed load balancer.
* Web application process.
* Queue Worker processes.
* scheduler process.
* PostgreSQL.
* Redis.
* object storage.
* secret management.
* monitoring and Log collection.

Optional external services may include:

* Email provider.
* AI provider.
* Search service.
* error-tracking platform.
* Plugin provider APIs.

---

## Network Boundaries

Only the public application entry point should be internet-accessible.

Private services include:

```text
PostgreSQL
Redis
internal Worker endpoints
administrative interfaces
monitoring collectors
```

Inbound access should follow:

```text
Internet
↓
TLS endpoint
↓
Reverse proxy
↓
Web application
```

Database and Redis ports must not be exposed publicly.

---

## Web Application

The Web process handles:

* API traffic.
* authentication.
* administrative UI.
* inbound Webhooks.
* synchronous commands and queries.
* health endpoints.

The Web process must be stateless.

Multiple Web instances may run behind the same load balancer.

---

## Queue Workers

Workers should be logically separated by workload.

Recommended groups:

```text
events
alerts
notifications
workflows
plugins
agents
projections
maintenance
```

The first production release may combine low-volume Queues into fewer Worker processes.

Agent and Plugin workloads should remain separable because their resource usage and failure patterns differ from normal domain jobs.

---

## Scheduler

Exactly one logical scheduler must dispatch scheduled work.

The scheduler should trigger jobs rather than perform long-running work directly.

Scheduled operations include:

* Workflow Timers.
* Plugin synchronization.
* retention evaluation.
* credential expiration checks.
* maintenance tasks.
* monitoring checks.

Scheduler failure must be detectable.

---

## PostgreSQL

PostgreSQL is the authoritative data store.

Production PostgreSQL should provide:

* Automated backups.
* point-in-time recovery.
* encrypted storage.
* TLS connections.
* restricted network access.
* performance monitoring.
* controlled maintenance.

The application should use a least-privilege database role.

Schema migrations may use a separate privileged role.

---

## Redis

Redis supports:

* Queue transport.
* cache.
* rate limiting.
* temporary locks.
* short-lived coordination.

Redis must not contain authoritative domain state.

Where possible, Queue and cache workloads should use separate databases, namespaces, or instances.

---

## Object Storage

Object storage contains:

* Evidence files.
* exports.
* archives.
* large execution artifacts.
* temporary uploads.

Production storage must provide:

* Encryption at rest.
* private access.
* Organization-scoped keys.
* lifecycle rules.
* short-lived signed URLs.
* deletion and retention controls.

The application must not depend on local container storage.

---

## Reverse Proxy

The reverse proxy or load balancer should provide:

* TLS termination.
* request-size limits.
* connection limits.
* timeout enforcement.
* forwarding headers.
* health-based routing.
* basic denial-of-service protection.

Application configuration must define trusted proxies explicitly.

---

## TLS

All external traffic must use HTTPS.

Internal TLS should be used where supported for:

* PostgreSQL.
* Redis.
* object storage.
* provider APIs.
* observability transport.

Certificates must support automated renewal and expiration monitoring.

---

## DNS

Production DNS should point to the public load balancer or reverse proxy.

DNS changes must be:

* Controlled.
* traceable.
* protected by strong authentication.
* reviewed for high-risk changes.

Low time-to-live values may be used temporarily during planned migrations.

---

## Process Placement

For the initial low-cost deployment, these processes may share one host:

```text
Reverse proxy
Web application
Queue Workers
Scheduler
Redis
```

PostgreSQL may also share the host during very early validation, but managed or isolated PostgreSQL is preferred for production customer data.

Logical process separation must remain even when physical infrastructure is shared.

---

## Recommended Early Production Layout

```text
Host or Container Platform
├── reverse-proxy
├── web
├── worker-default
├── worker-agents
├── scheduler
└── redis

Managed or Isolated Services
├── PostgreSQL
├── object storage
├── secret management
└── monitoring
```

This topology minimizes cost while preserving clear scaling boundaries.

---

## High Availability Evolution

As load and revenue grow, Sentinel may introduce:

* Multiple Web nodes.
* multiple Worker nodes.
* managed Redis.
* PostgreSQL failover.
* read replicas.
* redundant schedulers with leader election.
* multi-zone deployment.
* dedicated Search infrastructure.
* regional deployments.

High availability should be added based on measured risk and recovery requirements.

---

## Availability Zones

A later production topology should distribute critical components across failure zones where supported.

At minimum:

* Web instances should not share one failure domain.
* critical Workers should be restartable elsewhere.
* PostgreSQL backups must exist outside the primary host.
* object storage must not depend on the application server.

---

## Service Discovery

Early deployments may use deployment-managed hostnames.

Examples:

```text
postgres.internal
redis.internal
object-storage.internal
```

Application code must use configuration, not hard-coded addresses.

---

## Administrative Access

Production administration should use:

* Strong authentication.
* MFA.
* least-privilege accounts.
* restricted source networks or secure access gateways.
* short-lived access where possible.
* Audit recording.

Direct shell and database access should be exceptional.

---

## Secrets

Production secrets must be injected through an approved secret-management system.

Secrets must not exist in:

* Container images.
* source control.
* deployment Logs.
* command-line arguments.
* shared configuration files.

Each production integration should use dedicated credentials.

---

## Outbound Network Access

Outbound access should be restricted to required destinations where practical.

Expected destinations may include:

* Email providers.
* AI providers.
* Plugin APIs.
* object storage.
* observability endpoints.

Agent and Plugin execution must not receive unrestricted internal network access.

---

## Health and Readiness

The topology must expose:

* Web liveness.
* Web readiness.
* Worker heartbeat.
* Queue backlog.
* scheduler heartbeat.
* PostgreSQL health.
* Redis health.
* storage availability.
* external provider degradation.

Optional provider failure should not automatically make the full platform unavailable.

---

## Observability

Every production component must emit:

* Structured Logs.
* metrics.
* error reports.
* deployment identifiers.
* correlation identifiers.
* health status.

Important topology metrics include:

```text
request rate
request latency
error rate
database connections
database query latency
Redis latency
Queue depth
job age
Worker failures
scheduler delay
storage failures
```

---

## Capacity Controls

Production must define limits for:

* Request body size.
* upload size.
* API rate.
* Queue concurrency.
* Agent execution duration.
* Plugin synchronization size.
* database connections.
* Worker memory.
* export size.

Limits should fail predictably rather than exhaust infrastructure.

---

## Deployment Isolation

Staging and production must not share:

* Databases.
* Redis.
* storage buckets.
* credentials.
* Queue namespaces.
* encryption keys.

Shared infrastructure accounts may be acceptable only when strong Resource isolation is enforced.

---

## Maintenance Operations

Maintenance jobs should run through explicit deployment or Worker commands.

Examples:

* Database migration.
* Search Projection rebuild.
* retention processing.
* archive restoration.
* data backfill.
* credential rotation.

Maintenance operations must preserve Tenant Context, idempotency, and Audit requirements.

---

## Failure Scenarios

The topology must tolerate:

* Web process restart.
* Worker crash.
* scheduler restart.
* temporary Redis interruption.
* provider timeout.
* rolling deployment.
* duplicate Queue delivery.
* temporary object-storage failure.

PostgreSQL loss requires restoration or failover according to recovery procedures.

---

## Prohibited Practices

The following are prohibited:

* Public PostgreSQL or Redis access.
* persistent business data stored inside containers.
* unrestricted administrative accounts.
* production secrets in deployment files.
* mixing staging and production data stores.
* one Worker process with unlimited concurrency.
* long-running work inside the scheduler.
* allowing Agent or Plugin workloads unrestricted network access.
* depending on one application host without external backups.
* treating physical host separation as tenant isolation.

---

## Business Rules

* The initial production architecture is a modular monolith.
* Web, Worker, and scheduler processes remain logically separate.
* PostgreSQL is authoritative.
* Redis supports transient infrastructure concerns only.
* only the public application endpoint is internet-accessible.
* application processes are stateless.
* persistent files use object storage.
* production services use isolated credentials and configuration.
* infrastructure scales by component based on measured demand.
* topology changes must preserve existing domain contracts.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/02-docker-environment.md`
* `docs/deployment/03-ci-cd-pipeline.md`
* `docs/deployment/04-environment-configuration.md`
* `docs/deployment/06-workers-queues.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/deployment/08-backup-recovery.md`
* `docs/deployment/09-disaster-recovery.md`
* `docs/deployment/11-scaling.md`
* `docs/security/00-security-overview.md`
