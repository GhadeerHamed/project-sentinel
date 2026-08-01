# Observability

## Purpose

Observability enables Project Sentinel to understand the health, performance, reliability, and operational behavior of the platform itself.

It helps operators determine:

* Whether Sentinel is healthy.
* which capability is degraded.
* which Organizations are affected.
* whether processing is delayed.
* whether dependencies are failing.
* whether data is stale or incomplete.
* whether recovery is successful.

Observability must cover both technical availability and business-processing correctness.

A capability is not healthy merely because it responds to requests. It must also process data correctly, securely, and within expected time limits.

---

## Objectives

Observability enables Sentinel to:

* Measure platform and capability health.
* Detect failures and degradation.
* measure latency and throughput.
* monitor backlogs and processing delays.
* monitor external dependencies.
* track data freshness and completeness.
* evaluate reliability objectives.
* support Incident investigation.
* verify recovery.
* support capacity planning.
* provide Organization-specific health visibility.
* prevent silent operational failure.

---

## Responsibilities

Observability is responsible for:

* Collecting platform telemetry.
* producing operational Metrics.
* recording structured Logs.
* tracing distributed operations.
* evaluating Health Checks.
* measuring capability performance.
* measuring dependency health.
* tracking processing backlogs.
* tracking data freshness.
* calculating Service Level Indicators.
* evaluating Service Level Objectives.
* producing Health States.
* publishing health and degradation events.
* supporting operational dashboards.
* preserving diagnostic evidence.

---

## Functional Boundaries

Observability does not:

* Own customer Events.
* own Alert or Incident lifecycle.
* replace business analytics.
* replace Audit.
* execute remediation automatically.
* modify authoritative business Resources.
* expose unrestricted platform internals.
* expose one Organization’s telemetry to another.
* guarantee complete diagnosis of every failure.
* treat missing telemetry as evidence of health.

Alert Management owns operational Alerts.

Incident Management owns coordinated response.

Observability supplies the evidence used by those capabilities.

---

## Position in the Platform

```text
Platform Capabilities and Dependencies

↓

Operational Telemetry

↓

Measurements and Health Evaluation

↓

Alerts, Dashboards, and Incidents

↓

Investigation and Recovery
```

---

## Core Concepts

| Concept                 | Description                                              |
| ----------------------- | -------------------------------------------------------- |
| Telemetry               | Operational data emitted by Sentinel or its dependencies |
| Metric                  | A numeric measurement over time                          |
| Log                     | A structured record describing platform behavior         |
| Trace                   | The path of one operation across capabilities            |
| Health Check            | A controlled evaluation of capability availability       |
| Health State            | The assessed condition of a capability                   |
| Dependency              | An internal or external service required by Sentinel     |
| Backlog                 | Pending work awaiting processing                         |
| Freshness               | How current derived data is                              |
| Service Level Indicator | A measurement representing service behavior              |
| Service Level Objective | A target for a Service Level Indicator                   |
| Error Budget            | The tolerated amount of objective failure                |
| Diagnostic Evidence     | Telemetry retained for investigation                     |

---

## Observability Scope

Observability may apply to:

* Platform.
* region.
* Organization.
* capability.
* component.
* Project.
* Application.
* Environment.
* Plugin Installation.
* Agent.
* Notification Channel.
* Search Index.
* API Client.
* Workflow.
* dependency.

Every measurement must include enough scope to identify the affected area.

---

## Telemetry Types

Sentinel may collect:

* Metrics.
* Logs.
* Traces.
* Health Check results.
* dependency status.
* queue measurements.
* processing outcomes.
* data-quality measurements.
* usage measurements.
* security signals.

Each Telemetry Type serves a different operational purpose.

---

## Metrics

Metrics may measure:

* Request volume.
* error rate.
* latency.
* throughput.
* queue depth.
* oldest queued item.
* processing success.
* processing failure.
* provider availability.
* data freshness.
* storage usage.
* quota usage.
* concurrent execution.

Metrics should avoid unnecessary sensitive or high-cardinality fields.

---

## Logs

Logs provide structured diagnostic records.

Logs may include:

* Operation.
* capability.
* result.
* severity.
* timestamp.
* Organization reference.
* Resource reference.
* Correlation Identifier.
* duration.
* error category.
* provider.
* region.
* deployment version.

Logs must not include:

* Passwords.
* API secrets.
* authentication tokens.
* Plugin credentials.
* unrestricted customer payloads.
* unnecessary personal data.
* confidential Agent context.

