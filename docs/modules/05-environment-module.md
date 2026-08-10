# Environment Module

## Purpose

The Environment module owns deployment Environments within Sentinel Applications. An Environment represents one operational runtime, such as production, staging, testing, development, or sandbox.

The initial Milestone 4 slice establishes the Environment Registry. Runtime telemetry remains owned by the Event module.

---

## Ownership

The canonical hierarchy is:

```text
Organization → Project → Application → Environment
```

Every Environment belongs to exactly one Application and Organization. It persists:

```text
id
organization_id
application_id
name
slug
environment_type
status
created_by_identity_id
created_at
updated_at
archived_at
version
```

`id` uses UUIDv7. `project_id` is not persisted on Environment; Project ownership is validated through the Application relationship. An Environment cannot move between Applications or Organizations.

The canonical uniqueness constraint is:

```text
UNIQUE (organization_id, application_id, slug)
```

Environment names are not unique, and there is no separate Environment key.

---

## Environment Type

`environment_type` is required and must be one of:

```text
production
staging
testing
development
sandbox
other
```

It is descriptive classification. It must not grant authorization or bypass Policy enforcement; production-specific controls require an explicit Policy.

---

## Lifecycle

Environments are created `active`. The only supported MVP transition is:

```text
active → archived
```

Archived Environments are read-only, retain operational history, reject normal new activity, and remain available for authorized historical reads.

Disabled, maintenance, suspension, reactivation, provisioning, restoration, deletion, settings, and Environment criticality are deferred beyond the initial Registry slice.

---

## Commands and Queries

The MVP exposes:

```text
CreateEnvironment
ArchiveEnvironment
ListEnvironments
GetEnvironment
```

Commands require Tenant Context, authorization, valid parent ownership, and the applicable lifecycle rules. List and get operations are Application-scoped and retain authorized historical access to archived Environments.

---

## Parent Validation and Tenant Isolation

Environment creation requires:

* an active Organization resolved through Tenant Context;
* an active Project belonging to that Organization;
* an active Application belonging to that Organization and Project;
* an authorized acting identity.

Project-nested HTTP operations must validate the Organization, Project, Application, and Environment relationship. Environment repository lookups are scoped by `organization_id`, `application_id`, and `environment_id`; cross-Organization and cross-Project references must be tenant-safe not-found results.

Archived Projects and Applications cannot receive new Environments. Environment identifiers alone never establish access.

---

## Create Environment

Creation requires `environment.create` and an `Idempotency-Key`. The request accepts only `name`, `slug`, and `environment_type`.

The server owns Organization and Application ownership, the UUIDv7 identifier, active status, creator identity, timestamps, `archived_at = null`, and version `1`. Creation does not use `If-Match`.

Idempotency is scoped by Organization, acting identity, operation, key, and normalized request hash. A matching retry returns the original result; reusing a key with a different request is a conflict.

Successful creation atomically persists the Environment, publishes `environment.created` through the Outbox, and records Audit action `environment.create`.

---

## Events and Transactions

The MVP publishes:

```text
environment.created
environment.archived
```

Events include `organization_id`, `application_id`, `environment_id`, `event_id`, `occurred_at`, `correlation_id`, `causation_id`, and `event_version`.

State changes atomically commit Environment state, required Audit metadata, and Outbox records. Archive uses Resource Version optimistic concurrency; conflicts must not overwrite newer state.

---

## Integration Boundaries

The Environment module does not own Applications, deployment infrastructure, Events, Alerts, Incidents, Plugin credentials, Agent executions, or runtime configuration secrets. Other modules reference Environments through public contracts and must not write Environment state directly.

Event ingestion must verify that an Environment belongs to the expected Application and Organization and is active before accepting environment-specific telemetry.

---

## Testing Requirements

Tests must cover creation, parent ownership validation, archived-parent rejection, slug uniqueness, tenant isolation, idempotency, `active → archived`, optimistic concurrency, Outbox, Audit, and authorized historical reads.

---

## Related Documents

* `docs/modules/04-application-module.md`
* `docs/database/04-application-environment-schema.md`
* `docs/api/09-environments-api.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/04-authorization-enforcement.md`
