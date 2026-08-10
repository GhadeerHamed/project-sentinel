# Application and Environment Schema

## Purpose

This document defines the database schema for:

* Applications.
* Application ownership.
* Application lifecycle.
* Environments.
* Environment ownership.
* Environment lifecycle.

Applications belong to Projects.

Environments belong to Applications.

All records remain within one Organization.

Application and Environment configuration, ownership records, external references, and lifecycle history are deferred beyond the initial Registry slices.

---

## Design Principles

* Every Application belongs to one Project and one Organization.
* Every Environment belongs to one Application and the same Organization.
* Organization ownership never changes.
* Cross-Organization relationships are prohibited.
* Applications and Environments use their canonical MVP lifecycle states.
* Archival does not delete operational history.

---

## Schema Overview

```text
organizations
└── projects
    └── applications
        └── environments
```

---

The `applications` and `environments` tables are the initial Registry persistence. Settings, owners, tags, external references, and lifecycle-history tables are separate capabilities and are deferred beyond their respective Registry slices.

---

## Applications

### `applications`

Represents one operational Application monitored by Sentinel.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `project_id`             | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `slug`                   | VARCHAR     | Required    |
| `description`            | TEXT        | Optional    |
| `status`                 | VARCHAR     | Required    |
| `criticality`            | VARCHAR     | Required    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `archived_at`            | TIMESTAMPTZ | Optional    |
| `version`                | BIGINT      | Required    |

Supported status values:

* `active`
* `archived`

Supported criticality values:

* `critical`
* `high`
* `medium`
* `low`

Constraints:

```text
UNIQUE (organization_id, id)
UNIQUE (organization_id, project_id, slug)
```

Tenant-aware parent relationship:

```text
FOREIGN KEY (organization_id, project_id)
REFERENCES projects (organization_id, id)
```

`criticality` is required at creation and has no default. Application settings, classification, runtime/framework metadata, repository/source metadata, ownership records, tags, and external references are deferred beyond the first Application slice.

---

## Application Lifecycle

```text
Active

↓

Archived
```

Applications are created `active`. The only supported MVP transition is `active` → `archived`.

Suspension, reactivation, provisioning, restricted, deletion, and restoration are deferred.

Archiving must not delete:

* Environments.
* Events.
* Alerts.
* Incidents.
* Knowledge.
* execution history.
* audit history.

---

## Application Lifecycle History

### `application_lifecycle_history`

Stores immutable Application state transitions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `application_id`         | UUID        | Required    |
| `previous_status`        | VARCHAR     | Optional    |
| `new_status`             | VARCHAR     | Required    |
| `reason`                 | TEXT        | Required    |
| `changed_by_identity_id` | UUID        | Required    |
| `changed_at`             | TIMESTAMPTZ | Required    |
| `correlation_id`         | UUID        | Optional    |

Constraint:

```text
FOREIGN KEY (organization_id, application_id)
REFERENCES applications (organization_id, id)
```

Lifecycle history is append-only.

---

## Application Settings

### `application_settings`

Stores current Application-level settings.

| Column                       | Type        | Rules    |
| ---------------------------- | ----------- | -------- |
| `organization_id`            | UUID        | Required |
| `application_id`             | UUID        | Required |
| `default_environment_id`     | UUID        | Optional |
| `default_owner_team_id`      | UUID        | Optional |
| `default_alert_policy_id`    | UUID        | Optional |
| `default_incident_policy_id` | UUID        | Optional |
| `settings`                   | JSONB       | Required |
| `updated_by_identity_id`     | UUID        | Required |
| `updated_at`                 | TIMESTAMPTZ | Required |
| `version`                    | BIGINT      | Required |

Primary key:

```text
PRIMARY KEY (organization_id, application_id)
```

Application settings may narrow Project behavior.

They must not weaken mandatory Organization or platform restrictions.

---

## Application Owners

### `application_owners`

Assigns operational responsibility for an Application.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `application_id`          | UUID        | Required    |
| `owner_type`              | VARCHAR     | Required    |
| `owner_id`                | UUID        | Required    |
| `ownership_role`          | VARCHAR     | Required    |
| `assigned_by_identity_id` | UUID        | Required    |
| `assigned_at`             | TIMESTAMPTZ | Required    |
| `removed_at`              | TIMESTAMPTZ | Optional    |

Supported `owner_type` values:

* `membership`
* `team`

Supported ownership roles:

* `primary`
* `technical`
* `operational`
* `security`

Constraint:

```text
UNIQUE (
    organization_id,
    application_id,
    owner_type,
    owner_id,
    ownership_role
)
```

Every Active Application should have at least one accountable owner.

---

## Application Tags

### `application_tags`

Stores searchable Application classifications.

