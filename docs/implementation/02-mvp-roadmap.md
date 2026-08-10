# MVP Roadmap

## Purpose

This document defines the scope and delivery sequence for the first usable version of Project Sentinel.

The MVP must prove that Sentinel can transform operational telemetry into actionable operational response.

---

## MVP Product Flow

The MVP delivers this complete flow:

```text
Application emits Event
→ Sentinel validates and stores Event
→ Signal is derived
→ Alert is created
→ Operator acknowledges Alert
→ Incident is created
→ Responders manage Incident
→ Notification is delivered
→ Resolution is recorded
```

The MVP is successful only when this flow works reliably across multiple isolated Organizations.

---

## MVP Objectives

The MVP must prove:

* Multi-tenant isolation.
* Reliable Event ingestion.
* useful Alert creation.
* structured Incident response.
* operational visibility.
* safe asynchronous processing.
* basic Notification delivery.
* production-ready architecture at small scale.

---

## Included Capabilities

### Identity and Access

Include:

* User authentication.
* Sessions or personal API credentials.
* Organization Memberships.
* basic Roles and permissions.
* tenant-resolution middleware.
* access revocation.

Initial Roles may include:

```text
Organization Owner
Administrator
Operator
Viewer
```

---

### Organization Management

Include:

* Create Organization.
* update basic Organization profile.
* suspend Organization.
* list accessible Organizations.
* enforce Organization status.

Exclude advanced billing and plan automation.

---

### Application Registry

Include:

* Projects.
* Applications.
* Environments.
* active-to-archived Registry lifecycle.
* required Application operational criticality.
* tenant-scoped listing and retrieval.

Project and Application settings, Application classification and metadata, ownership records, Environment settings and criticality, and advanced lifecycle states are deferred.

This hierarchy provides scope for telemetry, Alerts, and Incidents.

---

### Event Ingestion

Include:

* Authenticated ingestion endpoint.
* single Event ingestion.
* bounded batch ingestion.
* canonical Event normalization.
* idempotency.
* immutable Event persistence.
* source attribution.
* basic filtering and pagination.

Initial Event types may focus on:

```text
application.error
application.health_failed
application.health_recovered
deployment.completed
custom.operational_event
```

---

### Signal Derivation

Include deterministic Signals for:

* Repeated application errors.
* health-check failure.
* service recovery.
* elevated error frequency.
* deployment detection.

Advanced statistical anomaly detection is deferred.

---

### Alert Management

Include:

* Basic Alert Rules.
* Signal evaluation.
* Alert creation.
* fingerprint-based deduplication.
* repeated Alert occurrences.
* severity.
* acknowledgement.
* suppression.
* resolution.
* Alert timeline and evidence.

A visual rule builder is not required.

---

### Incident Management

Include:

* Manual Incident creation.
* Incident creation from an Alert.
* severity and priority.
* acknowledgement.
* commander assignment.
* responders.
* timeline entries.
* evidence references.
* mitigation.
* resolution.
* closure.

Advanced response automation is deferred.

---

### Notifications

Include:

* Email Notification Channel.
* basic Templates.
* Alert and Incident Notifications.
* asynchronous delivery.
* bounded retries.
* delivery history.
* failure visibility.

SMS, chat platforms, and advanced escalation policies are deferred.

---

### Audit and History

Include Audit Records for:

* Membership and permission changes.
* Application hierarchy changes.
* Alert acknowledgement and resolution.
* Incident lifecycle changes.
* Notification configuration.
* privileged operations.

Operational histories remain available through their owning modules.

---

### Operational Interface

The MVP interface should provide:

* Login.
* Organization selection.
* Project, Application, and Environment management.
* Event list and detail.
* Alert list and detail.
* Incident list and detail.
* Notification delivery status.
* basic system-health visibility.

The interface may be simple and function-focused.

---

### Search

Include database-backed filtering for:

* Events.
* Alerts.
* Incidents.
* Applications.

A dedicated Search engine is not required for the MVP.

The Search module contract remains available for later evolution.

---

## Required Platform Foundations

The MVP requires:

```text
Laravel application
PostgreSQL
Redis
Docker Compose
Queue Workers
Scheduler
Outbox publisher (required immediately before the first asynchronous consumer; durable Outbox persistence is sufficient for Milestone 5)
Audit foundation
Structured Logs
Health endpoints
CI pipeline
Automated backups
```

A single-server production topology is acceptable initially.

---

## Deferred Capabilities

The following are intentionally deferred:

* Public Plugin marketplace.
* multiple production Plugins.
* autonomous AI Actions.
* advanced AI Agents.
* visual Workflow designer.
* complex Workflow orchestration.
* semantic and vector Search.
* dedicated Search infrastructure.
* mobile application.
* SMS delivery.
* multi-region deployment.
* database sharding.
* customer-configurable dashboards.
* advanced analytics.
* enterprise SSO.
* complex billing automation.
* public extension SDK.

Deferred capabilities remain documented but are not MVP dependencies.

---

## First Integration

The MVP should support one practical telemetry integration.

Recommended first option:

```text
Generic HTTP Event ingestion API
+
small Laravel SDK or Composer package
```

The SDK may provide:

* Exception reporting.
* health events.
* deployment events.
* custom Event submission.
* automatic Application and Environment identifiers.

This proves Sentinel with a real application before building a broad Plugin ecosystem.

---

## Delivery Milestones

### Milestone 1 — Development Foundation

Deliver:

* Laravel repository.
* Docker environment.
* PostgreSQL and Redis.
* CI checks.
* test framework.
* base module structure.

Success:

```text
Application, Worker, and scheduler run locally.
```

