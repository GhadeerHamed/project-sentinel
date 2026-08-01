# Organization and Project Schema

## Purpose

This document defines the database schema for:

* Organizations.
* Organization settings.
* Organization lifecycle.
* Projects.
* Project ownership.
* Project membership and scope.
* Project configuration.

Organizations are the primary tenant boundary in Project Sentinel.

Projects organize related Applications, Environments, operational Resources, ownership, and configuration within an Organization.

---

## Design Principles

* Every tenant-owned Resource belongs to one Organization.
* Organization ownership is immutable.
* Projects always belong to one Organization.
* Project names and slugs are unique within an Organization.
* Organization lifecycle is explicit.
* Projects use lifecycle states instead of normal deletion.
* Configuration is versioned where historical reconstruction is required.
* Cross-Organization relationships are prohibited.
* Resource identifiers do not grant access.
* Authorization remains separate from data ownership.

---

## Schema Overview

```text
organizations
├── organization_settings
├── organization_domains
├── organization_lifecycle_history
├── organization_configuration_versions
├── projects
│   ├── project_settings
│   ├── project_owners
│   ├── project_memberships
│   ├── project_lifecycle_history
│   └── project_configuration_versions
└── organization_metadata
```

---

## Organizations

### `organizations`

Represents one Sentinel tenant.

| Column                   | Type        | Rules               |
| ------------------------ | ----------- | ------------------- |
| `id`                     | UUID        | Primary key         |
| `name`                   | VARCHAR     | Required            |
| `slug`                   | VARCHAR     | Required and unique |
| `status`                 | VARCHAR     | Required            |
| `timezone`               | VARCHAR     | Required            |
| `locale`                 | VARCHAR     | Required            |
| `data_region`            | VARCHAR     | Optional            |
| `created_by_identity_id` | UUID        | Required            |
| `created_at`             | TIMESTAMPTZ | Required            |
| `updated_at`             | TIMESTAMPTZ | Required            |
| `activated_at`           | TIMESTAMPTZ | Optional            |
| `suspended_at`           | TIMESTAMPTZ | Optional            |
| `pending_deletion_at`    | TIMESTAMPTZ | Optional            |
| `deleted_at`             | TIMESTAMPTZ | Optional            |
| `version`                | BIGINT      | Required            |

Supported status values:

* `pending`
* `active`
* `suspended`
* `pending_deletion`
* `deleted`

Constraints:

```text
UNIQUE (slug)
```

The Organization identifier must never change.

---

## Organization Lifecycle

```text
Pending

↓

Active

↓

Suspended

↓

Pending Deletion

↓

Deleted
```

Possible transitions include:

* `pending` → `active`
* `active` → `suspended`
* `suspended` → `active`
* `active` → `pending_deletion`
* `suspended` → `pending_deletion`
* `pending_deletion` → `active`
* `pending_deletion` → `deleted`

Deletion must follow retention, legal-hold, export, and audit requirements.

---

## Organization Lifecycle History

### `organization_lifecycle_history`

Stores immutable Organization state transitions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `previous_status`        | VARCHAR     | Optional    |
| `new_status`             | VARCHAR     | Required    |
| `reason`                 | TEXT        | Required    |
| `changed_by_identity_id` | UUID        | Required    |
| `changed_at`             | TIMESTAMPTZ | Required    |
| `correlation_id`         | UUID        | Optional    |

Lifecycle history must not be updated after creation.

---

## Organization Settings

### `organization_settings`

Stores the current Organization-level settings.

| Column                           | Type        | Rules       |
| -------------------------------- | ----------- | ----------- |
| `organization_id`                | UUID        | Primary key |
| `business_hours`                 | JSONB       | Optional    |
| `default_severity_model`         | VARCHAR     | Required    |
| `default_priority_model`         | VARCHAR     | Required    |
| `default_retention_policy_id`    | UUID        | Optional    |
| `default_notification_policy_id` | UUID        | Optional    |
| `default_security_policy_id`     | UUID        | Optional    |
| `settings`                       | JSONB       | Required    |
| `updated_by_identity_id`         | UUID        | Required    |
| `updated_at`                     | TIMESTAMPTZ | Required    |
| `version`                        | BIGINT      | Required    |

The `settings` field may contain capability-independent Organization preferences.

Capability-specific settings should remain owned by their capability.

---

## Organization Configuration Versions

### `organization_configuration_versions`

