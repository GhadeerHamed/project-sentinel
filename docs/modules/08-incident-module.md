# Incident Module

## Purpose

The Incident module owns coordinated response to significant operational problems.

It turns one or more Alerts or manually reported conditions into a managed Incident with responders, evidence, timeline, Actions, and resolution.

---

## Owns

* Incident.
* Incident lifecycle.
* Incident severity and priority.
* Incident assignment.
* responder participation.
* Incident timeline.
* Incident evidence references.
* Incident Actions.
* resolution summary.
* Post-Incident Review reference.

---

## Does Not Own

* Events and Signals.
* Alert lifecycle.
* Notification delivery.
* Workflow execution.
* Knowledge publication.
* Agent execution.
* Application or Environment configuration.

The module references these Resources through identifiers or explicit contracts.

---

## Incident Resource

Important attributes:

```text
id
organization_id
application_id
environment_id
reference
title
description
status
severity
priority
commander_identity_id
opened_at
acknowledged_at
resolved_at
closed_at
version
```

The primary identifier uses UUIDv7.

Human-readable references may use a format such as:

```text
INC-2026-00421
```

---

## Lifecycle

Suggested states:

```text
open
acknowledged
investigating
mitigated
resolved
closed
```

Typical progression:

```text
open
→ acknowledged
→ investigating
→ mitigated
→ resolved
→ closed
```

A resolved Incident may be reopened when the operational condition returns.

Invalid transitions must be rejected.

---

## Commands

```text
CreateIncident
CreateIncidentFromAlert
AcknowledgeIncident
AssignIncidentCommander
AddIncidentResponder
RemoveIncidentResponder
ChangeIncidentSeverity
StartInvestigation
RecordMitigation
ResolveIncident
ReopenIncident
CloseIncident
AddIncidentTimelineEntry
AttachIncidentEvidence
CreateIncidentAction
CompleteIncidentAction
```

Every state-changing command requires authorization and current Resource Version.

---

## Queries

```text
GetIncident
ListIncidents
GetIncidentTimeline
GetIncidentEvidence
GetIncidentResponders
ListIncidentActions
GetIncidentSummary
```

Queries must preserve Organization, Application, Environment, and sensitivity scope.

---

## Published Events

```text
incident.created
incident.acknowledged
incident.commander_assigned
incident.responder_added
incident.severity_changed
incident.investigation_started
incident.mitigated
incident.resolved
incident.reopened
incident.closed
incident.action_created
incident.action_completed
```

Events must include:

```text
organization_id
incident_id
application_id
environment_id
event_id
occurred_at
correlation_id
causation_id
event_version
```

---

## Consumed Events

The module may consume:

```text
alert.created
alert.updated
alert.resolved
application.archived
environment.disabled
environment.archived
organization.suspended
organization.archived
```

Alert events may propose Incident creation or update, but must not bypass Incident rules.

Consumers must be idempotent.

---

## Alert Integration

An Incident may reference multiple Alerts.

Creating an Incident from an Alert must verify:

* Matching Organization.
* matching Application and Environment scope.
* Alert existence.
* Alert eligibility.
* duplicate Incident Policy.
* authorization or approved automation.

The Incident module must not modify Alert state directly.

---

## Evidence

Incident evidence may reference:

* Events.
* Signals.
* Alerts.
* Logs.
* files.
* Agent findings.
* Plugin data.
* manual observations.

Evidence relationships must preserve:

```text
organization_id
source_type
source_id
added_by
added_at
```

Evidence should normally be referenced rather than copied.

---

## Timeline

The Incident timeline records significant facts such as:

* Incident creation.
* acknowledgement.
* responder changes.
* investigation findings.
* mitigation Actions.
* severity changes.
* external communications.
* resolution.
* reopening.
* closure.

Timeline history is append-only.

---

## Responders

Incident participation may include:

```text
commander
responder
observer
communications_lead
```

The Identity and Access module validates identities and Organization access.

The Incident module owns Incident-specific participation.

---

## Incident Actions

Incident Actions represent follow-up or response work.

Examples:

```text
Restart affected service
Disable unsafe Plugin
Rotate compromised credential
Deploy corrective release
Write Post-Incident Review
```

Actions must include:

```text
owner_identity_id
status
priority
due_at
completed_at
```

Complex reusable orchestration remains owned by the Workflow module.

---

## Severity and Priority

Severity represents operational impact.

Priority represents response urgency.

Suggested severity values:

```text
critical
high
medium
low
```

Severity may consider:

* Alert severity.
* Environment criticality.
* customer impact.
* affected Applications.
* security implications.
* duration.

The Incident module owns the final Incident severity.

---

## Notification Integration

The module may request Notifications for:

* Incident creation.
* commander assignment.
* severity escalation.
* mitigation.
* resolution.
* reopening.

It must not call delivery providers directly.

---

## Workflow Integration

Workflows may coordinate:

* response checklists.
* approval-controlled remediation.
* escalation.
* scheduled follow-up.
* Post-Incident Review tasks.

Workflow Actions must invoke Incident commands and cannot write Incident state directly.

---

## Agent Integration

Agents may:

* summarize evidence.
* correlate Alerts and Events.
* propose severity.
* recommend remediation.
* draft Incident updates.

Agent output is advisory until validated through an authorized Incident command.

---

## Knowledge Integration

Resolved Incidents may produce reusable Knowledge.

The Knowledge module may consume:

```text
incident.resolved
incident.closed
```

The Incident module does not publish Knowledge directly.

---

## Repository Interfaces

```text
IncidentRepository
IncidentTimelineRepository
IncidentEvidenceRepository
IncidentResponderRepository
IncidentActionRepository
```

Other modules must not modify these repositories directly.

---

## Transactions

Incident state changes should atomically commit:

* Incident state.
* timeline entry.
* responder or Action changes.
* required Audit metadata.
* Outbox Records.

Notifications and external provider calls occur asynchronously.

---

## Optimistic Concurrency

Concurrency protection is required for:

* Lifecycle transitions.
* commander assignment.
* severity changes.
* mitigation and resolution.
* responder changes.
* concurrent automation.

Conflicting operations must return a concurrency conflict.

---

## Authorization

Typical permissions:

```text
incident.create
incident.read
incident.acknowledge
incident.assign
incident.update
incident.mitigate
incident.resolve
incident.close
incident.evidence.manage
incident.action.manage
```

Production and security Incidents may require stronger authorization.

---

## Tenant Isolation

Every Incident operation requires:

```text
organization_id
incident_id
```

All linked Alerts, Events, evidence, responders, and Actions must belong to the same Organization.

---

## Audit Requirements

Audit Records are required for:

* Incident creation.
* commander assignment.
* severity changes.
* mitigation.
* resolution.
* reopening.
* closure.
* sensitive evidence access.
* privileged response Actions.

Routine timeline entries remain part of immutable Incident history.

---

## Prohibited Practices

The Incident module must not:

* Mutate Alerts or Events.
* send provider Notifications directly.
* execute remediation outside approved domain or Workflow contracts.
* accept cross-Organization evidence.
* delete timeline history.
* treat Agent recommendations as authoritative.
* bypass authorization through automation.
* close an Incident without a resolution record.

---

## Business Rules

* Every Incident belongs to one Organization and Application.
* Incidents may reference multiple Alerts and evidence sources.
* Incident timeline history is append-only.
* the Incident module owns final severity, lifecycle, and responder assignment.
* Alert resolution does not automatically close an Incident.
* Agent and Workflow operations use authorized Incident commands.
* resolution and closure are separate lifecycle steps.
* all state changes are versioned, auditable, and event-producing.

---

## Related Documents

* `docs/modules/06-event-module.md`
* `docs/modules/07-alert-module.md`
* `docs/modules/09-knowledge-module.md`
* `docs/modules/10-agent-module.md`
* `docs/modules/12-notification-module.md`
* `docs/modules/17-workflow-module.md`
* `docs/database/07-incident-schema.md`
* `docs/api/12-incidents-api.md`
* `docs/security/09-incident-response.md`