---

### Milestone 2 — Shared Platform Foundation

Deliver:

* Shared Kernel identifiers and time abstractions.
* correlation and causation identifiers.
* Tenant Context.
* transaction, optimistic-concurrency, Outbox, and Audit foundations.

Success:

```text
Protected operations use framework-independent shared platform primitives.
```

---

### Milestone 3 — Secure Tenant Foundation

Deliver:

* Users.
* Organizations.
* Memberships.
* Roles and permissions.
* Tenant Context.
* tenant-isolation tests.

Success:

```text
Two Organizations cannot access each other’s Resources.
```

---

### Milestone 4 — Project and Application Registry

Deliver:

* Projects.
* Applications.
* Environments.
* required Application criticality and active-to-archived Registry lifecycle.
* management APIs and UI.

Success:

```text
An Organization can register the systems it operates.
```

---

### Milestone 5 — Telemetry Intake

Deliver:

* API credentials.
* Event ingestion.
* canonical normalization.
* idempotency.
* Event storage.
* Event browsing.

Milestone 5 durably persists `event.accepted` Outbox records but does not require the dispatcher because it has no consumer. The Outbox publisher is a shared prerequisite immediately before Milestone 6 begins consuming `event.accepted`.

Success:

```text
A real application can send trusted Events to Sentinel.
```

---

### Milestone 6 — Alerting

Deliver:

* Outbox publisher before the first `event.accepted` consumer.
* Signal derivation.
* basic Alert Rules.
* Alert lifecycle.
* deduplication.
* evidence timeline.

Success:

```text
Operational Events produce actionable, non-duplicated Alerts.
```

---

### Milestone 7 — Incident Response

Deliver:

* Incident creation from Alerts.
* responder coordination.
* timeline.
* mitigation and resolution.
* basic Incident UI.

Success:

```text
Operators can coordinate and document a complete response.
```

---

### Milestone 8 — Notifications

Deliver:

* Email Channel.
* Templates.
* delivery Workers.
* retry behavior.
* delivery status.

Success:

```text
Relevant operators receive Alert and Incident updates.
```

---

### Milestone 9 — Internal Production Release

Deliver:

* Production deployment.
* monitoring.
* backup verification.
* security review.
* load testing.
* operational runbooks.

Success:

```text
Sentinel monitors at least one real application safely.
```

---

## MVP Acceptance Scenario

The MVP should pass this scenario:

1. A User creates an Organization.
2. The User creates a Project, Application, and production Environment.
3. Sentinel issues an ingestion credential.
4. The application sends repeated failure Events.
5. Sentinel accepts each unique Event.
6. Sentinel derives a failure Signal.
7. An Alert Rule creates one deduplicated Alert.
8. An operator receives an email.
9. The operator acknowledges the Alert.
10. The operator creates an Incident.
11. A commander and responders are assigned.
12. mitigation is recorded.
13. a recovery Event is received.
14. the Alert is resolved.
15. the Incident is resolved and closed.
16. the complete history remains searchable and auditable.

---

## MVP Quality Requirements

The MVP must include:

* Tenant-isolation tests.
* authorization tests.
* repository integration tests.
* Event idempotency tests.
* Queue retry tests.
* Outbox tests.
* optimistic concurrency tests.
* API contract tests.
* backup restoration test.
* basic performance test.
* security-sensitive Log redaction.

A feature without required tests is not complete.

---

## Initial Capacity Target

The first release should support a modest internal workload such as:

```text
10–50 Organizations
100–500 Applications
10–50 Events per second sustained
short bursts above normal ingestion
thousands of active Alerts and Incidents
multiple concurrent Workers
```

These are engineering targets, not public commitments.

Actual scaling decisions must follow measured usage.

---

## Product Validation Metrics

Useful MVP metrics include:

* Organizations actively using Sentinel.
* Applications sending Events.
* Event ingestion success rate.
* Event-to-Alert processing latency.
* duplicate Alert reduction.
* Alerts acknowledged.
* Incidents created and resolved.
* Notification delivery success.
* weekly active operators.
* operational failures discovered through Sentinel.

The strongest validation is repeated use during real operational problems.

---

## Scope Control

A capability enters the MVP only when it is required to complete the primary operational flow.

New ideas should be evaluated against:

```text
Does this help ingest, detect, alert, respond, or learn?
```

When the answer is no, the capability should normally be deferred.

---

## MVP Completion Criteria

The MVP is complete when:

* One real application sends production telemetry.
* tenant isolation has been independently tested.
* Alerts are created from Signals.
* Incidents can be managed end to end.
* Notifications are delivered reliably.
* failures and retries are observable.
* production backups are restorable.
* the platform can be deployed through CI/CD.
* no deferred capability is required for the core flow.

---

## Business Rules

* The MVP proves one complete operational workflow.
* secure multi-tenancy is mandatory from the first release.
* generic Event ingestion is the first integration.
* deterministic detection precedes advanced AI analysis.
* email is the first Notification Channel.
* PostgreSQL filtering is sufficient for initial Search.
* Plugins, Agents, and advanced Workflows are deferred.
* the first release may use a low-cost single-server topology.
* every milestone delivers tested usable behavior.
* scope expands only after the core flow is validated with real usage.

---

## Related Documents

* `docs/implementation/00-implementation-plan.md`
* `docs/implementation/01-repository-structure.md`
* `docs/modules/00-module-architecture.md`
* `docs/modules/06-event-module.md`
* `docs/modules/07-alert-module.md`
* `docs/modules/08-incident-module.md`
* `docs/modules/12-notification-module.md`
* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/10-release-strategy.md`
