# Monitoring and Observability

## Purpose

This document defines the operational observability requirements for Project Sentinel.

Observability must help operators understand system health, diagnose failures, measure performance, and verify deployments without exposing sensitive tenant data.

---

## Core Principles

* Every production component emits structured telemetry.
* Logs, metrics, and traces use consistent identifiers.
* Monitoring failures must be visible.
* Alerts must be actionable.
* Tenant data and secrets must remain protected.
* Observability should diagnose behavior without requiring direct database access.
* Telemetry cost and cardinality must remain controlled.

---

## Observability Signals

Sentinel uses:

```text
Logs
Metrics
Traces
Health checks
Deployment events
Operational Alerts
```

Each signal serves a different purpose and should not replace the others.

---

## Structured Logs

Production Logs must use structured output, preferably JSON.

Recommended fields:

```text
timestamp
level
service
environment
release_version
request_id
correlation_id
organization_id
identity_type
operation
resource_type
resource_id
duration_ms
outcome
```

Fields should be omitted when they are unavailable rather than populated with misleading values.

---

## Log Levels

Supported levels:

* `debug`
* `info`
* `warning`
* `error`
* `critical`

Production should normally use `info` or higher.

Debug logging must be temporary, scoped, and prevented from exposing sensitive payloads.

---

## Sensitive Data

Logs, traces, and metrics must not contain:

* Passwords.
* API keys.
* access tokens.
* authorization headers.
* webhook secrets.
* plaintext credentials.
* unrestricted Event payloads.
* confidential evidence.
* full Agent prompts or outputs.
* unnecessary personal data.

Sensitive fields must be redacted before telemetry leaves the application process.

---

## Correlation

Every inbound request should receive or generate:

```text
request_id
correlation_id
```

The correlation identifier must propagate through:

* Domain commands.
* database transactions.
* Outbox messages.
* Queue jobs.
* Workflow Steps.
* Agent executions.
* Plugin operations.
* Notifications.

This allows one operational flow to be traced across asynchronous boundaries.

---

## Metrics

Core service metrics should include:

```text
request rate
request latency
error rate
active requests
database query duration
database connection usage
Redis latency
Queue depth
oldest job age
job execution duration
job failure rate
Worker availability
scheduler delay
provider request duration
provider failure rate
```

Domain-level metrics may include:

```text
Events ingested
Alerts created
Incidents opened
Notifications delivered
Workflow Steps completed
Agent executions completed
Plugin synchronizations completed
```

---

## Metric Labels

Metric labels must have bounded cardinality.

Acceptable labels may include:

```text
service
environment
route
operation
status
queue
job_type
provider
failure_class
```

High-cardinality values should not normally be metric labels, including:

* `organization_id`
* `user_id`
* `resource_id`
* `request_id`
* `correlation_id`

These identifiers belong in Logs and traces.

---

## Tracing

Distributed tracing should cover important execution paths such as:

```text
HTTP request
→ application command
→ database operation
→ Outbox publication
→ Queue job
→ provider call
```

Trace spans should record:

* Operation name.
* service.
* duration.
* outcome.
* safe dependency information.
* request and correlation identifiers.

Trace payloads must follow the same data-protection rules as Logs.

---

## Web Monitoring

Web monitoring should track:

* Request volume.
* latency percentiles.
* error rate.
* authentication failures.
* authorization denials.
* rate-limit responses.
* request-size rejection.
* dependency latency.
* readiness status.

Metrics should distinguish routes by normalized route name, not raw URL.

---

## Worker Monitoring

Worker monitoring should track:

* Worker heartbeat.
* Queue depth.
* oldest queued job.
* jobs processed.
* execution duration.
* retries.
* timeouts.
* failed jobs.
* dead-letter growth.
* graceful shutdown failures.

Different Queue groups require different thresholds.

---

## Scheduler Monitoring

The scheduler must emit:

* Heartbeat.
* last successful execution.
* execution delay.
* failed scheduled command count.
* duplicated-dispatch detection.
* jobs dispatched per schedule.

Missing scheduler heartbeat should create an operational Alert.

---

## PostgreSQL Monitoring

PostgreSQL monitoring should include:

* Availability.
* connection usage.
* query latency.
* slow queries.
* lock waits.
* deadlocks.
* transaction duration.
* storage usage.
* replication health where applicable.
* backup status.

Database monitoring must not expose query parameters containing sensitive data.

---

## Redis Monitoring

Redis monitoring should include:

* Availability.
* latency.
* memory usage.
* eviction count.
* connection usage.
* blocked clients.
* Queue-related command failures.
* persistence health where enabled.

Cache eviction must not be treated as authoritative data loss.

---

## Object Storage Monitoring

Monitor:

* Availability.
* upload and download failures.
* request latency.
* storage growth.
* lifecycle-rule failures.
* signed URL failures.
* archive and export failures.

Object names containing tenant identifiers must remain access-controlled.

---

## External Provider Monitoring

External providers should be monitored for:

* Availability.
* latency.
* timeout rate.
* rate limiting.
* authentication failure.
* quota exhaustion.
* malformed responses.
* circuit-breaker state.

Provider failures should identify the integration type without exposing credentials or sensitive payloads.

---

## Agent Monitoring

Agent telemetry should include:

* Execution count.
* duration.
* status.
* provider.
* model identifier where permitted.
* retry count.
* estimated usage or cost.
* validation failure.
* Tool call count.
* approval-required outcome.

Raw prompts and outputs should not be included in general operational telemetry.

---

## Plugin Monitoring

Plugin telemetry should include:

* Installation identifier in Logs.
* synchronization duration.
* imported item count.
* provider failures.
* callback verification failures.
* retry count.
* synchronization lag.
* capability violation.

