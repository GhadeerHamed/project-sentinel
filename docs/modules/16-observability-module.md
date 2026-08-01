# Observability Module

## Purpose

The Observability module provides governed operational telemetry for Sentinel itself.

It collects and exposes Logs, metrics, traces, health state, and operational signals without becoming the authoritative source of domain state.

---

## Owns

* Observability configuration metadata.
* service health records.
* Worker and scheduler heartbeat state.
* telemetry ingestion status.
* operational metric definitions.
* dashboard definitions.
* Alert rule references for platform health.
* deployment markers.
* telemetry pipeline failure records.

External telemetry platforms may store the underlying high-volume data.

---

## Does Not Own

* Domain Audit Records.
* security Incident lifecycle.
* tenant business Resources.
* infrastructure provisioning.
* application deployment.
* domain Event ingestion.
* customer-facing Alert lifecycle.

Operational findings may be forwarded to the Alert module through explicit contracts.

---

## Core Responsibilities

The module is responsible for:

* Defining telemetry conventions.
* tracking platform component health.
* recording deployment markers.
* monitoring telemetry pipelines.
* exposing operational health queries.
* detecting missing heartbeats.
* publishing platform-health events.
* protecting sensitive telemetry access.
* supporting Incident investigation through references.

---

## Observability Signals

The module coordinates:

```text
Logs
Metrics
Traces
Health checks
Heartbeats
Deployment events
Operational alerts
```

Each signal remains independently useful.

---

## Service Identity

Every emitting process should identify:

```text
service_name
process_type
environment
instance_id
release_version
```

Process types may include:

```text
web
worker
scheduler
maintenance
migration
```

---

## Telemetry Context

Operational telemetry should propagate:

```text
request_id
correlation_id
organization_id when appropriate
resource_type
resource_id
operation
outcome
duration
```

High-cardinality identifiers belong in Logs or traces, not general metric labels.

---

## Commands

```text
RegisterService
RecordServiceHeartbeat
RecordWorkerHeartbeat
RecordSchedulerHeartbeat
RecordDeploymentMarker
RecordTelemetryPipelineFailure
ResolveTelemetryPipelineFailure
UpdateObservabilityConfiguration
```

Telemetry ingestion commands must remain lightweight and idempotent where practical.

---

## Queries

```text
GetPlatformHealth
GetServiceHealth
ListServiceInstances
GetWorkerHealth
GetSchedulerHealth
GetTelemetryPipelineStatus
GetDeploymentMarkers
GetOperationalDashboardDefinition
```

Queries must respect Environment and administrative access scope.

---

## Published Events

```text
observability.service_healthy
observability.service_degraded
observability.service_unavailable
observability.worker_missing
observability.scheduler_missing
observability.telemetry_pipeline_failed
observability.telemetry_pipeline_recovered
observability.deployment_recorded
```

Events should contain safe diagnostic metadata only.

---

## Consumed Events

The module may consume:

```text
deployment.started
deployment.completed
deployment.failed
queue.backlog_detected
backup.failed
security.monitoring_failed
organization.suspended
```

These events may affect dashboards or platform-health evaluation.

Consumers must remain idempotent.

---

## Health State

Suggested health states:

```text
healthy
degraded
unavailable
unknown
```

Health evaluation should consider:

* Heartbeat freshness.
* dependency availability.
* error rate.
* latency.
* Queue backlog.
* recent failures.
* telemetry completeness.

---

## Heartbeats

Heartbeats should be recorded for:

* Web service groups.
* Worker groups.
* scheduler.
* Outbox publisher.
* maintenance processes.
* telemetry collectors.

A missing heartbeat must become visible within a defined threshold.

---

## Worker Monitoring

Worker health should include:

```text
queue
worker_group
last_heartbeat_at
jobs_processed
jobs_failed
oldest_job_age
current_concurrency
release_version
```

Worker health does not replace detailed Queue metrics.

---

## Scheduler Monitoring

Scheduler health should include:

```text
last_heartbeat_at
last_dispatch_at
last_successful_task_at
execution_delay
failed_task_count
release_version
```

The scheduler must not fail silently.

---

## Deployment Markers

Every deployment marker should contain:

```text
deployment_id
environment
release_version
source_commit
image_digest
started_at
completed_at
outcome
```

Deployment markers allow operators to correlate releases with changes in system behavior.

---

## Metrics

Core metric categories include:

```text
traffic
latency
errors
saturation
Queue health
database health
provider health
Agent and Plugin execution
deployment health
backup health
```

Metric definitions should use bounded labels.

---

## Logs

Logs should be:

* Structured.
* timestamped.
* attributable to one service.
* correlated across asynchronous work.
* redacted before export.
* retained according to Policy.

Raw credentials and unrestricted tenant payloads are prohibited.

---

## Traces

Tracing should cover important paths such as:

```text
HTTP request
→ application command
→ database transaction
→ Outbox publication
→ Queue job
→ external provider
```

Trace sampling must preserve useful failure visibility without uncontrolled cost.

---

## Operational Alert Integration

The Observability module may request Alerts for:

* Service unavailability.
* elevated error rate.
* excessive latency.
* Queue backlog.
* Worker failure.
* scheduler failure.
* telemetry pipeline failure.
* backup failure.
* deployment regression.

The Alert module owns the resulting Alert lifecycle.

---

## Security Monitoring Integration

Security telemetry belongs to the security-monitoring capability.

The Observability module may expose infrastructure and application evidence to security monitoring through approved references.

It must not weaken access controls for investigation convenience.

---

## Incident Integration

Operational Alerts may lead to Incidents.

The Observability module may provide:

* Dashboard links.
* trace references.
* Log query references.
* deployment markers.
* health snapshots.

It must not modify Incident state directly.

---

## Data Protection

Telemetry must exclude:

* Passwords.
* API keys.
* access tokens.
* authorization headers.
* private keys.
* unrestricted prompts.
* confidential evidence.
* unnecessary personal data.

Organization identifiers may appear in protected Logs and traces where operationally required.

---

## Access Control

Typical permissions include:

```text
observability.read
observability.dashboard.manage
observability.configuration.manage
observability.telemetry.inspect
observability.restricted.inspect
```

Cross-tenant telemetry inspection requires privileged, audited access.

---

## Retention

Telemetry retention should consider:

* Operational usefulness.
* investigation requirements.
* security obligations.
* storage cost.
* data classification.
* Legal Hold.

Audit Records remain governed separately.

---

## Failure Handling

Telemetry pipeline failures must not remain silent.

Failure handling should:

1. Record the affected pipeline.
2. identify missing signal types.
3. create an operational Alert.
4. preserve local fallback telemetry where possible.
5. notify authorized operators.
6. verify recovery and data freshness.

---

## Repository Interfaces

```text
ServiceHealthRepository
HeartbeatRepository
DeploymentMarkerRepository
TelemetryPipelineStatusRepository
ObservabilityConfigurationRepository
```

These repositories store operational metadata, not high-volume domain telemetry.

---

## Audit Requirements

Audit Records are required for:

* Observability configuration changes.
* dashboard access to restricted telemetry.
* cross-tenant investigations.
* telemetry retention changes.
* manual health overrides.
* disabling monitoring or Alerts.

Routine telemetry reads may use access Logs unless stricter Policy applies.

---

## Testing Requirements

Tests must cover:

* Heartbeat freshness.
* missing Worker detection.
* scheduler failure detection.
* health-state transitions.
* deployment marker recording.
* telemetry redaction.
* bounded metric labels.
* pipeline failure escalation.
* privileged telemetry access.
* duplicate event handling.

---

## Prohibited Practices

The Observability module must not:

* Become authoritative business storage.
* store plaintext credentials.
* use high-cardinality Resource IDs as general metric labels.
* expose tenant telemetry without authorization.
* hide monitoring failures.
* write Alert or Incident tables directly.
* depend on one telemetry provider contract.
* retain unrestricted debug telemetry indefinitely.
* allow manual health overrides without Audit.

---

## Business Rules

* Observability telemetry is derived operational data.
* every production process emits identifiable health information.
* request and correlation identifiers propagate across asynchronous work.
* telemetry excludes secrets and unnecessary sensitive data.
* health failures and missing heartbeats become actionable.
* deployment markers are traceable to immutable releases.
* operational Alerts are created through the Alert module.
* restricted telemetry access is privileged and auditable.
* telemetry providers may change without changing module contracts.
* monitoring failure is itself an operational failure.

---

## Related Documents

* `docs/modules/13-search-module.md`
* `docs/modules/15-administration-module.md`
* `docs/modules/18-laravel-runtime-adapter.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/security/08-security-monitoring.md`
* `docs/database/13-audit-schema.md`
* `docs/architecture/03-component-architecture.md`