Preserves immutable Organization configuration versions.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `version_number`          | BIGINT      | Required    |
| `configuration`           | JSONB       | Required    |
| `status`                  | VARCHAR     | Required    |
| `change_summary`          | TEXT        | Required    |
| `created_by_identity_id`  | UUID        | Required    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `approved_by_identity_id` | UUID        | Optional    |
| `approved_at`             | TIMESTAMPTZ | Optional    |
| `activated_at`            | TIMESTAMPTZ | Optional    |
| `superseded_at`           | TIMESTAMPTZ | Optional    |

Supported status values:

* `draft`
* `validated`
* `approved`
* `active`
* `superseded`
* `rejected`
* `archived`

Constraints:

```text
UNIQUE (organization_id, version_number)
```

Only one Organization Configuration Version should normally be Active.

---

## Organization Domains

### `organization_domains`

Stores verified domains associated with an Organization.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `domain`                  | VARCHAR     | Required    |
| `status`                  | VARCHAR     | Required    |
| `verification_method`     | VARCHAR     | Optional    |
| `verification_token_hash` | VARCHAR     | Optional    |
| `verified_at`             | TIMESTAMPTZ | Optional    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `updated_at`              | TIMESTAMPTZ | Required    |

Supported status values:

* `pending`
* `verified`
* `rejected`
* `revoked`

Constraints:

```text
UNIQUE (domain)
```

A verified domain may belong to only one Organization unless a governed shared-domain model is introduced later.

---

## Organization Metadata

### `organization_metadata`

Stores non-sensitive, extensible Organization metadata.

| Column            | Type        | Rules    |
| ----------------- | ----------- | -------- |
| `organization_id` | UUID        | Required |
| `metadata_key`    | VARCHAR     | Required |
| `metadata_value`  | JSONB       | Required |
| `updated_at`      | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (organization_id, metadata_key)
```

Metadata must not be used to store:

* Credentials.
* authorization rules.
* lifecycle state.
* billing secrets.
* sensitive operational evidence.

---

## Projects

### `projects`

Represents one operational grouping within an Organization.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
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
* `suspended`
* `archived`

Supported criticality values:

* `critical`
* `high`
* `medium`
* `low`

Constraints:

```text
UNIQUE (organization_id, id)
UNIQUE (organization_id, slug)
UNIQUE (organization_id, name)
```

A Project must always belong to one Organization.

---

## Project Lifecycle

```text
Active

↓

Suspended

↓

Archived
```

A Suspended Project may return to Active.

An Archived Project should not return to Active without an explicit restoration process.

Archiving a Project must not delete its Applications, Environments, Events, Alerts, Incidents, or history.

---

## Project Lifecycle History

### `project_lifecycle_history`

Stores immutable Project state transitions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `project_id`             | UUID        | Required    |
| `previous_status`        | VARCHAR     | Optional    |
| `new_status`             | VARCHAR     | Required    |
| `reason`                 | TEXT        | Required    |
| `changed_by_identity_id` | UUID        | Required    |
| `changed_at`             | TIMESTAMPTZ | Required    |
| `correlation_id`         | UUID        | Optional    |

Tenant-aware foreign key:

```text
FOREIGN KEY (organization_id, project_id)
REFERENCES projects (organization_id, id)
```

---

## Project Settings

### `project_settings`

Stores the current Project-level settings.

| Column                       | Type        | Rules    |
| ---------------------------- | ----------- | -------- |
| `organization_id`            | UUID        | Required |
| `project_id`                 | UUID        | Required |
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
PRIMARY KEY (organization_id, project_id)
```

Project settings must not weaken mandatory Organization or platform restrictions.

---

## Project Configuration Versions

### `project_configuration_versions`

Preserves immutable Project configuration versions.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `project_id`              | UUID        | Required    |
| `version_number`          | BIGINT      | Required    |
| `configuration`           | JSONB       | Required    |
| `status`                  | VARCHAR     | Required    |
| `change_summary`          | TEXT        | Required    |
| `created_by_identity_id`  | UUID        | Required    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `approved_by_identity_id` | UUID        | Optional    |
| `approved_at`             | TIMESTAMPTZ | Optional    |
| `activated_at`            | TIMESTAMPTZ | Optional    |
| `superseded_at`           | TIMESTAMPTZ | Optional    |

Constraints:

```text
UNIQUE (organization_id, project_id, version_number)
```