---

## Traces

Traces connect one operation across multiple capabilities and dependencies.

Traces may follow:

* API request processing.
* Event ingestion.
* Alert creation.
* Incident workflows.
* Agent Executions.
* Plugin Executions.
* Notification delivery.
* Search Requests.
* administrative changes.

Trace data must preserve Organization isolation and avoid sensitive payload content.

---

## Correlation

Correlation Identifiers connect related operational records.

A Correlation Identifier may connect:

* API Requests.
* Events.
* Signals.
* Alerts.
* Incidents.
* Agent Executions.
* Plugin Executions.
* Notification Attempts.
* Workflows.
* Logs.
* Traces.
* Audit records.

Correlation supports end-to-end investigation.

---

## Health Checks

Health Checks evaluate whether a capability or dependency can perform expected behavior.

Health Checks may include:

* Availability checks.
* readiness checks.
* dependency checks.
* credential checks.
* queue checks.
* data-freshness checks.
* synthetic business checks.

Health Checks must be:

* Non-destructive.
* bounded by timeout.
* explainable.
* rate-limited.
* safe for external providers.
* isolated by Organization where required.

---

## Health States

Sentinel may use the following Health States:

| State       | Meaning                                            |
| ----------- | -------------------------------------------------- |
| Healthy     | Operating within expected objectives               |
| Degraded    | Operating with reduced performance or completeness |
| Unhealthy   | Operating unreliably or incorrectly                |
| Unavailable | Unable to perform expected behavior                |
| Maintenance | Intentionally affected by planned work             |
| Restricted  | Limited intentionally by Policy or safety control  |
| Recovering  | Returning to normal after failure                  |
| Unknown     | Current health cannot be determined                |

Unknown must never be presented as Healthy.

---

## Health Evaluation

Health may be evaluated using:

* Availability.
* error rate.
* latency.
* throughput.
* backlog.
* data freshness.
* processing correctness.
* dependency health.
* saturation.
* recent failures.
* recovery progress.
* Service Level Objectives.

One successful request is not enough to prove a capability is Healthy.

---

## Health Aggregation

Health may be aggregated through:

```text
Dependency Health

↓

Component Health

↓

Capability Health

↓

Regional Health

↓

Platform Health
```

Higher-level Health must not hide important lower-level degradation.

---

## Partial Degradation

Sentinel must represent partial failure accurately.

Examples include:

* One Notification provider is unavailable.
* Semantic Search is unavailable while keyword Search works.
* one Organization’s Plugin credentials have expired.
* one region is degraded.
* one Agent provider is unavailable.
* one Search Resource Type is stale.

Partial failure must not be represented as complete platform failure or complete health.

---

## Capability Health

Each major capability should expose health information.

Capability Health may include:

* Availability.
* processing latency.
* success rate.
* failure rate.
* backlog.
* oldest pending item.
* dependency health.
* data freshness.
* current restrictions.
* recent changes.

---

## Event Processing Health

Event Processing should expose:

* Events received.
* Events accepted.
* Events rejected.
* validation failures.
* duplicate rate.
* ingestion latency.
* processing latency.
* queue depth.
* oldest pending Event.
* failed processing count.

---

## Alert Management Health

Alert Management should expose:

* Signals evaluated.
* Alerts created.
* Alert evaluation latency.
* Rule failures.
* duplicate rate.
* suppression rate.
* unassigned Critical Alerts.
* acknowledgement delay.
* escalation delay.

---

## Incident Management Health

Incident Management should expose:

* Active Incidents.
* unowned Incidents.
* lifecycle-transition failures.
* overdue status updates.
* action backlog.
* time to acknowledgement.
* time to mitigation.
* time to resolution.
* Post-Incident Review backlog.

---

## Knowledge Management Health

Knowledge Management should expose:

* Review backlog.
* overdue reviews.
* stale Knowledge.
* conflict count.
* publication failures.
* Knowledge without owners.
* feedback indicating unsafe or outdated guidance.

---

## Agent Platform Health

The Agent Platform should expose:

* Agent Execution count.
* success rate.
* failure rate.
* timeout rate.
* provider latency.
* provider availability.
* output-validation failures.
* Tool failures.
* cost usage.
* suspended Agents.
* Recommendation acceptance and outcome.

---

## Plugin Platform Health

The Plugin Platform should expose:

* Plugin Execution count.
* success rate.
* failure rate.
* retry rate.
* credential failures.
* provider latency.
* synchronization delay.
* validation failures.
* unhealthy Installations.
* unsupported Plugin Versions.

---

## Notification Platform Health

The Notification Platform should expose:

* Notifications requested.
* Delivery Attempts.
* delivery success rate.
* delivery failure rate.
* retry rate.
* provider latency.
* acknowledgement delay.
* escalation rate.
* unhealthy Channels.
* recipient-resolution failures.

---

## Search Health

Search should expose:

* Search Request volume.
* query latency.
* query failures.
* timeout rate.
* indexing backlog.
* indexing failure rate.
* index freshness.
* stale Resource Types.
* semantic provider availability.
* reconciliation failures.

---

## Authentication and Authorization Health

Authentication and Authorization should expose:

* Authentication success and failure.
* MFA failures.
* SSO provider health.
* Session revocation delay.
* Authorization latency.
* Authorization evaluation failures.
* Policy evaluation failures.
* suspicious access attempts.
* cross-Organization access attempts.

An indeterminate Authorization Decision must never become Allow.

---

## Public API Health

The Public API should expose:

* Request volume.
* request latency.
* success rate.
* validation failure rate.
* authentication failure rate.
* authorization failure rate.
* timeout rate.
* Rate Limiting.
* Quota rejection.
* API Job failures.
* Webhook delivery failures.

---

## Workflow Health

Operational Workflows should expose:

* Workflow starts.
* Workflow completions.
* Workflow failures.
* blocked Workflows.
* overdue tasks.
* approval delay.
* action failures.
* compensation activity.
* stalled Workflows.

---

## Dependency Health

Dependencies may include:

* Databases.
* queues.
* search infrastructure.
* object storage.
* identity providers.
* Notification providers.
* AI providers.
* Plugin providers.
* external APIs.
* cache systems.
* secret storage.

Dependency health should be measured separately from capability health.

---

## Dependency Classification

Dependencies may be classified as:

| Classification        | Meaning                                  |
| --------------------- | ---------------------------------------- |
| Critical              | Failure prevents a Core capability       |
| Important             | Failure materially degrades a capability |
| Optional              | Failure removes a non-essential feature  |
| External              | Operated outside Sentinel                |
| Internal              | Operated as part of Sentinel             |
| Organization-Specific | Used by one Organization                 |
| Shared                | Used by multiple Organizations           |

Classification influences Alerting and Incident response.

---

## Backlogs

Backlogs represent pending work.

Backlogs may occur in:

* Event ingestion.
* Alert evaluation.
* Notification delivery.
* Plugin execution.
* Agent execution.
* Search indexing.
* Workflow execution.
* exports.
* retention processing.

Backlog monitoring should include:

* Pending count.
* oldest item age.
* arrival rate.
* completion rate.
* growth rate.
* failure count.
* affected Organizations.

---

## Data Freshness

Freshness measures how closely derived information reflects authoritative state.

Freshness applies to:

* Search Indexes.
* Dashboards.
* health summaries.
* Agent context.
* usage summaries.
* Notification routing.
* authorization caches.

Stale information must remain visibly stale.

---

## Data Completeness

Completeness checks may identify:

* Missing Search Projections.
* missing Audit records.
* incomplete Incident timelines.
* missing provider callbacks.
* missing Notification results.
* missing Agent evidence.
* missing ownership.
* lost telemetry.

Completeness failure may require Alert or Incident evaluation.

---

## Data Correctness

Correctness checks may verify:

* Organization ownership.
* valid lifecycle state.
* Search Projection consistency.
* Notification provider result consistency.
* Plugin mapping validity.
* Agent evidence references.
* Quota calculations.
* authorization-cache consistency.

Incorrect derived data must not overwrite authoritative Resources automatically.

---

## Service Level Indicators

Service Level Indicators may include:

* Successful API request ratio.
* Event processing latency.
* Alert evaluation latency.
* Notification delivery success.
* Search freshness.
* Agent Execution success.
* Plugin availability.
* Authorization decision latency.
* Workflow completion rate.

Every Indicator must have a defined calculation.

---

## Service Level Objectives

A Service Level Objective should define:

* Capability.
* Indicator.
* scope.
* target.
* measurement period.
* exclusions.
* owner.
* breach behavior.

Objectives may differ by:

* Capability.
* subscription.
* region.
* Environment.
* Organization tier.

---

## Error Budgets

