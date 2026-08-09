# Project Module

## Purpose

The Project module groups related Applications and operational work within an Organization.

A Project provides organizational scope but is not an independent tenant boundary.

---

## Owns

The module owns:

* Project.
* Project lifecycle.
* Project profile.
* Project settings.
* Project-level operational defaults.
* Project ownership references.
* Project history.

Applications and Environments are owned by their respective modules.

---

## Responsibilities

The module is responsible for:

* Creating Projects within an Organization.
* maintaining Project identity and metadata.
* managing lifecycle transitions.
* defining Project-level defaults.
* validating Organization ownership.
* exposing Project status to dependent modules.
* publishing Project lifecycle events.

---

## Does Not Own

The module does not own:

* Organization lifecycle.
* Memberships or permissions.
* Applications.
* Environments.
* Events, Alerts, or Incidents.
* Project team membership unless introduced through a separate access-scope model.
* external credentials.

The module must not modify Resources owned by other modules directly.

---

## Project Resource

Important attributes include:

```text
id
organization_id
name
slug
description
status
created_by_identity_id
created_at
updated_at
archived_at
version
```

The primary identifier uses UUIDv7.

The Project slug is unique within its Organization.

---

## MVP Lifecycle

Projects are created with the following status:

```text
active
```

The supported MVP transition is:

```text
active → archived
```

Suspension, reactivation, restricted, provisioning, deletion, and restoration states are deferred.

---

## Active Project

An Active Project may:

* Contain Applications.
* receive new operational Resources.
* participate in Search and reporting.
* provide defaults to Applications.
* be used as an authorization scope.

All operations remain subject to Organization status and permissions.

---

## Archived Project

An Archived Project:

* Cannot receive new Applications.
* cannot accept normal configuration changes.
* remains available for authorized historical access.
* retains related operational history.
* follows retention and Legal Hold requirements.

Archiving a Project does not automatically delete its Applications or operational data.

---

## Commands

Primary commands include:

```text
CreateProject
RenameProject
ChangeProjectSlug
UpdateProjectDescription
UpdateProjectSettings
ArchiveProject
```

Commands require Organization context and optimistic concurrency where applicable.

## Create Project

The first Project slice creates an active Project through `CreateProject`.

It requires:

* an authenticated active Organization Membership and resolved Tenant Context;
* the `project.create` permission;
* `name`, `slug`, and an optional `description`;
* an `Idempotency-Key` scoped by Organization, acting identity, operation, key, and normalized request hash.

The server owns the Organization reference, UUIDv7 identifier, active status, creator identity, timestamps, version `1`, and `archived_at = null`. Creation does not use an expected version. The operation must atomically publish `project.created`, record the Outbox message, and record Audit action `project.create`.

---

## Queries

Primary queries include:

```text
GetProject
GetProjectBySlug
ListProjects
GetProjectSettings
GetProjectStatus
```

Queries must preserve Organization and authorization scope.

---

## Published Events

The module publishes:

```text
project.created
project.renamed
project.settings_updated
project.archived
```

Events must include:

```text
organization_id
project_id
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
organization.reactivated
organization.archived
```

Organization lifecycle changes may restrict Project operations without changing every Project’s authoritative lifecycle state.

Consumers must remain idempotent.

---

## Organization Integration

Every Project belongs to exactly one Organization.

Project creation requires:

* Existing Organization.
* Active Organization status.
* authorized identity.

A Project cannot move between Organizations.

---

## Application Integration

Applications reference one Project.

The Application module must verify:

* Project exists.
* Project belongs to the active Organization.
* Project is Active.
* the caller may create or access Applications in that Project.

The Project module must not write Application state.

---

## Settings

Project settings are a separate capability and are not part of Project creation.

Project settings may define defaults such as:

* Default timezone.
* operational ownership metadata.
* default retention preferences.
* default Notification behavior.
* default Incident severity configuration.
* labeling conventions.

Application-specific values may override Project defaults where Policy permits.

Secrets must not be stored in Project settings.

---

## Authorization

Typical permissions include:

```text
project.create
project.read
project.update
project.archive
project.application.create
```

Authorization may also consider:

* Organization Membership.
* Project scope.
* Project status.
* authentication strength for high-risk changes.

Role names must not be embedded in Project domain logic.

---

## Scope

Project may act as an authorization scope for Resources such as:

* Applications.
* Environments.
* Events.
* Alerts.
* Incidents.
* Knowledge.

Resource-owning modules remain responsible for enforcing that scope.

---

## Tenant Isolation

All Project operations require:

```text
organization_id
project_id
```

Queries by `project_id` alone are prohibited.

Cross-Organization Project references must be rejected even when the identifier exists.

---

## Repository Interfaces

The module owns repository contracts such as:

```text
ProjectRepository
ProjectSettingsRepository
ProjectHistoryRepository
```

Other modules should use Project queries or contracts rather than changing Project persistence directly.

---

## Transactions

Project state changes should atomically commit:

* Project state.
* lifecycle history where required.
* Audit metadata.
* Outbox Records.

Dependent module reactions occur through events.

---

## Optimistic Concurrency

Project updates should use a Resource Version.

Concurrency protection is required for:

* Settings changes.
* metadata updates.
* archival.

Conflicting writes must not silently overwrite newer changes.

---

## Project Archival

Archiving a Project should:

1. Validate that the Project is Active.
2. authorize the operation.
3. mark the Project Archived.
4. preserve related Resources.
5. publish `project.archived`.
6. prevent new child Resources.

Application and operational modules decide how existing child Resources behave.

---

## Search Integration

The Search module may consume Project events to maintain derived Project documents.

Search Projections may include:

* Name.
* slug.
* description.
* status.
* Organization reference.

Search remains derived and must not become authoritative Project state.

---

## Audit Requirements

Audit Records are required for:

* Project creation.
* metadata changes.
* settings changes.
* archival.
* privileged access to archived Projects.

Audit payloads must exclude secrets and unrestricted tenant data.

---

## Testing Requirements

Tests must cover:

* Project creation.
* unique slug within an Organization.
* identical slugs across different Organizations.
* inactive Organization rejection.
* cross-Organization access rejection.
* metadata updates.
* optimistic concurrency.
* archival.
* prevention of new child Resources after archival.
* event publication.
* authorization scope.

---

## Prohibited Practices

The following are prohibited:

* Treating Project as an independent tenant.
* moving Projects between Organizations.
* querying Projects without Organization scope.
* storing Applications inside the Project module.
* deleting child Resources during archival.
* storing credentials in Project settings.
* allowing archived Projects to accept new Resources.
* modifying Organization state from this module.
* bypassing Application module contracts.
* using Project slug as an authorization control.

---

## Business Rules

* Every Project belongs to exactly one Organization.
* Project is an organizational and authorization scope, not a tenant boundary.
* Project identifiers use UUIDv7.
* Project slugs are unique only within an Organization.
* Projects cannot move between Organizations.
* archived Projects retain historical data but reject normal writes.
* Applications are owned by the Application module.
* Project defaults may influence but do not own child Resource configuration.
* Project state changes are versioned, auditable, and event-producing.
* all Project access remains Organization-scoped.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/02-organization-module.md`
* `docs/modules/04-application-module.md`
* `docs/modules/14-identity-access-module.md`
* `docs/database/03-organization-project-schema.md`
* `docs/api/07-projects-api.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/04-authorization-enforcement.md`