---

## Project Owners

### `project_owners`

Assigns accountable ownership to a Project.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `project_id`              | UUID        | Required    |
| `owner_type`              | VARCHAR     | Required    |
| `owner_id`                | UUID        | Required    |
| `ownership_role`          | VARCHAR     | Required    |
| `assigned_by_identity_id` | UUID        | Required    |
| `assigned_at`             | TIMESTAMPTZ | Required    |
| `removed_at`              | TIMESTAMPTZ | Optional    |

Supported `owner_type` values:

* `membership`
* `team`

Supported ownership roles may include:

* `primary`
* `technical`
* `operational`
* `security`

Constraints:

```text
UNIQUE (
    organization_id,
    project_id,
    owner_type,
    owner_id,
    ownership_role
)
```

Every active Project should have at least one accountable owner.

---

## Project Memberships

### `project_memberships`

Limits or extends a Membership’s access within one Project.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `project_id`              | UUID        | Required    |
| `membership_id`           | UUID        | Required    |
| `access_level`            | VARCHAR     | Required    |
| `assigned_by_identity_id` | UUID        | Required    |
| `assigned_at`             | TIMESTAMPTZ | Required    |
| `expires_at`              | TIMESTAMPTZ | Optional    |
| `revoked_at`              | TIMESTAMPTZ | Optional    |

Possible access levels:

* `viewer`
* `operator`
* `manager`
* `administrator`

Constraints:

```text
UNIQUE (organization_id, project_id, membership_id)
```

Project Membership is an optional scoping mechanism.

It does not replace Role and permission evaluation.

---

## Project Teams

### `project_teams`

Associates an Organization Team with a Project.

| Column              | Type        | Rules    |
| ------------------- | ----------- | -------- |
| `organization_id`   | UUID        | Required |
| `project_id`        | UUID        | Required |
| `team_id`           | UUID        | Required |
| `relationship_type` | VARCHAR     | Required |
| `assigned_at`       | TIMESTAMPTZ | Required |
| `removed_at`        | TIMESTAMPTZ | Optional |

Possible relationship types:

* `owner`
* `operator`
* `responder`
* `viewer`
* `security`

Primary key:

```text
PRIMARY KEY (
    organization_id,
    project_id,
    team_id,
    relationship_type
)
```

The Team and Project must belong to the same Organization.

---

## Project Tags

### `project_tags`

Stores searchable Project classifications.

| Column            | Type        | Rules    |
| ----------------- | ----------- | -------- |
| `organization_id` | UUID        | Required |
| `project_id`      | UUID        | Required |
| `tag`             | VARCHAR     | Required |
| `created_at`      | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (organization_id, project_id, tag)
```

Tags are descriptive metadata.

They must not be used as authorization controls.

---

## Project External References

### `project_external_references`

Maps a Project to an external system.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `project_id`             | UUID        | Required    |
| `provider`               | VARCHAR     | Required    |
| `external_type`          | VARCHAR     | Required    |
| `external_id`            | VARCHAR     | Required    |
| `plugin_installation_id` | UUID        | Optional    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `archived_at`            | TIMESTAMPTZ | Optional    |

Constraints:

```text
UNIQUE (
    organization_id,
    provider,
    external_type,
    external_id
)
```

External references must remain scoped to the Organization and provider.

---

## Tenant-Aware Relationships

Important relationships must preserve matching Organization ownership.

Examples:

```text
organization_memberships.organization_id
    → organizations.id

projects.organization_id
    → organizations.id

project_settings (organization_id, project_id)
    → projects (organization_id, id)

project_owners (organization_id, project_id)
    → projects (organization_id, id)

project_memberships (organization_id, project_id)
    → projects (organization_id, id)
```

The schema must prevent a Project from referencing:

* A Team from another Organization.
* a Membership from another Organization.
* an external Plugin Installation from another Organization.
* an Application or Environment from another Organization.

---

## Configuration Precedence

Project configuration follows this precedence:

```text
Platform Safety Requirements

↓

Organization Policies

↓

Organization Configuration

↓

Project Configuration

↓

Application and Environment Configuration
```

Project configuration may narrow Organization behavior.

It must not weaken mandatory Organization or platform restrictions.

---

## Optimistic Concurrency

Mutable Organization and Project records should use the `version` column.

Example update:

```sql
UPDATE projects
SET
    name = :name,
    description = :description,
    version = version + 1,
    updated_at = :updated_at