An Error Budget represents the tolerated amount of Service Level Objective failure.

Error Budget status may be:

* Healthy.
* At Risk.
* Exhausted.
* Recovering.
* Unknown.

Error Budget state may influence:

* Feature rollout.
* release pace.
* maintenance approval.
* reliability work.
* provider changes.
* capacity investment.

---

## Operational Dashboards

Dashboards may present:

* Platform Health.
* capability Health.
* dependency Health.
* regional Health.
* Organization Health.
* backlog.
* Service Level Objectives.
* Error Budgets.
* active Incidents.
* Plugin Health.
* Agent Health.
* Notification Health.
* Search Health.
* capacity and usage.

Dashboards are derived views.

They must display data freshness and current scope.

---

## Organization Health

Authorized Organization administrators may view health relevant to their Organization.

This may include:

* Event ingestion.
* processing delay.
* Alert backlog.
* Incident state.
* Plugin Health.
* Agent Health.
* Notification delivery.
* Search freshness.
* API usage.
* Quota state.
* scheduled Maintenance.
* known platform impact.

Organization views must not expose another Organization’s information.

---

## Alerting

Observability may produce Signals for Alert Management.

Examples include:

* High error rate.
* processing delay.
* growing backlog.
* provider failure.
* stale Search Index.
* authorization failure spike.
* storage saturation.
* failed Audit processing.
* Agent provider outage.
* Notification delivery failure.

Alert Management remains authoritative for resulting Alerts.

---

## Incident Support

Observability supports Incident Management through:

* Impact measurement.
* scope identification.
* timeline reconstruction.
* dependency analysis.
* change correlation.
* mitigation validation.
* recovery verification.
* customer-impact assessment.

Incident Management remains authoritative for Incident lifecycle.

---

## Recovery Verification

Recovery should be verified through sustained evidence such as:

* Error rate returning to normal.
* latency returning to normal.
* backlog decreasing.
* successful processing.
* dependency recovery.
* Search freshness recovery.
* successful Health Checks.
* reduced Organization impact.
* no immediate recurrence.

One successful request is not sufficient evidence of recovery.

---

## Change Correlation

Observability may correlate degradation with:

* Deployments.
* Configuration changes.
* Feature Flags.
* Plugin upgrades.
* Agent Version changes.
* provider changes.
* Policy changes.
* migrations.
* Maintenance Windows.

Correlation suggests a possible relationship.

It does not prove causation.

---

## Capacity and Saturation

Observability should measure capacity risks such as:

* Queue saturation.
* storage consumption.
* database connection usage.
* worker utilization.
* memory or compute pressure.
* provider Quota usage.
* concurrent Executions.
* Search Index growth.

Capacity trends support planning before failure occurs.

---

## Cost and Usage

Observability may measure:

* Infrastructure usage.
* storage usage.
* Agent cost.
* Notification provider cost.
* Plugin provider usage.
* Search usage.
* API usage.
* Organization consumption.

Cost and usage information supports Administration and Subscription Management.

It must remain distinct from technical Health.

---

## Sensitive Telemetry

Sensitive Telemetry may include:

* Security events.
* Authorization Decisions.
* customer identifiers.
* restricted Incident evidence.
* internal infrastructure details.
* Agent context metadata.
* personal or regulated data.

Sensitive Telemetry requires:

* Explicit authorization.
* data minimization.
* redaction.
* stronger retention controls.
* enhanced audit.
* restricted export.

---

## Debug Mode

Authorized operators may enable temporary Debug Mode.

Debug Mode may increase:

* Log detail.
* Trace sampling.
* Plugin diagnostics.
* Agent execution metadata.
* provider diagnostics.

Debug Mode must be:

* Scoped.
* time-bounded.
* authorized.
* auditable.
* automatically disabled at expiration.

Debug Mode must not expose credentials or prohibited data.

---

## Telemetry Retention

Retention may differ by Telemetry Type.

A retention policy may define:

* Telemetry Type.
* scope.
* retention period.
* aggregation behavior.
* archive behavior.
* deletion behavior.
* sensitivity.
* legal hold.

Diagnostic telemetry should not be retained longer than required.

Audit-required records remain governed by Audit retention rules.

---

## Observability Availability

Observability must monitor itself.

The platform should measure:

* Telemetry ingestion.
* telemetry loss.
* processing backlog.
* storage failure.
* query failure.
* Health Check failure.
* Alert evaluation failure.
* Dashboard freshness.
* export failure.

