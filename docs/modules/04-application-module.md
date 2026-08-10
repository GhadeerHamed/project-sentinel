# Application Module

## Purpose

The Application module owns the systems monitored and managed by Project Sentinel.

An Application belongs to one Project and represents a logical software product, service, or workload.

---

## Owns

The module owns:

* Application.
* Application lifecycle.

Environments are owned by the Environment module.

Application profile and metadata updates, settings, ownership metadata, operational defaults, and separate lifecycle history are deferred Registry capabilities.

---

## Responsibilities

The module is responsible for:

* Creating Applications within Projects.
* Listing and retrieving Applications through their Projects.
* Archiving active Applications.
* validating Project and Organization ownership.
* exposing Application status.
* publishing the current Application lifecycle events.

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
criticality
status
created_by_identity_id
created_at
updated_at
archived_at
version
```

The primary identifier uses UUIDv7.

The slug is unique within its Project. Application names are not unique.

Application settings, classification, runtime/framework metadata, repository/source metadata, ownership records, tags, and external references are separate capabilities and deferred beyond the first Application slice.

---

## MVP Lifecycle

Applications are created with the following status:

```text
active
```

The supported MVP transition is:

```text
active → archived
```

Suspension, reactivation, provisioning, restricted, deletion, and restoration states are deferred.

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
* cannot receive normal new operational activity.
* remains available for authorized historical access.
* retains related operational history.

Archival does not delete child Resources.

---

## Commands

The MVP commands are:

```text
CreateApplication
ArchiveApplication
```

`ArchiveApplication` requires:

* Tenant Context.
* valid Project ownership.
* authorization.
* optimistic concurrency.

Application rename, slug change, description or metadata update, criticality mutation, and settings update are deferred.

## Create Application

The first Application slice creates an active Application through `CreateApplication`.

It requires:

* an authenticated active Organization Membership and resolved Tenant Context;
* the `application.create` permission;
* an active Project belonging to the resolved Organization;
* `name`, `slug`, required `criticality`, and an optional `description`;
* an `Idempotency-Key` scoped by Organization, acting identity, operation, key, and normalized request hash.

Criticality values are `critical`, `high`, `medium`, and `low`; no default is applied. The server owns the Organization reference, Project ownership validation, UUIDv7 identifier, active status, creator identity, timestamps, version `1`, and `archived_at = null`. Creation does not use an expected version. The operation must atomically publish `application.created`, record the Outbox message, and record Audit action `application.create`.

---

## Queries

The MVP queries are:

```text
GetApplication
ListApplications
```

Queries must remain Organization-scoped and enforce the Application's persisted Project ownership. The MVP HTTP API always nests Application list, get, and archive operations beneath Organization and Project.

---

## Published Events

The MVP publishes:

```text
application.created
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

An Application cannot move between Projects or Organizations through normal MVP operations. Any future ownership migration must be explicitly governed.

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

## Deferred Application Settings

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

Application settings are separate from Application creation and deferred beyond the first Application slice.

---

## Authorization

Typical permissions include:

```text
application.create
application.read
application.archive
```

The MVP requires an active Organization Membership, Tenant Context, and the relevant permission. Project and Application scope extensions, high-risk authentication, and Environment permissions are owned by future access-scope or Environment capabilities.

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
```

Settings and history repository contracts are deferred. Other modules use public Application contracts rather than modifying Application persistence directly.

---

## Transactions

Application state changes should atomically commit:

* Application state.
* Audit metadata.
* Outbox Records.

Reactions in other modules occur through published events.

---

## Optimistic Concurrency

Application archival uses a Resource Version.

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

Search remains derived and rebuildable.

---

## Audit Requirements

Audit Records are required for:

* Application creation.
* archival.

Sensitive configuration must be redacted.

---

## Testing Requirements

Tests must cover:

* Application creation.
* Project and Organization ownership.
* inactive Project rejection.
* unique slug within a Project.
* identical slugs in different Projects.
* required criticality validation.
* cross-tenant access rejection.
* authorized list and historical retrieval.
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
* Application names are not unique.
* Application criticality is required at creation and uses `critical`, `high`, `medium`, or `low`.
* Applications cannot move between Projects or Organizations through normal operations.
* archived Applications retain history but reject normal writes.
* Environments are owned by the Environment module.
* operational modules reference Applications without owning them.
* Application updates, settings, and criticality mutation are deferred.
* Application archival is versioned, auditable, and event-producing.
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
