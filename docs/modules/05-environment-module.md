# Environment Module

## Purpose

The Environment module owns deployment environments within Sentinel Applications.

An Environment represents a distinct operational runtime such as production, staging, testing, or development.

---

## Owns

The module owns:

* Environment.
* Environment lifecycle.
* Environment classification.
* Environment settings.
* Environment operational criticality.
* Environment history.

Runtime telemetry is owned by the Event module.

---

## Responsibilities

The module is responsible for:

* Creating Environments within Applications.
* maintaining Environment metadata.
* classifying operational criticality.
* managing lifecycle transitions.
* validating Application ownership.
* defining Environment-level operational defaults.
* publishing Environment lifecycle events.

---

## Does Not Own

The module does not own:

* Applications.
* deployment infrastructure.
* application releases.
* Events or Signals.
* Alerts or Incidents.
* Plugin credentials.
* Agent executions.
* runtime configuration secrets.

It must not modify Resources owned by other modules directly.

---

## Environment Resource

Important attributes include:

```text
id
organization_id
application_id
name
slug
type
status
criticality
settings
created_at
updated_at
archived_at
version
```

The primary identifier uses UUIDv7.

The slug is unique within its Application.

---

## Environment Type

Suggested types:

```text
production
staging
testing
development
sandbox
other
```

Environment type describes intended usage.

It must not be used as the sole authorization control.

---

## Criticality

Suggested criticality levels:

```text
critical
high
medium
low
```

Criticality may influence:

* Alert severity.
* Notification routing.
* approval requirements.
* Agent Action restrictions.
* retention defaults.
* operational prioritization.

Owning modules remain responsible for applying these rules.

---

## Lifecycle

Suggested states:

```text
active
disabled
archived
```

Supported transitions:

```text
active → disabled
disabled → active
active → archived
disabled → archived
```

Archived Environments should not return to normal operation without an explicit recovery policy.

---

## Active Environment

An Active Environment may:

* Receive Events.
* participate in Signal and Alert processing.
* create Incidents.
* run approved Plugins and Agents.
* appear in Search and dashboards.

All operations remain subject to parent Resource status and authorization.

---

## Disabled Environment

A Disabled Environment:

* Rejects normal Event ingestion.
* prevents new operational executions.
* retains existing history.
* remains available for authorized investigation.
* may permit controlled maintenance operations.

Disabling is reversible.

---

## Archived Environment

An Archived Environment:

* Rejects new telemetry.
* cannot receive normal configuration changes.
* retains historical Resources.
* follows retention and Legal Hold rules.
* remains accessible only for authorized historical use.

Archival does not delete operational data.

---

## Commands

Primary commands include:

```text
CreateEnvironment
RenameEnvironment
ChangeEnvironmentSlug
ChangeEnvironmentType
ChangeEnvironmentCriticality
UpdateEnvironmentSettings
DisableEnvironment
EnableEnvironment
ArchiveEnvironment
```

Commands require Tenant Context, authorization, and valid parent ownership.

---

## Queries

Primary queries include:

```text
GetEnvironment
GetEnvironmentBySlug
ListEnvironments
GetEnvironmentStatus
GetEnvironmentSettings
GetEnvironmentCriticality
```

Queries must remain Organization- and Application-scoped.

---

## Published Events

The module publishes:

```text
environment.created
environment.renamed
environment.type_changed
environment.criticality_changed
environment.settings_updated
environment.disabled
environment.enabled
environment.archived
```

Events must include:

```text
organization_id
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
organization.suspended
organization.archived
project.archived
application.archived
```

Parent lifecycle changes restrict Environment operations.

Consumers must remain idempotent.

---

## Application Integration

Every Environment belongs to exactly one Application.

Environment creation requires:

* Active Organization.
* active Project.
* active Application.
* matching Organization ownership.
* authorized identity.
* available plan capacity.

An Environment cannot move between Applications or Organizations.

---

## Event Integration

The Event module references an Environment when telemetry is environment-specific.

Before accepting an Event, the Event module should verify that the Environment:

* Exists.
* belongs to the expected Application and Organization.
* is Active.
* may receive telemetry.

The Environment module does not write Event data.

---

## Alert and Incident Integration

Alerts and Incidents may reference an Environment.

Environment criticality may contribute to:

* Alert severity calculation.
* Incident priority.
* escalation Policy.
* Notification routing.

The Alert and Incident modules remain authoritative for those decisions.

---

## Plugin and Agent Integration

Plugin Installations and Agent executions may be scoped to one or more Environments.

They must verify:

* Organization ownership.
* Application ownership.
* Environment status.
* granted Resource scope.
* production restrictions.

Production Environments may require stronger authorization or approval.

---

## Settings

Environment settings may define:

* Event ingestion preferences.
* Alert evaluation defaults.
* Notification routing.
* retention preferences.
* production protection rules.
* Agent and Plugin restrictions.
* operational ownership metadata.

Credentials and secrets must use secure secret references.

---

## Authorization

Typical permissions include:

```text
environment.create
environment.read
environment.update
environment.disable
environment.archive
environment.production.manage
```

Sensitive production changes may require:

* Strong authentication.
* elevated permission.
* approval.
* recent reauthentication.
* Audit recording.

---

## Tenant Isolation

All Environment operations require:

```text
organization_id
application_id
environment_id
```

Cross-Organization or cross-Application references must be rejected.

Environment identifiers alone never establish access.

---

## Repository Interfaces

The module owns repository contracts such as:

```text
EnvironmentRepository
EnvironmentSettingsRepository
EnvironmentHistoryRepository
```

Other modules use public Environment contracts rather than modifying Environment persistence directly.

---

## Transactions

Environment state changes should atomically commit:

* Environment state.
* lifecycle history.
* required Audit metadata.
* Outbox Records.

Dependent module reactions occur through published events.

---

## Optimistic Concurrency

Environment metadata, settings, criticality, and lifecycle changes should use a Resource Version.

Conflicting writes must return a concurrency conflict.

---

## Environment Disablement

Disabling an Environment should:

1. Validate current state.
2. authorize the operation.
3. mark the Environment Disabled.
4. publish `environment.disabled`.
5. reject new normal ingestion and executions.
6. preserve existing operational history.

Dependent modules decide how active work is paused or completed.

---

## Environment Archival

Archiving should:

1. Validate current state.
2. authorize the operation.
3. mark the Environment Archived.
4. publish `environment.archived`.
5. reject new operational activity.
6. retain history according to Policy.

---

## Search Integration

The Search module may maintain derived Environment documents containing:

* Name.
* slug.
* type.
* status.
* criticality.
* Application reference.
* operational ownership metadata.

Search remains derived and rebuildable.

---

## Audit Requirements

Audit Records are required for:

* Environment creation.
* type and criticality changes.
* settings changes.
* disablement and reactivation.
* archival.
* privileged production changes.

Sensitive configuration must be redacted.

---

## Testing Requirements

Tests must cover:

* Environment creation.
* parent ownership validation.
* inactive Application rejection.
* slug uniqueness within an Application.
* cross-tenant rejection.
* lifecycle transitions.
* criticality changes.
* production authorization.
* optimistic concurrency.
* Event ingestion eligibility.
* event publication.

---

## Prohibited Practices

The following are prohibited:

* Treating Environment as an independent tenant.
* moving Environments between Applications.
* querying by Environment ID without tenant scope.
* storing telemetry inside this module.
* storing plaintext credentials in settings.
* accepting telemetry for disabled or archived Environments.
* using Environment type as the only production safeguard.
* deleting history during archival.
* allowing other modules to write Environment tables.
* duplicating authoritative Environment state.

---

## Business Rules

* Every Environment belongs to one Application and Organization.
* Environment defines an operational runtime scope.
* Environment identifiers use UUIDv7.
* slugs are unique within an Application.
* Environments cannot move between Applications.
* disabled Environments retain history but reject normal activity.
* archived Environments are historical and read-only.
* production Environments may require stronger controls.
* Event, Alert, Incident, Agent, and Plugin modules reference but do not own Environments.
* state changes are versioned, auditable, and event-producing.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/04-application-module.md`
* `docs/modules/06-event-module.md`
* `docs/modules/07-alert-module.md`
* `docs/modules/08-incident-module.md`
* `docs/modules/10-agent-module.md`
* `docs/modules/11-plugin-module.md`
* `docs/database/04-application-environment-schema.md`
* `docs/api/09-environments-api.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/04-authorization-enforcement.md`