| Column            | Type        | Rules    |
| ----------------- | ----------- | -------- |
| `organization_id` | UUID        | Required |
| `application_id`  | UUID        | Required |
| `tag`             | VARCHAR     | Required |
| `created_at`      | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (organization_id, application_id, tag)
```

Tags are descriptive only.

They must not grant access or change Policy behavior.

---

## Application External References

### `application_external_references`

Maps an Application to an external provider Resource.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `application_id`         | UUID        | Required    |
| `provider`               | VARCHAR     | Required    |
| `external_type`          | VARCHAR     | Required    |
| `external_id`            | VARCHAR     | Required    |
| `plugin_installation_id` | UUID        | Optional    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `archived_at`            | TIMESTAMPTZ | Optional    |

Constraint:

```text
UNIQUE (
    organization_id,
    provider,
    external_type,
    external_id
)
```

An external reference must not connect Resources from different Organizations.

---

## Environments

### `environments`

Represents one operational Environment of an Application.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `application_id`         | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `slug`                   | VARCHAR     | Required    |
| `environment_type`       | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `archived_at`            | TIMESTAMPTZ | Optional    |
| `version`                | BIGINT      | Required    |

Supported Environment Types:

* `production`
* `staging`
* `testing`
* `development`
* `sandbox`
* `other`

Supported status values:

* `active`
* `archived`

Constraints:

```text
UNIQUE (organization_id, id)
UNIQUE (organization_id, application_id, slug)
```

Tenant-aware parent relationship:

```text
FOREIGN KEY (organization_id, application_id)
REFERENCES applications (organization_id, id)
```

`project_id` is not persisted on Environments. Route-level Project ownership is validated through the Application relationship. Environment criticality, region, settings, ownership records, external references, and other operational metadata are deferred beyond the initial Registry slice.

Environment creation uses the shared idempotency scope of Organization, acting identity, operation, key, and normalized request hash. Its transaction persists the Environment, an `environment.created` Outbox record, and Audit action `environment.create` atomically.

---

## Environment Lifecycle

Environments are created `active`. The only supported MVP transition is `active` → `archived`.

Archived Environments reject normal new activity, retain operational history, and remain available for authorized historical reads. Maintenance, suspension, reactivation, provisioning, restoration, and deletion are deferred.

---

## Deferred Environment Capabilities

Environment lifecycle history, settings, operational ownership, external references, and production-control configuration are deferred. Future tables for those capabilities must remain Organization-scoped and reference Environments through tenant-aware foreign keys.

---

## Deferred Configuration and Production Controls

Environment configuration and production-specific controls are deferred. When introduced, they may narrow higher-level behavior but must not weaken mandatory restrictions or treat `environment_type` as authorization.

---

## Parent Lifecycle Effects

### Application Archival

Archiving an Application should prevent normal creation of new Environments and operational activity.

Existing records remain historically accessible.

### Project Archival

Archiving a Project likewise prevents normal creation of new Environments beneath its Applications while preserving historical access.

---

## Optimistic Concurrency

Archive operations use `version`.

Example:

```sql
UPDATE environments
SET
    status = :new_status,
    version = version + 1,
    updated_at = :updated_at
WHERE organization_id = :organization_id
  AND id = :environment_id
  AND version = :expected_version;
```

If no row is updated, Sentinel must return a concurrency conflict.

---

## Tenant Isolation

Every Application and Environment operation must include `organization_id`.

The schema must prevent:

* An Application referencing another Organization’s Project.
* an Environment referencing another Organization’s Application.

Organization identifiers must come from trusted Tenant Context.

---

## Required Indexes

Recommended indexes include:

```text
applications (organization_id, project_id, status)
applications (organization_id, criticality)
applications (organization_id, created_at)

application_owners (
    organization_id,
    application_id,
    removed_at
)

application_owners (
    organization_id,
    owner_type,
    owner_id
)

application_lifecycle_history (
    organization_id,
    application_id,
    changed_at
)

environments (
    organization_id,
    application_id,
    status,
    created_at,
    id
)
```

Indexes should support actual query and authorization patterns.

---

## Retention

Application and Environment records should normally remain for the required Organization retention period.

Environment records, related operational references, and Audit records remain historically available.

Archival removes records from normal active views but does not delete operational history.

---

## Audit Requirements

Auditable operations include:

* Application creation.
* Application archival.
* future Application lifecycle transitions when introduced.
* Environment creation.
* Environment archival.

---

## Business Rules

* Every Application belongs to exactly one Project and Organization.
* Every Environment belongs to exactly one Application and Organization.
* Application and Environment ownership cannot move between Organizations.
* Parent and child Resources must share the same Organization.
* Application slugs are unique within their Project; names are not unique.
* Environment slugs are unique within their Application; names are not unique.
* Application ownership is a deferred separate capability and is not required by Create Application.
* Archiving does not delete operational history.
* Resource identifiers do not grant access.
* Missing or conflicting Organization ownership results in rejection.

---

## Success Criteria

The Application and Environment schema is successful when:

* Applications are organized clearly within Projects.
* Environments are organized clearly within Applications.
* tenant ownership remains explicit.
* cross-Organization relationships are prevented.
* the active-to-archived lifecycle remains enforceable.
* Application and Environment ownership remains clear.
* archival preserves operational history.
* future operational capabilities can reference Applications and Environments safely.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/03-organization-project-schema.md`
* `docs/database/05-event-signal-schema.md`
* `docs/database/06-alert-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/functional/02-project-management.md`
* `docs/functional/03-application-management.md`
* `docs/functional/04-environment-management.md`
* `docs/functional/14-administration.md`
* `docs/architecture/02-domain-architecture.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-005-domain-driven-design.md`
