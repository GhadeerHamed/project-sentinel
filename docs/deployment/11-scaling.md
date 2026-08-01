# Scaling

## Purpose

This document defines how Project Sentinel scales from an early single-server deployment to a larger distributed production platform.

Scaling decisions must follow measured demand rather than anticipated complexity.

---

## Core Principles

* Preserve the modular monolith initially.
* Scale processes independently before creating services.
* PostgreSQL remains authoritative.
* Keep Web and Worker processes stateless.
* Protect tenant fairness.
* Prefer horizontal scaling for application workloads.
* Optimize before partitioning.
* Introduce infrastructure only when its operational value exceeds its cost.

---

## Scaling Dimensions

Sentinel may need to scale for:

```text
API traffic
Event ingestion
Queue throughput
database volume
Search volume
Agent executions
Plugin synchronization
Notifications
file storage
Organization count
```

Each dimension may require a different response.

---

## Scaling Signals

Scaling decisions should use:

* Request rate and latency.
* CPU and memory usage.
* Queue depth.
* oldest job age.
* Worker execution duration.
* database load and connection usage.
* slow-query frequency.
* storage growth.
* provider quotas.
* Organization-level workload distribution.
* error and timeout rates.

Infrastructure should not scale based on Queue depth alone.

---

## Scaling Order

Preferred scaling sequence:

```text
Measure
↓
Remove inefficient behavior
↓
Add indexes and caching
↓
Tune concurrency and limits
↓
Scale processes horizontally
↓
Separate heavy workloads
↓
Scale managed data services
↓
Partition only when necessary
```

Microservices and sharding are late-stage options.

---

## Web Application Scaling

Web instances remain stateless and run behind a load balancer.

Horizontal Web scaling requires:

* Shared PostgreSQL.
* shared Redis.
* shared object storage.
* centralized Session storage or stateless tokens.
* consistent configuration.
* health-based routing.

No request should depend on local instance storage.

---

## Worker Scaling

Worker groups scale independently by workload.

Examples:

```text
Event volume increases
→ scale event Workers

Notification backlog increases
→ scale notification Workers

Agent demand increases
→ scale Agent Workers

Plugin synchronization grows
→ scale Plugin Workers
```

Worker concurrency must respect database and provider capacity.

---

## Workload Isolation

Resource-intensive workloads should use separate Queues and Worker groups.

Priority isolation should include:

* Core Event and Alert processing.
* Notifications.
* Workflows.
* Plugins.
* Agents.
* exports and maintenance.
* Search Projection rebuilds.

A large export or Agent execution must not delay critical Event processing.

---

## Tenant Fairness

One Organization must not consume all platform capacity.

Possible controls include:

* Per-Organization rate limits.
* concurrent-job limits.
* workload quotas.
* weighted scheduling.
* bounded batch sizes.
* plan-based limits.
* dedicated Queues for exceptional workloads.

Fairness controls must remain observable and enforceable.

---

## PostgreSQL Scaling

PostgreSQL should be scaled progressively.

### First Steps

* Correct indexes.
* query-plan analysis.
* bounded pagination.
* reduced N+1 queries.
* shorter transactions.
* connection pooling.
* data retention and archival.
* optimized bulk operations.

### Vertical Scaling

Increase:

* CPU.
* memory.
* storage performance.
* connection capacity.

Vertical scaling is often the simplest early-stage database improvement.

### Read Replicas

Read replicas may support:

* Reporting.
* analytics.
* heavy non-authoritative queries.
* selected Search Projection processing.

Authorization-sensitive or immediately consistent reads should continue using the primary database.

### Partitioning

Large append-heavy tables may later use PostgreSQL partitioning.

Potential candidates:

* Events.
* Audit Records.
* execution history.
* Notification deliveries.

Partitioning should follow proven volume and maintenance needs.

### Sharding

Application-level database sharding is not an initial requirement.

It should be considered only when:

* One PostgreSQL cluster cannot meet measured requirements.
* partitioning and archival are insufficient.
* tenant placement can be managed operationally.
* backup, migration, Search, and reporting implications are understood.

---

## Database Connections

Application scaling must not exhaust PostgreSQL connections.

Controls include:

* Connection pooling.
* bounded Worker concurrency.
* separate process limits.
* query timeouts.
* idle connection management.
* admission control.

Adding application instances without connection planning may reduce reliability.

---

## Redis Scaling

Redis may initially support Queue and cache workloads together.

As demand grows, separate:

```text
Queue Redis
Cache Redis
Rate-limit or coordination Redis
```

This prevents cache eviction or heavy Queue usage from affecting unrelated concerns.

Redis Cluster or managed scaling should be introduced only when a single Redis deployment becomes insufficient.

---

## Cache Strategy

Caching should target measured expensive reads.

Cache entries must remain:

* Tenant-scoped.
* bounded in lifetime.
* invalidatable.
* non-authoritative.
* safe after eviction.

Caching must not hide missing indexes or inefficient queries.

---

## Object Storage Scaling

Object storage should scale independently from application servers.

Controls include:

* Direct authorized uploads where appropriate.
* multipart uploads.
* lifecycle policies.
* archive tiers.
* content-size limits.
* asynchronous processing.
* short-lived signed URLs.

Large files should not pass unnecessarily through Web process memory.

---

## Search Scaling

Search should remain a derived capability.

Initial Search may use PostgreSQL where sufficient.

