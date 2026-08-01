# Workers and Queues

## Purpose

This document defines how Project Sentinel executes asynchronous work through Queues and Worker processes.

Asynchronous processing must remain tenant-aware, idempotent, observable, and safe to retry.

---

## Core Principles

* PostgreSQL remains authoritative.
* Queues transport work; they do not own domain state.
* Every job carries Organization context.
* Jobs must be safe to retry.
* Workloads are separated by operational characteristics.
* Failed jobs remain inspectable and recoverable.
* Worker deployment scales independently from Web traffic.

---

## Asynchronous Workloads

Queues are appropriate for:

* Event processing.
* Signal derivation.
* Alert evaluation.
* Incident automation.
* Notifications.
* Workflow Steps.
* Plugin synchronization.
* Agent execution.
* Search Projection updates.
* Outbox publishing.
* retention and archival.
* exports and large imports.

Simple operations that must complete before responding should remain synchronous.

---

## Queue Groups

Recommended logical Queues:

```text
outbox
events
alerts
incidents
notifications
workflows
plugins
agents
projections
maintenance
```

The MVP may combine low-volume Queues into fewer Worker groups.

Queue names must remain stable because deployment and monitoring depend on them.

---

## Worker Groups

Recommended Worker groups:

```text
worker-core
├── outbox
├── events
├── alerts
└── incidents

worker-notifications
└── notifications

worker-workflows
└── workflows

worker-integrations
└── plugins

worker-agents
└── agents

worker-projections
└── projections

worker-maintenance
└── maintenance
```

Agent workloads should remain isolated due to longer execution times, cost, and external provider dependence.

---

## Job Envelope

Every tenant-owned job should include:

```text
job_id
job_type
organization_id
acting_identity_id
acting_identity_type
resource_type
resource_id
correlation_id
causation_id
attempt
created_at
```

Jobs requiring a specific Resource Version should include that Version explicitly.

Sensitive payloads should not be embedded when a Resource reference is sufficient.

---

## Tenant Context

Workers must establish trusted Tenant Context before loading tenant-owned data.

A Worker must reject a job when:

* `organization_id` is missing.
* the target Resource belongs to another Organization.
* the acting identity is invalid.
* the required capability has been revoked.
* the job context conflicts with current domain ownership.

Queue separation alone does not provide tenant isolation.

---

## Job Payloads

Job payloads should contain references and immutable facts rather than complete domain objects.

Preferred:

```json
{
  "organization_id": "019...",
  "incident_id": "019...",
  "expected_version": 12,
  "correlation_id": "019..."
}
```

Avoid:

```json
{
  "incident": {
    "entire": "serialized domain object"
  }
}
```

Workers should reload authoritative state before execution.

---

## Idempotency

Every job that can cause side effects must be idempotent.

Possible mechanisms include:

* Unique operation identifiers.
* processed-message records.
* database uniqueness constraints.
* provider idempotency keys.
* current-state validation.
* compare-and-set updates.

Duplicate delivery must not create duplicate authoritative outcomes.

---

## Transactions

A job should use a database transaction when multiple authoritative changes must succeed together.

External network calls should not normally remain inside long database transactions.

Preferred pattern:

```text
Load and validate state
↓
Commit authoritative transition
↓
Write Outbox message
↓
Perform external delivery asynchronously
```

---

## Outbox Processing

Domain transactions write Outbox Records in the same PostgreSQL transaction as state changes.

Outbox Workers then:

1. Claim unpublished Records.
2. publish the message.
3. mark successful publication.
4. retry transient failures.
5. preserve publication metadata.

Outbox processing must tolerate duplicate publication.

---

## Job Claiming

Workers should claim work using mechanisms that prevent simultaneous processing of the same Queue item.

Database-backed maintenance jobs may use:

```sql
FOR UPDATE SKIP LOCKED
```

Redis Queue visibility and reservation timeouts must exceed expected execution time.

Long-running jobs should renew ownership or use workload-specific timeouts.

---

## Retry Policy

Retries are appropriate for transient failures such as:

* Provider timeouts.
* temporary network failure.
* rate limiting.
* dependency unavailability.
* lock contention.

Retries are not appropriate for:

* Validation failure.
* authorization denial.
* tenant mismatch.
* unsupported configuration.
* permanently revoked credentials.
* invalid Resource state.

Permanent failures should fail immediately.

---

## Backoff

Retries should use bounded exponential backoff with jitter.

Example:

```text
30 seconds
2 minutes
10 minutes
30 minutes
```

Maximum attempts and retry duration must be defined per job type.

Immediate unlimited retries are prohibited.

---

## Execution Timeouts

Every job type must define a maximum execution duration.

Examples:

```text
Outbox publishing: short
Notification delivery: short
Plugin synchronization: medium
Agent execution: long but bounded
Export generation: workload-specific
```

A timed-out job must fail predictably and preserve enough state for safe retry.

---

## Failed Jobs

Failed jobs must record:

```text
job_id
job_type
organization_id
queue
attempts
failure_class
safe_failure_message
failed_at
correlation_id
```

Raw credentials, confidential payloads, and unrestricted provider responses must not be stored.

Failed jobs should remain searchable by authorized operators.

---

## Dead-Letter Handling

Jobs exceeding retry limits move to failed-job or dead-letter storage.

Dead-letter handling must support:

* Inspection.
* classification.
* retry after correction.
* discard with reason.
* escalation to an Alert or Incident.