Silent Observability failure is unacceptable.

---

## Degraded Observability

Observability may be degraded when:

* Metrics are delayed.
* Logs are unavailable.
* Trace collection is reduced.
* one region is missing telemetry.
* Dashboards are stale.
* Health Checks cannot run.
* Search over telemetry is unavailable.

When visibility is incomplete, affected Health States should become Unknown or Degraded rather than Healthy.

---

## Auditability

The following activities may be audited:

* Observability configuration changes.
* Service Level Objective changes.
* Dashboard changes.
* Debug Mode activation.
* sensitive telemetry access.
* telemetry export.
* retention changes.
* Health override.
* Alert-condition changes.
* provider integration changes.

Audit records should identify:

* Organization or platform scope.
* actor.
* action.
* time.
* reason.
* previous configuration.
* resulting configuration.
* applicable Policy.

---

## Capability Interactions

| Capability                       | Relationship                                           |
| -------------------------------- | ------------------------------------------------------ |
| Organization Management          | Provides Organization scope                            |
| Application Management           | Provides Application context                           |
| Environment Management           | Provides Environment criticality                       |
| Event Processing                 | Emits ingestion and processing telemetry               |
| Alert Management                 | Receives health Signals                                |
| Incident Management              | Uses diagnostic and recovery evidence                  |
| Knowledge Management             | Uses reliability evidence for learning                 |
| Agent Platform                   | Emits execution, provider, and cost telemetry          |
| Plugin Platform                  | Emits execution and provider telemetry                 |
| Notification Platform            | Emits delivery and Channel telemetry                   |
| Search                           | Exposes Search Health and indexes authorized telemetry |
| Authentication and Authorization | Protects telemetry and emits access health             |
| Administration                   | Configures health, retention, and objectives           |
| Public API                       | Emits API health and usage telemetry                   |
| Operational Workflows            | Emits Workflow progress and failure telemetry          |
| Subscription Management          | Uses governed usage measurements                       |
| Policy Layer                     | Governs telemetry collection and access                |
| Audit                            | Supplies evidence about Audit health                   |

Observability remains authoritative for platform telemetry, Health States, Service Level Indicators, Service Level Objectives, and operational dashboards.

Each capability remains authoritative for its own business Resources.

---

## Business Rules

* Observability must preserve Organization isolation.
* Health must be based on operational evidence.
* Unknown must not be represented as Healthy.
* Partial degradation must remain visible.
* Business-processing correctness is part of Health.
* Observability must not modify authoritative business Resources.
* Sensitive Telemetry requires explicit authorization.
* Credentials and secrets must not appear in telemetry.
* Cross-Organization data must not leak through Metrics, Logs, Traces, or Dashboards.
* Health States must be explainable.
* Service Level Objectives must have defined Indicators.
* Dependency failure must identify affected scope where possible.
* Stale telemetry must remain visibly stale.
* Critical telemetry loss must be detectable.
* Observability must monitor its own pipeline.
* Debug Mode must be time-bounded.
* Sampling must not remove mandatory security or Audit evidence.
* Recovery must be verified through sustained evidence.
* Observability Alerts remain owned by Alert Management.
* Platform Incidents remain owned by Incident Management.
* Unauthorized telemetry access is rejected and auditable.

---

## Success Criteria

Observability is successful when:

* Platform Health is visible and evidence-based.
* failures are detected before becoming silent operational loss.
* affected capabilities and Organizations can be identified.
* operators can trace failures across dependencies.
* processing backlogs and stale data remain visible.
* active Incidents have useful diagnostic evidence.
* recovery can be verified objectively.
* Service Level Objectives can be evaluated consistently.
* capacity risks are identified early.
* sensitive Telemetry remains protected.
* Unknown states remain distinct from Healthy states.
* Observability failure is itself detectable.
* Organization isolation is preserved.

---

## Related Documents

* `docs/functional/00-platform-overview.md`
* `docs/functional/05-event-processing.md`
* `docs/functional/06-alert-management.md`
* `docs/functional/07-incident-management.md`
* `docs/functional/08-knowledge-management.md`
* `docs/functional/09-agent-platform.md`
* `docs/functional/10-plugin-platform.md`
* `docs/functional/11-notification-platform.md`
* `docs/functional/12-search.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/functional/14-administration.md`
* `docs/functional/16-public-api.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/08-security-architecture.md`
* `docs/architecture/09-scaling-strategy.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