Dedicated Search infrastructure is justified when requirements include:

* Large full-text indexes.
* complex facets.
* low-latency cross-Resource Search.
* semantic or vector Search.
* independent Search scaling.

Search Projections must remain rebuildable from authoritative data.

---

## Event Ingestion Scaling

High-volume Event ingestion should use:

* Small validated requests.
* batch ingestion.
* idempotency.
* asynchronous normalization.
* bounded payload size.
* rate limits.
* admission control.
* per-Organization quotas.

Ingestion acceptance and downstream processing capacity must be monitored separately.

---

## Agent Scaling

Agent execution is constrained by:

* Provider quotas.
* token or usage cost.
* execution duration.
* Worker memory.
* Tool call volume.
* approval waiting.
* Organization budgets.

Agent scaling should use:

* Dedicated Workers.
* bounded concurrency.
* execution timeouts.
* per-Organization budgets.
* provider rate limiting.
* model routing where approved.

Uncontrolled horizontal scaling may increase cost without improving reliability.

---

## Plugin Scaling

Plugin workloads vary by provider.

Scaling controls include:

* Separate Queues.
* provider-specific rate limits.
* synchronization checkpoints.
* bounded page sizes.
* incremental synchronization.
* per-Installation concurrency limits.
* circuit breakers.

One failing provider must not block other Plugin types.

---

## Notification Scaling

Notification delivery should use:

* Dedicated Workers.
* provider quotas.
* batch delivery where supported.
* retry with backoff.
* per-Organization limits.
* delivery status tracking.

Critical operational Notifications may use higher-priority Queues than informational messages.

---

## Scheduler Scaling

The scheduler should remain logically singular.

At higher availability levels, multiple scheduler instances may run with:

* Leader election.
* distributed locking.
* idempotent dispatch.

Scheduled work must never rely solely on one unmonitored process.

---

## Multi-Region Scaling

Multi-region deployment is not an initial requirement.

It may later address:

* Data residency.
* global latency.
* regional availability.
* customer isolation.

Multi-region architecture requires explicit decisions for:

* Tenant placement.
* database ownership.
* replication.
* failover.
* Search.
* object storage.
* identity.
* background processing.
* consistency.

Active-active write regions should not be introduced without a clear business requirement.

---

## Service Extraction

A module may become an independent service when it has:

* Distinct scaling characteristics.
* clear data ownership.
* stable contracts.
* independent deployment value.
* operational ownership.
* sufficient monitoring and failure isolation.

Possible later candidates:

* Event ingestion.
* Notifications.
* Search.
* Agent execution.
* Plugin execution.

Service extraction must not split unclear domain ownership.

---

## Capacity Planning

Capacity reviews should estimate:

```text
Organizations
Users
Events per second
Events retained
API requests per second
Queue jobs per second
Concurrent Agent executions
Plugin synchronization volume
Notification deliveries
database growth
object-storage growth
```

Forecasts should be compared with measured production usage.

---

## Load Testing

Load testing should cover:

* API reads and writes.
* Event batch ingestion.
* Event-to-Alert latency.
* Queue backlog recovery.
* concurrent Agent executions.
* Plugin synchronization.
* Search.
* large exports.
* database connection pressure.

Tests should identify the first constrained dependency rather than only report maximum request rate.

---

## Backpressure

When downstream capacity is exhausted, Sentinel should degrade predictably.

Possible controls:

* Rate limiting.
* request rejection.
* Queue admission limits.
* delayed low-priority work.
* reduced batch size.
* disabled non-critical features.
* per-Organization throttling.

The platform must not accept unlimited work it cannot process within defined limits.

---

## Autoscaling

Autoscaling may use:

* CPU and memory.
* request concurrency.
* Queue depth.
* oldest job age.
* execution latency.

Autoscaling must define:

* Minimum and maximum instances.
* stabilization periods.
* cooldown periods.
* database connection impact.
* provider quota impact.

Agent autoscaling must also consider cost.

---

## Scaling Verification

Every scaling change should verify:

* Performance improvement.
* error rates.
* database impact.
* Queue behavior.
* tenant fairness.
* cost impact.
* rollback behavior.
* observability coverage.

More infrastructure is not proof of higher capacity.

---

## Prohibited Practices

The following are prohibited:

* Introducing microservices without a measured need.
* scaling Workers without database-capacity review.
* using caching instead of fixing unsafe queries.
* allowing one Organization unlimited capacity.
* using Redis as authoritative storage.
* adding read replicas for writes requiring immediate consistency.
* sharding before indexing, retention, and partitioning are insufficient.
* running heavy workloads on critical Queues.
* autoscaling without upper limits.
* scaling Agent execution without budget controls.

---

## Business Rules

* Sentinel begins as a modular monolith.
* Web and Worker processes scale independently.
* PostgreSQL optimization precedes sharding.
* workloads are isolated according to operational characteristics.
* tenant fairness is enforced.
* asynchronous capacity uses Queue depth and job-age measurements.
* Search remains derived and rebuildable.
* Agent scaling includes cost and provider constraints.
* service extraction requires clear ownership and independent value.
* scaling changes are based on measurements and verified outcomes.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/06-workers-queues.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/deployment/08-backup-recovery.md`
* `docs/deployment/09-disaster-recovery.md`
* `docs/deployment/10-release-strategy.md`
* `docs/architecture/09-scaling-strategy.md`
* `docs/database/16-indexing-strategy.md`