Critical jobs must not disappear silently.

---

## Manual Retry

Manual retry requires:

* Authorized operator.
* current Tenant Context.
* confirmation that the cause is resolved.
* preserved idempotency key.
* Audit recording for sensitive operations.

Retrying must not replace the original job identity without traceability.

---

## Ordering

Strict global ordering is not required.

Ordering should be enforced only where business correctness requires it.

Possible approaches:

* Per-Resource sequence numbers.
* optimistic concurrency.
* partitioning by Resource identifier.
* stale-message rejection.
* ordered Workflow Step execution.

A later message must not overwrite newer authoritative state.

---

## Concurrency

Concurrency should be configured per Worker group.

High concurrency may be suitable for:

* Notifications.
* small Projection updates.
* independent provider calls.

Lower concurrency may be required for:

* Agent executions.
* large imports.
* resource-intensive exports.
* rate-limited Plugins.

Concurrency must respect database connection and provider limits.

---

## Rate Limiting

Workers must respect:

* Provider rate limits.
* Organization plan limits.
* integration-specific quotas.
* Agent budget limits.
* Notification delivery limits.

Rate-limited jobs should be delayed rather than repeatedly failed.

One Organization must not consume all Worker capacity.

---

## Fairness

The Queue system should prevent a high-volume Organization from starving others.

Possible controls include:

* Per-Organization concurrency.
* weighted scheduling.
* workload partitioning.
* rate limits.
* admission control.
* dedicated Queues for exceptional workloads.

Fairness controls may evolve as usage grows.

---

## Scheduler Responsibilities

The scheduler should dispatch jobs for:

* Workflow Timers.
* Plugin synchronization.
* credential-expiration checks.
* retention evaluation.
* archive processing.
* monitoring checks.

The scheduler must not perform long-running domain work directly.

Scheduled dispatch should be idempotent.

---

## Worker Shutdown

Workers must support graceful shutdown.

During deployment, a Worker should:

1. Stop reserving new work.
2. finish or safely release the current job.
3. preserve failure and retry state.
4. exit before the deployment timeout.

Forced termination must not leave authoritative state partially committed.

---

## Deployment Compatibility

Jobs may remain queued while application Versions change.

Therefore:

* Payloads must be versioned when structure may change.
* Workers should tolerate older supported payloads.
* destructive payload changes require a migration plan.
* old Workers and new Web instances may overlap during rolling deployment.

Queue compatibility is part of release safety.

---

## Observability

Worker telemetry should include:

```text
queue depth
oldest job age
processing rate
success rate
failure rate
retry count
execution duration
Worker heartbeat
dead-letter count
```

Logs should include:

```text
job_id
job_type
organization_id
resource_id
correlation_id
attempt
queue
```

Secrets and unrestricted tenant payloads must remain excluded.

---

## Alerts

Operational Alerts should be created for:

* Rapid Queue growth.
* oldest job age exceeding limits.
* repeated job failure.
* unavailable Workers.
* scheduler heartbeat loss.
* dead-letter growth.
* Outbox publication delay.
* Agent or Plugin backlog.
* failed-job storage failure.

Thresholds should differ by Queue.

---

## Scaling

Workers should scale using:

* Queue depth.
* oldest job age.
* processing latency.
* CPU and memory.
* provider limits.
* database capacity.

Queue depth alone is insufficient when jobs vary significantly in cost.

---

## Queue Availability

Temporary Queue unavailability must not corrupt authoritative state.

During outage:

* Web requests may continue when they can safely write state and Outbox Records.
* asynchronous effects are delayed.
* Queue health becomes degraded.
* backlog is processed after recovery.

Critical synchronous behavior must not depend on an unavailable Queue unless explicitly designed to fail closed.

---

## Security

Workers must use:

* Dedicated workload identities.
* least-privilege credentials.
* private Redis access.
* validated Tenant Context.
* restricted outbound access.
* protected failed-job storage.

Agent and Plugin Workers should have narrower network access than general application processes.

---

## Testing Requirements

Tests should cover:

* Duplicate delivery.
* retry behavior.
* permanent failure classification.
* Tenant Context restoration.
* cross-Organization rejection.
* stale Resource Version handling.
* Worker termination.
* delayed and scheduled jobs.
* Outbox duplicate publication.
* dead-letter recovery.
* payload-version compatibility.

---

## Prohibited Practices

The following are prohibited:

* Treating Redis as authoritative domain storage.
* jobs without Organization context.
* serializing complete mutable domain objects.
* unlimited retries.
* external calls inside long database transactions.
* assuming exactly-once Queue delivery.
* retrying authorization or tenant-isolation failures.
* running all workloads with unlimited shared concurrency.
* silent failed-job deletion.
* non-versioned breaking payload changes.
* long-running work inside the scheduler.

---

## Business Rules

* Queue delivery is treated as at least once.
* Every side-effecting job is idempotent.
* Tenant Context is validated during execution.
* Workers reload authoritative state before acting.
* transient failures use bounded retry and backoff.
* permanent failures move directly to failure handling.
* Queue workloads remain independently scalable.
* Agent and Plugin processing remains isolated where practical.
* failed jobs are observable and recoverable.
* deployment changes preserve queued-job compatibility.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/02-docker-environment.md`
* `docs/deployment/03-ci-cd-pipeline.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/deployment/10-release-strategy.md`
* `docs/database/00-data-architecture.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/07-agent-plugin-security.md`