WHERE organization_id = :organization_id
  AND id = :project_id
  AND version = :expected_version;
```

If no row is updated, the operation must return a concurrency conflict.

---

## Organization Suspension

Suspending an Organization may restrict:

* User access.
* API access.
* new Event ingestion.
* Agent Executions.
* Plugin Executions.
* Workflow starts.
* administrative changes.

Suspension behavior is defined through Administration and Policy.

The Organization record remains the owner of all existing data.

---

## Project Suspension

Suspending a Project may restrict:

* New Applications.
* configuration changes.
* selected API operations.
* Agent or Plugin operations.
* new Workflow starts.

Existing operational evidence and authorized Incident response should remain accessible according to Policy.

---

## Organization Deletion

Organization deletion must not use an uncontrolled database cascade.

Deletion processing must consider:

* Legal Holds.
* retention requirements.
* audit preservation.
* active Incidents.
* object storage.
* Search Projections.
* exports.
* credentials.
* Plugin and Agent state.
* backups.

The `deleted` status represents completed governed deletion, not a simple soft-delete operation.

---

## Project Archival

Archiving a Project should:

* Prevent normal creation of new child Resources.
* remove the Project from default active views.
* preserve existing Applications and operational records.
* preserve ownership and history.
* maintain Search availability for authorized historical use.
* preserve audit records.

Project archival must not physically delete operational history.

---

## Required Indexes

Recommended indexes include:

```text
organizations (status)
organizations (slug)

organization_domains (organization_id, status)
organization_lifecycle_history (organization_id, changed_at)

projects (organization_id, status)
projects (organization_id, criticality)
projects (organization_id, created_at)

project_owners (organization_id, project_id, removed_at)
project_owners (organization_id, owner_type, owner_id)

project_memberships (organization_id, membership_id, revoked_at)
project_memberships (organization_id, project_id, revoked_at)

project_teams (organization_id, team_id, removed_at)
project_lifecycle_history (organization_id, project_id, changed_at)

project_external_references (
    organization_id,
    provider,
    external_id
)
```

Indexes should reflect actual access patterns and authorization queries.

---

## Retention

Organization and Project records should normally be retained for the lifetime of the tenant and required historical period.

Records requiring historical preservation include:

* Organization lifecycle history.
* Project lifecycle history.
* configuration versions.
* ownership history.
* Project Membership history.
* external-reference history.

Expired invitation or temporary setup data may follow shorter retention periods.

---

## Audit Requirements

Auditable operations include:

* Organization creation.
* Organization activation.
* Organization suspension.
* Organization deletion request.
* Project creation.
* Project suspension.
* Project archival.
* ownership changes.
* Project Membership changes.
* configuration activation.
* domain verification.
* external-reference changes.

Audit Records are stored in the Audit schema.

Domain history remains in the Organization and Project schema.

---

## Business Rules

* Every Project belongs to exactly one Organization.
* Organization ownership must not change.
* Project ownership must not change between Organizations.
* Organization slugs are globally unique.
* Project names and slugs are unique within an Organization.
* Child Resources must belong to the same Organization as their Project.
* Every active Project should have an accountable owner.
* Project access records must reference Memberships or Teams from the same Organization.
* Project Membership does not replace permission evaluation.
* Tags do not grant access.
* Organization and Project lifecycle changes are historically recorded.
* Active Configuration Versions are immutable.
* Project configuration must not weaken Organization restrictions.
* Organization deletion must follow governed retention processing.
* Project archival must preserve operational history.
* Resource identifiers do not grant access.
* Missing or conflicting Organization ownership results in rejection.

---

## Success Criteria

The Organization and Project schema is successful when:

* Organizations provide a clear tenant boundary.
* every Project has explicit Organization ownership.
* Project naming remains tenant-scoped.
* ownership and operational responsibility remain clear.
* Organization and Project lifecycle can be reconstructed.
* configuration changes remain versioned.
* Project access can be scoped without weakening authorization.
* cross-Organization relationships are prevented.
* archival preserves operational history.
* governed Organization deletion is possible.
* future dedicated-tenant storage remains possible.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/04-application-environment-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/15-retention-archival.md`
* `docs/functional/01-organization-management.md`
* `docs/functional/02-project-management.md`
* `docs/functional/14-administration.md`
* `docs/architecture/02-domain-architecture.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-005-domain-driven-design.md`
