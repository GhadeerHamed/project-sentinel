# Application Module

## Purpose

The Application module owns the systems monitored and managed by Project Sentinel.

An Application belongs to one Project and represents a logical software product, service, or workload.

---

## Owns

The module owns:

* Application.
* Application lifecycle.
* Application profile.
* Application settings.
* Application ownership metadata.
* Application operational defaults.
* Application history.

Environments are owned by the Environment module.

---

## Responsibilities

The module is responsible for:

* Creating Applications within Projects.
* maintaining Application metadata.
* managing lifecycle transitions.
* validating Project and Organization ownership.
* defining Application-level operational defaults.
* exposing Application status.
* publishing Application lifecycle events.

---

## Does Not Own

The module does not own:

* Projects.
* Environments.
* Events.
* Signals.
* Alerts.
* Incidents.
* Plugin credentials.
* Agent executions.
* Application runtime telemetry.

It must not modify Resources owned by other modules directly.

---

## Application Resource

Important attributes include:

```text
id
organization_id
project_id
name
slug
description
status
settings
created_at
updated_at
archived_at
version
```

The primary identifier uses UUIDv7.

The slug is unique within its Project.

---

## Lifecycle

Suggested states:

```text
active
archived
```

Supported transition:

```text
active → archived
```

Restoration may be introduced later through an explicit recovery command.

---

## Active Application

An Active Application may:

* Contain Environments.
* receive operational telemetry.
* participate in Alert and Incident processing.
* use Plugins and Agents.
* appear in Search and operational dashboards.

All operations remain subject to Organization, Project, and authorization state.

---

## Archived Application

An Archived Application:

* Cannot receive new Environments.
* cannot accept normal configuration changes.
* should reject new telemetry unless an explicit migration policy allows it.
* remains available for authorized historical access.
* retains related operational history.

Archival does not delete child Resources.

---

## Commands

Primary commands include:

```text
CreateApplication
RenameApplication
ChangeApplicationSlug
UpdateApplicationDescription
UpdateApplicationSettings
ArchiveApplication
```

Commands require:

* Tenant Context.
* valid Project ownership.
* authorization.
* optimistic concurrency where applicable.

---

## Queries

Primary queries include:

```text
GetApplication
GetApplicationBySlug
ListApplications
GetApplicationSettings
GetApplicationStatus
```

Queries must remain Organization- and Project-scoped.

---

## Published Events

The module publishes:

```text
application.created
application.renamed
application.settings_updated
application.archived
```

Events must include:

```text
organization_id
project_id
application_id
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
```

Parent lifecycle events may restrict Application operations without rewriting every Application state.

Consumers must remain idempotent.

---

## Project Integration

Every Application belongs to exactly one Project.

Application creation requires:

* Active Organization.
* active Project.
* matching Organization ownership.
* authorized identity.
* available plan capacity.

An Application cannot move between Projects or Organizations through a normal update.

---

## Environment Integration

The Environment module references one Application.

Environment creation must verify that the Application:

* Exists.
* belongs to the active Organization.
* belongs to the expected Project.
* is Active.

The Application module must not write Environment state.

---

## Operational Integration

Events, Alerts, Incidents, Knowledge, Plugins, Agents, and Workflows may reference an Application.

Those modules must preserve:

```text
organization_id
application_id
```

They must not copy or mutate authoritative Application state.

---

## Settings

Application settings may define defaults such as:

* Operational ownership.
* default severity behavior.
* Event ingestion preferences.
* Alert processing defaults.
* Notification routing defaults.
* retention preferences.
* Agent and Plugin availability.

Environment-level configuration may override approved defaults.

Secrets must not be stored in Application settings.

---

## Authorization

Typical permissions include:

```text
application.create
application.read
application.update
application.archive
application.environment.create
```

Authorization may consider:

* Organization Membership.
* Project scope.
* Application scope.
* Application state.
* authentication strength for sensitive changes.

---

## Tenant Isolation

All operations require:

```text
organization_id
application_id
```

Project-scoped operations also require:

```text
project_id
```

Cross-Organization or cross-Project references must be rejected.

Application IDs alone never establish access.

---

## Repository Interfaces

The module owns repository contracts such as:

```text
ApplicationRepository
ApplicationSettingsRepository
ApplicationHistoryRepository
```

Other modules use public Application contracts rather than modifying Application persistence directly.

---

## Transactions

Application state changes should atomically commit:

* Application state.
* history where required.
* Audit metadata.
* Outbox Records.

Reactions in other modules occur through published events.

---

## Optimistic Concurrency

Application metadata, settings, and lifecycle changes should use a Resource Version.

Conflicting changes must return a concurrency conflict rather than overwrite newer state.

---

## Application Archival

Archiving an Application should:

1. Validate current state.
2. authorize the operation.
3. mark the Application Archived.
4. preserve child and historical Resources.
5. publish `application.archived`.
6. reject new normal operational activity.

Dependent modules decide how their existing Resources behave.

---

## Search Integration

The Search module may maintain derived Application documents containing:

* Name.
* slug.
* description.
* status.
* Project reference.
* operational ownership metadata.

Search remains derived and rebuildable.

---

## Audit Requirements

Audit Records are required for:

* Application creation.
* metadata changes.
* settings changes.
* archival.
* privileged access to archived Applications.

Sensitive configuration must be redacted.

---

## Testing Requirements

Tests must cover:

* Application creation.
* Project and Organization ownership.
* inactive Project rejection.
* unique slug within a Project.
* identical slugs in different Projects.
* cross-tenant access rejection.
* settings updates.
* optimistic concurrency.
* archival.
* prevention of new child Resources after archival.
* event publication.
* authorization scope.

---

## Prohibited Practices

The following are prohibited:

* Moving Applications between Organizations.
* querying Applications by ID without tenant scope.
* storing Environments inside this module.
* storing credentials in Application settings.
* deleting operational history during archival.
* accepting normal telemetry for archived Applications.
* modifying Project state from this module.
* allowing other modules to write Application tables.
* using Application slug as authorization.
* duplicating authoritative Application state in dependent modules.

---

## Business Rules

* Every Application belongs to one Project and one Organization.
* Application is an operational Resource scope, not a tenant boundary.
* Application identifiers use UUIDv7.
* slugs are unique within a Project.
* Applications cannot move between Organizations.
* archived Applications retain history but reject normal writes.
* Environments are owned by the Environment module.
* operational modules reference Applications without owning them.
* state changes are versioned, auditable, and event-producing.
* all Application access remains tenant-scoped.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/02-organization-module.md`
* `docs/modules/03-project-module.md`
* `docs/modules/05-environment-module.md`
* `docs/modules/06-event-module.md`
* `docs/modules/14-identity-access-module.md`
* `docs/database/04-application-environment-schema.md`
* `docs/api/08-applications-api.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/04-authorization-enforcement.md`