Metrics should aggregate by Plugin type rather than Installation identifier.

---

## Health Endpoints

Each service should expose:

### Liveness

Confirms that the process is running.

### Readiness

Confirms that the process can safely accept work.

Readiness may check:

* Required configuration.
* PostgreSQL connectivity.
* Redis connectivity when required.
* migration compatibility.
* critical internal dependencies.

Optional external providers should normally report degraded status rather than make the full application unready.

---

## Service Status

Sentinel should distinguish:

```text
healthy
degraded
unavailable
```

Examples:

* Email provider unavailable: `degraded`
* PostgreSQL unavailable: `unavailable`
* one optional Plugin provider unavailable: `degraded`
* all Workers stopped: relevant asynchronous capability `unavailable`

---

## Operational Alerts

Alerts should be based on symptoms and user impact where possible.

Important Alerts include:

* Elevated API error rate.
* excessive request latency.
* Queue backlog growth.
* old jobs exceeding limits.
* Worker or scheduler heartbeat loss.
* PostgreSQL connection exhaustion.
* storage approaching capacity.
* failed backups.
* Outbox publication delay.
* repeated provider failure.
* deployment verification failure.
* observability pipeline failure.

---

## Alert Quality

Every operational Alert should include:

* Clear condition.
* severity.
* affected component.
* current value.
* threshold.
* first detection time.
* relevant dashboard or query.
* recommended investigation path.

Alerts without an expected operator Action should be reconsidered.

---

## Alert Noise Control

Use:

* Deduplication.
* aggregation.
* recovery notifications.
* minimum-duration thresholds.
* environment-specific thresholds.
* maintenance windows.
* dependency-aware suppression.

Critical failures must not be hidden by broad suppression rules.

---

## Dashboards

Minimum production dashboards should cover:

```text
Platform overview
Web/API health
Workers and Queues
PostgreSQL
Redis
External providers
Agents and Plugins
Deployments
Backups and recovery
```

Dashboards should show current state and recent trends.

---

## Service-Level Objectives

Sentinel should define measurable objectives for important user-facing capabilities.

Examples:

* API availability.
* API latency.
* Event ingestion delay.
* Alert evaluation delay.
* Notification delivery delay.
* Queue processing delay.

Objectives should be introduced gradually and based on product commitments.

---

## Deployment Observability

Every deployment should create a telemetry marker containing:

```text
environment
release_version
source_commit
image_digest
deployment_id
started_at
completed_at
outcome
```

Dashboards and traces should make it possible to compare system behavior before and after a release.

---

## Retention

Telemetry retention should depend on:

* Operational value.
* investigation needs.
* security requirements.
* cost.
* regulation.
* contractual commitments.

Suggested relative retention:

```text
High-volume debug data: short
Operational Logs: medium
Metrics: medium to long
Security and Audit Records: governed separately
```

Telemetry retention must not override domain retention or Legal Hold rules.

---

## Access Control

Observability access must use least privilege.

Operators should see only the data necessary for their responsibilities.

Cross-tenant investigation requires:

* Explicit privileged access.
* defined purpose.
* restricted scope.
* Audit recording.

Observability systems must not become an uncontrolled path to tenant data.

---

## Monitoring the Monitoring System

Sentinel must detect failures in:

* Log collection.
* metric ingestion.
* trace export.
* dashboard data freshness.
* Alert evaluation.
* Alert delivery.
* telemetry storage capacity.

A silent monitoring failure is an operational risk.

---

## Local and Staging Environments

Local development may use simpler tooling while preserving:

* Structured Logs.
* request identifiers.
* correlation identifiers.
* Queue visibility.
* failed-job inspection.

Staging should mirror production telemetry structure and Alert logic where practical.

Staging telemetry must remain isolated from production.

---

## Incident Integration

Operational Alerts may create or contribute to Sentinel Incidents.

An Alert should escalate when:

* Impact is sustained.
* multiple components are affected.
* manual coordination is required.
* recovery requires controlled Actions.
* customer impact is likely.

Observability evidence should remain attached to the Incident through references rather than unrestricted payload copies.

---

## Testing Requirements

Tests should verify:

* Correlation propagation.
* sensitive-field redaction.
* health endpoint behavior.
* metric emission for critical paths.
* Worker heartbeat behavior.
* Alert condition evaluation.
* deployment marker creation.
* telemetry failure handling.

---

## Prohibited Practices

The following are prohibited:

* Plaintext credentials in telemetry.
* raw request-body logging by default.
* `organization_id` or Resource IDs as metric labels.
* raw URLs as route labels.
* silent scheduler or Worker failure.
* unrestricted observability access.
* relying only on Logs without metrics or health checks.
* using optional provider failure as universal application failure.
* retaining debug telemetry indefinitely.
* Alerts without defined operational meaning.

---

## Business Rules

* Production services emit structured Logs, metrics, and health data.
* request and correlation identifiers propagate across asynchronous execution.
* telemetry excludes secrets and unnecessary sensitive data.
* high-cardinality identifiers remain outside metric labels.
* Web, Workers, scheduler, data stores, and providers are monitored independently.
* deployment events are visible in operational telemetry.
* critical monitoring failures create Alerts.
* observability access follows least privilege and tenant-protection rules.
* Alerts must be actionable and noise-controlled.
* observability supports Incident investigation without bypassing domain security.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/03-ci-cd-pipeline.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/06-workers-queues.md`
* `docs/deployment/08-backup-recovery.md`
* `docs/deployment/10-release-strategy.md`
* `docs/security/06-data-protection.md`
* `docs/security/08-security-monitoring.md`
* `docs/database/13-audit-schema.md`
