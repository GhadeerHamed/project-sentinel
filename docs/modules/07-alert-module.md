# Alert Module

## Purpose

The Alert module owns Alert rules, Alert evaluation, Alert lifecycle, acknowledgement, suppression, and resolution.

It converts detected Signals into actionable operational conditions.

---

## Owns

The module owns:

* Alert Rule.
* Alert Rule Version.
* Alert evaluation result.
* Alert.
* Alert occurrence.
* Alert acknowledgement.
* Alert suppression state.
* Alert lifecycle history.
* Alert evidence references.

---

## Responsibilities

The module is responsible for:

* Defining Alert Rules.
* evaluating Signals against active Rules.
* creating or updating Alerts.
* deduplicating repeated conditions.
* maintaining Alert lifecycle.
* preserving Signal and Event evidence.
* publishing Alert lifecycle events.
* requesting Notifications and Incident escalation through explicit contracts.

---

## Does Not Own

The module does not own:

* Events or Signals.
* Incident lifecycle.
* Notification delivery.
* Workflow execution.
* Application or Environment configuration.
* Agent execution.
* Plugin synchronization.

It must not modify Incident or Notification state directly.

---

## Alert Rule

An Alert Rule defines when operational evidence should produce an Alert.

Important attributes include:

```text
id
organization_id
application_id
environment_id
name
status
condition
severity_mapping
deduplication_window
suppression_policy
version
created_at
updated_at
```

Rule changes create a new Version where reproducibility is required.

---

## Alert Resource

Important attributes include:

```text
id
organization_id
application_id
environment_id
rule_id
status
severity
title
fingerprint
first_detected_at
last_detected_at
acknowledged_at
resolved_at
version
```

The primary identifier uses UUIDv7.

Alerts must preserve references to their originating Signals and evidence.

---

## Lifecycle

Suggested states:

```text
open
acknowledged
suppressed
resolved
```

Typical transitions:

```text
open → acknowledged
open → suppressed
open → resolved
acknowledged → suppressed
acknowledged → resolved
suppressed → open
suppressed → resolved
```

Invalid transitions must be rejected.

---

## Commands

Primary commands include:

```text
CreateAlertRule
UpdateAlertRule
EnableAlertRule
DisableAlertRule
EvaluateSignal
AcknowledgeAlert
SuppressAlert
ReopenAlert
ResolveAlert
AttachAlertEvidence
```

Commands require Tenant Context, authorization, and current Resource Version where applicable.

---

## Queries

Primary queries include:

```text
GetAlert
ListAlerts
GetAlertTimeline
GetAlertEvidence
GetAlertRule
ListAlertRules
GetAlertEvaluationHistory
```

Queries must preserve Organization, Application, Environment, and sensitivity scope.

---

## Published Events

The module publishes:

```text
alert_rule.created
alert_rule.updated
alert_rule.enabled
alert_rule.disabled
alert.created
alert.updated
alert.acknowledged
alert.suppressed
alert.reopened
alert.resolved
```

Events should include:

```text
organization_id
alert_id
application_id
environment_id
rule_id
event_id
occurred_at
correlation_id
causation_id
event_version
```

---

## Consumed Events

The module primarily consumes:

```text
signal.detected
signal.updated
signal.expired
application.archived
environment.disabled
environment.archived
organization.suspended
organization.archived
```

Consumers must remain idempotent.

---

## Signal Evaluation

When `signal.detected` is received, the module should:

1. Validate Organization and Resource scope.
2. load applicable active Alert Rules.
3. evaluate each Rule against normalized Signal data.
4. calculate severity and fingerprint.
5. create or update the matching Alert.
6. preserve evidence references.
7. publish resulting Alert events.

Signal processing must not depend on raw provider-specific payloads.

---

## Deduplication

Operationally equivalent conditions should update an existing Alert rather than create unlimited duplicates.

Deduplication may use:

```text
organization_id
rule_id
application_id
environment_id
fingerprint
active lifecycle state
```

Deduplication rules must be deterministic and versioned when changed materially.

---

## Alert Occurrences

Repeated matching Signals may create Alert occurrences.

An occurrence records:

```text
alert_id
signal_id
detected_at
severity
evidence_reference
```

Occurrences preserve history without replacing the Alert’s current lifecycle state.

---

## Severity

Suggested Alert severity values:

```text
critical
high
medium
low
informational
```

Severity may consider:

* Rule configuration.
* Signal severity.
* Environment criticality.
* repetition frequency.
* affected operational scope.

Incident priority remains owned by the Incident module.

---

## Acknowledgement

Acknowledgement records that an authorized identity has taken ownership or reviewed the Alert.

Acknowledgement should include:

```text
alert_id
acknowledged_by
acknowledged_at
note
```

Acknowledgement does not resolve the underlying operational condition.

---

## Suppression

Suppression temporarily prevents normal Alert escalation or repeated Notification behavior.

Suppression must include:

* Reason.
* initiating identity.
* start time.
* optional expiration.
* scope.
* Audit reference.

Suppression must not delete evidence or Alert history.

---

## Resolution

An Alert may resolve when:

* The monitored condition has recovered.
* an authorized User resolves it manually.
* an owning Rule defines an automatic recovery condition.
* an approved automation completes the resolution command.

Resolution must record its source and preserve history.

---

## Incident Integration

The Incident module may consume:

```text
alert.created
alert.updated
alert.resolved
```

An Incident may reference one or more Alerts.

The Alert module must not:

* Create Incident rows directly.
* change Incident status.
* assign Incident responders.
* decide Incident priority authoritatively.

---

## Notification Integration

The Alert module may request Notifications through an explicit Notification command or event.

Example:

```text
alert.created
↓
Notification policy evaluation
↓
Notification delivery
```

The Alert module does not call email, SMS, or external providers directly.

---

## Workflow Integration

Workflows may react to Alert events or issue authorized Alert commands.

Workflow execution must not bypass:

* Alert lifecycle rules.
* authorization.
* optimistic concurrency.
* suppression Policy.
* Audit requirements.

---

## Agent Integration

Agents may:

* Summarize Alert evidence.
* correlate related operational facts.
* recommend acknowledgement or escalation.
* propose remediation Actions.

Agents must not mutate Alert state without invoking authorized Alert commands.

---

## Repository Interfaces

The module owns repository contracts such as:

```text
AlertRepository
AlertRuleRepository
AlertOccurrenceRepository
AlertEvaluationRepository
AlertEvidenceRepository
```

Other modules must not write Alert persistence directly.

---

## Transactions

Alert state changes should atomically commit:

* Alert state.
* occurrence or lifecycle history.
* evidence relationships.
* Audit metadata where required.
* Outbox Records.

External Notifications and provider calls occur asynchronously.

---

## Optimistic Concurrency

Alert lifecycle changes should use a Resource Version.

Concurrency protection is especially required for:

* Acknowledgement.
* suppression.
* reopening.
* resolution.
* concurrent Signal evaluation.

Conflicting state changes must not silently overwrite each other.

---

## Authorization

Typical permissions include:

```text
alert.read
alert.acknowledge
alert.suppress
alert.resolve
alert.rule.create
alert.rule.update
alert.rule.enable
alert.rule.disable
```

Authorization may consider:

* Organization Membership.
* Application and Environment scope.
* Environment criticality.
* Alert sensitivity.
* current Alert state.

---

## Tenant Isolation

All Alert operations require:

```text
organization_id
alert_id
```

Application- and Environment-scoped operations must validate parent ownership.

Signal, Rule, and evidence references must belong to the same Organization.

---

## Search Integration

The Search module may index selected Alert fields such as:

* Title.
* status.
* severity.
* Application.
* Environment.
* Rule.
* timestamps.
* responder references.

Search remains derived and must preserve authorization and tenant scope.

---

## Audit Requirements

Audit Records are required for:

* Alert Rule changes.
* acknowledgement.
* suppression.
* reopening.
* manual resolution.
* privileged evidence access.
* administrative re-evaluation.

High-volume automatic evaluations may use immutable operational history instead of one administrative Audit Record per evaluation.

---

## Testing Requirements

Tests must cover:

* Rule evaluation.
* Alert creation.
* deduplication.
* repeated occurrences.
* severity mapping.
* lifecycle transitions.
* invalid transition rejection.
* acknowledgement.
* suppression expiration.
* automatic and manual resolution.
* cross-tenant evidence rejection.
* duplicate event delivery.
* optimistic concurrency.
* event publication.

---

## Prohibited Practices

The following are prohibited:

* Creating Alerts directly from provider-specific payloads.
* mutating Signals or Events.
* writing Incident tables.
* delivering Notifications directly.
* deleting Alert history during resolution.
* acknowledging or resolving without authorization.
* deduplicating across Organizations.
* treating acknowledgement as resolution.
* allowing Agent or Workflow execution to bypass Alert commands.
* querying Alerts without Organization scope.

---

## Business Rules

* Alerts are derived from trusted Signals.
* every Alert belongs to one Organization and Application.
* Alert Rules are versioned when reproducibility requires it.
* repeated equivalent Signals update an existing active Alert where deduplication applies.
* Alert occurrences preserve repeated evidence.
* acknowledgement does not resolve an Alert.
* suppression preserves Alert history and evidence.
* the Incident module owns Incident creation and lifecycle.
* Notifications are requested through explicit integration contracts.
* Alert state changes are authorized, versioned, auditable, and event-producing.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/05-environment-module.md`
* `docs/modules/06-event-module.md`
* `docs/modules/08-incident-module.md`
* `docs/modules/12-notification-module.md`
* `docs/modules/17-workflow-module.md`
* `docs/database/06-alert-schema.md`
* `docs/api/11-alerts-api.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/adr/ADR-004-operational-processing-model.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
