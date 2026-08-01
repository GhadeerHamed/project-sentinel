# Plugin Schema

## Purpose

This document defines the database schema for the Plugin Platform.

It covers:

* Plugin Definitions.
* immutable Plugin Versions.
* Organization installations.
* configuration.
* credentials.
* capability grants.
* webhooks.
* synchronization.
* executions.
* health state.
* lifecycle history.

The Plugin Platform owns Plugin installation and execution records.

Connected domain capabilities remain authoritative for imported or affected Resources.

---

## Design Principles

* Plugin Definitions may be platform-owned.
* Every Plugin Installation belongs to one Organization.
* Every Installation uses a distinct Plugin Identity.
* Plugin Versions are immutable.
* Credentials are stored as secret references.
* Configuration is separated from secret material.
* Plugin capabilities must be explicitly granted.
* Plugin execution remains tenant-scoped.
* Imported data must preserve source attribution.
* Provider callbacks must be authenticated and idempotent.
* Protected Actions require normal Policy evaluation.
* Cross-Organization relationships are prohibited.

---

## Schema Overview

```text
plugin_definitions
└── plugin_versions
    └── plugin_version_capabilities

plugin_installations
├── plugin_installation_versions
├── plugin_credentials
├── plugin_capability_grants
├── plugin_webhook_endpoints
├── plugin_sync_states
├── plugin_executions
│   └── plugin_execution_attempts
├── plugin_health_checks
└── plugin_lifecycle_history
```

Plugin Identity records are defined in:

```text
docs/database/02-identity-access-schema.md
```

---

# Plugin Definitions

## `plugin_definitions`

Represents one supported Plugin.

| Column              | Type        | Rules               |
| ------------------- | ----------- | ------------------- |
| `id`                | UUID        | Primary key         |
| `key`               | VARCHAR     | Required and unique |
| `name`              | VARCHAR     | Required            |
| `description`       | TEXT        | Required            |
| `provider`          | VARCHAR     | Required            |
| `plugin_type`       | VARCHAR     | Required            |
| `status`            | VARCHAR     | Required            |
| `active_version_id` | UUID        | Optional            |
| `created_at`        | TIMESTAMPTZ | Required            |
| `updated_at`        | TIMESTAMPTZ | Required            |
| `version`           | BIGINT      | Required            |

Supported Plugin Types may include:

* `source`
* `destination`
* `bidirectional`
* `notification`
* `observability`
* `deployment`
* `ticketing`
* `collaboration`
* `custom`

Supported statuses:

* `draft`
* `active`
* `deprecated`
* `suspended`
* `archived`

Constraints:

```text
UNIQUE (key)
```

Plugin Definitions are platform-owned.

---

# Plugin Versions

## `plugin_versions`

Stores immutable Plugin implementation contracts.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `plugin_definition_id`     | UUID        | Required    |
| `version_number`           | VARCHAR     | Required    |
| `manifest`                 | JSONB       | Required    |
| `configuration_schema`     | JSONB       | Required    |
| `credential_schema`        | JSONB       | Required    |
| `event_schema`             | JSONB       | Required    |
| `minimum_platform_version` | VARCHAR     | Optional    |
| `status`                   | VARCHAR     | Required    |
| `created_at`               | TIMESTAMPTZ | Required    |
| `activated_at`             | TIMESTAMPTZ | Optional    |
| `deprecated_at`            | TIMESTAMPTZ | Optional    |

Supported statuses:

* `draft`
* `validated`
* `active`
* `deprecated`
* `withdrawn`

Constraints:

```text
UNIQUE (
    plugin_definition_id,
    version_number
)
```

Plugin Versions must not be modified after activation.

---

# Plugin Version Capabilities

## `plugin_version_capabilities`

Defines capabilities requested by a Plugin Version.

| Column              | Type    | Rules       |
| ------------------- | ------- | ----------- |
| `id`                | UUID    | Primary key |
| `plugin_version_id` | UUID    | Required    |
| `capability_code`   | VARCHAR | Required    |
| `operation_type`    | VARCHAR | Required    |
| `risk_level`        | VARCHAR | Required    |
| `requires_approval` | BOOLEAN | Required    |
| `description`       | TEXT    | Required    |

Possible operation types:

* `read`
* `write`
* `execute`
* `subscribe`
* `publish`

Examples:

```text
event.ingest
incident.read
incident.create_comment
notification.send
deployment.read
workflow.trigger
```

Constraints:

```text
UNIQUE (
    plugin_version_id,
    capability_code
)
```

A Plugin may request capabilities, but installation Policies decide what is granted.

---

# Plugin Installations

## `plugin_installations`

Represents one Organization installation.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `plugin_definition_id`     | UUID        | Required    |
| `plugin_version_id`        | UUID        | Required    |
| `plugin_identity_id`       | UUID        | Required    |
| `name`                     | VARCHAR     | Required    |
| `status`                   | VARCHAR     | Required    |
| `environment_scope`        | JSONB       | Required    |
| `installed_by_identity_id` | UUID        | Required    |
| `installed_at`             | TIMESTAMPTZ | Required    |
| `activated_at`             | TIMESTAMPTZ | Optional    |
| `suspended_at`             | TIMESTAMPTZ | Optional    |
| `archived_at`              | TIMESTAMPTZ | Optional    |
| `updated_at`               | TIMESTAMPTZ | Required    |
| `version`                  | BIGINT      | Required    |

Supported statuses:

* `pending_configuration`
* `validating`
* `active`
* `degraded`
* `suspended`
* `failed`
* `archived`

Constraints:

```text
UNIQUE (organization_id, id)
UNIQUE (organization_id, name)
UNIQUE (organization_id, plugin_identity_id)
```

One Organization may install the same Plugin more than once for different accounts or scopes.

---

# Installation Configuration

## `plugin_installation_versions`

Stores immutable installation configuration Versions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `version_number`         | BIGINT      | Required    |
| `configuration`          | JSONB       | Required    |
| `scope_definition`       | JSONB       | Required    |
| `change_summary`         | TEXT        | Required    |
| `status`                 | VARCHAR     | Required    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `activated_at`           | TIMESTAMPTZ | Optional    |
| `superseded_at`          | TIMESTAMPTZ | Optional    |

Supported statuses:

* `draft`
* `validated`
* `active`
* `superseded`
* `rejected`

Constraints:

```text
UNIQUE (
    organization_id,
    plugin_installation_id,
    version_number
)
```

Configuration must not contain secret values.

---

# Plugin Credentials

## `plugin_credentials`

Stores credential metadata and secret references.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `credential_type`        | VARCHAR     | Required    |
| `secret_reference`       | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `expires_at`             | TIMESTAMPTZ | Optional    |
| `rotated_at`             | TIMESTAMPTZ | Optional    |
| `last_used_at`           | TIMESTAMPTZ | Optional    |
| `revoked_at`             | TIMESTAMPTZ | Optional    |

Supported statuses:

* `active`
* `expiring`
* `expired`
* `revoked`
* `invalid`

Actual credentials must be stored using an approved secret-management mechanism.

---

# Capability Grants

## `plugin_capability_grants`

Stores capabilities granted to an Installation.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `capability_code`        | VARCHAR     | Required    |
| `scope_type`             | VARCHAR     | Required    |
| `scope_id`               | UUID        | Optional    |
| `status`                 | VARCHAR     | Required    |
| `granted_by_identity_id` | UUID        | Required    |
| `granted_at`             | TIMESTAMPTZ | Required    |
| `expires_at`             | TIMESTAMPTZ | Optional    |
| `revoked_at`             | TIMESTAMPTZ | Optional    |

Supported statuses:

* `active`
* `suspended`
* `expired`
* `revoked`

Constraints:

```text
UNIQUE (
    organization_id,
    plugin_installation_id,
    capability_code,
    scope_type,
    scope_id
)
```

A grant cannot exceed the capabilities declared by the installed Plugin Version.

---

# Webhook Endpoints

## `plugin_webhook_endpoints`

Stores inbound callback configuration.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `endpoint_key`           | VARCHAR     | Required    |
| `secret_reference`       | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `rotated_at`             | TIMESTAMPTZ | Optional    |
| `disabled_at`            | TIMESTAMPTZ | Optional    |

Supported statuses:

* `active`
* `rotating`
* `disabled`
* `revoked`

Constraints:

```text
UNIQUE (endpoint_key)
```

Webhook authentication secrets must not be stored directly.

---

## `plugin_webhook_deliveries`

Stores received provider callback metadata.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `external_delivery_id`   | VARCHAR     | Optional    |
| `payload_hash`           | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `received_at`            | TIMESTAMPTZ | Required    |
| `processed_at`           | TIMESTAMPTZ | Optional    |
| `failure_code`           | VARCHAR     | Optional    |
| `event_id`               | UUID        | Optional    |

Supported statuses:

* `received`
* `authenticated`
* `processed`
* `duplicate`
* `rejected`
* `failed`

Possible idempotency constraint:

```text
UNIQUE (
    organization_id,
    plugin_installation_id,
    external_delivery_id
)
```

---

# Synchronization State

## `plugin_sync_states`

Stores synchronization progress for one Installation scope.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `plugin_installation_id`  | UUID        | Required    |
| `sync_type`               | VARCHAR     | Required    |
| `scope_key`               | VARCHAR     | Required    |
| `cursor`                  | TEXT        | Optional    |
| `last_successful_sync_at` | TIMESTAMPTZ | Optional    |
| `next_sync_at`            | TIMESTAMPTZ | Optional    |
| `status`                  | VARCHAR     | Required    |
| `failure_count`           | INTEGER     | Required    |
| `last_failure_code`       | VARCHAR     | Optional    |
| `updated_at`              | TIMESTAMPTZ | Required    |
| `version`                 | BIGINT      | Required    |

Supported statuses:

* `idle`
* `scheduled`
* `running`
* `degraded`
* `failed`
* `suspended`

Constraints:

```text
UNIQUE (
    organization_id,
    plugin_installation_id,
    sync_type,
    scope_key
)
```

Synchronization cursors must be treated as opaque provider values.

---

# Plugin Executions

## `plugin_executions`

Stores one Plugin operation.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `plugin_version_id`      | UUID        | Required    |
| `plugin_identity_id`     | UUID        | Required    |
| `execution_type`         | VARCHAR     | Required    |
| `operation`              | VARCHAR     | Required    |
| `target_resource_type`   | VARCHAR     | Optional    |
| `target_resource_id`     | UUID        | Optional    |
| `status`                 | VARCHAR     | Required    |
| `initiated_by_type`      | VARCHAR     | Required    |
| `initiated_by_id`        | UUID        | Optional    |
| `idempotency_key`        | VARCHAR     | Optional    |
| `requested_at`           | TIMESTAMPTZ | Required    |
| `started_at`             | TIMESTAMPTZ | Optional    |
| `completed_at`           | TIMESTAMPTZ | Optional    |
| `failure_code`           | VARCHAR     | Optional    |
| `failure_message`        | TEXT        | Optional    |
| `correlation_id`         | UUID        | Optional    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Possible Execution Types:

* `ingestion`
* `synchronization`
* `query`
* `action`
* `notification`
* `validation`
* `health_check`

Supported statuses:

* `requested`
* `authorized`
* `queued`
* `running`
* `completed`
* `failed`
* `rejected`
* `cancelled`
* `timed_out`

---

## Plugin Execution Attempts

### `plugin_execution_attempts`

Stores individual provider-call attempts.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `plugin_execution_id`  | UUID        | Required    |
| `attempt_number`       | INTEGER     | Required    |
| `status`               | VARCHAR     | Required    |
| `request_reference`    | JSONB       | Required    |
| `result_reference`     | JSONB       | Optional    |
| `started_at`           | TIMESTAMPTZ | Required    |
| `completed_at`         | TIMESTAMPTZ | Optional    |
| `provider_status_code` | INTEGER     | Optional    |
| `failure_code`         | VARCHAR     | Optional    |
| `retryable`            | BOOLEAN     | Optional    |

Constraints:

```text
UNIQUE (
    organization_id,
    plugin_execution_id,
    attempt_number
)
```

Credentials and unrestricted provider payloads must not be stored.

---

# Imported Resource References

## `plugin_external_resources`

Maps provider Resources to Sentinel Resources.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `external_resource_type` | VARCHAR     | Required    |
| `external_resource_id`   | VARCHAR     | Required    |
| `sentinel_resource_type` | VARCHAR     | Optional    |
| `sentinel_resource_id`   | UUID        | Optional    |
| `external_version`       | VARCHAR     | Optional    |
| `last_seen_at`           | TIMESTAMPTZ | Required    |
| `archived_at`            | TIMESTAMPTZ | Optional    |

Constraints:

```text
UNIQUE (
    organization_id,
    plugin_installation_id,
    external_resource_type,
    external_resource_id
)
```

This mapping does not transfer ownership of the Sentinel Resource to the Plugin Platform.

---

# Plugin Health

## `plugin_health_checks`

Stores Installation health results.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `status`                 | VARCHAR     | Required    |
| `check_type`             | VARCHAR     | Required    |
| `summary`                | TEXT        | Optional    |
| `checked_at`             | TIMESTAMPTZ | Required    |
| `response_time_ms`       | INTEGER     | Optional    |
| `failure_code`           | VARCHAR     | Optional    |

Supported statuses:

* `healthy`
* `degraded`
* `unavailable`
* `unauthorized`
* `misconfigured`
* `unknown`

Health results are operational observations.

They do not replace Installation lifecycle state.

---

# Lifecycle History

## `plugin_lifecycle_history`

Stores immutable Installation lifecycle changes.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `previous_status`        | VARCHAR     | Optional    |
| `new_status`             | VARCHAR     | Required    |
| `reason`                 | TEXT        | Required    |
| `changed_by_type`        | VARCHAR     | Required    |
| `changed_by_id`          | UUID        | Optional    |
| `changed_at`             | TIMESTAMPTZ | Required    |
| `correlation_id`         | UUID        | Optional    |

History records are append-only.

---

# Installation Transaction

Installing a Plugin should normally create:

1. Plugin Installation.
2. Plugin Identity.
3. initial configuration Version.
4. requested capability records.
5. lifecycle history.
6. Outbox message.

Credentials should be created through the secret-management boundary.

---

# Execution Transaction

Starting a Plugin Execution should normally create:

1. Plugin Execution.
2. idempotency record when required.
3. execution history or Outbox message.
4. commit.

External provider calls occur only after commit.

Completion should persist:

1. final Execution status.
2. attempt result.
3. imported Resource mappings where applicable.
4. new Events or commands through owning capabilities.
5. Outbox message.

---

# Optimistic Concurrency

Mutable Installation, synchronization, and Execution records use `version`.

Example:

```sql
UPDATE plugin_installations
SET
    status = :new_status,
    version = version + 1,
    updated_at = :updated_at
WHERE organization_id = :organization_id
  AND id = :plugin_installation_id
  AND version = :expected_version;
```

A failed Version match produces a concurrency conflict.

Plugin Versions and lifecycle history remain immutable.

---

# Tenant Isolation

Every Installation-related table includes `organization_id`.

The schema must prevent:

* Cross-Organization configuration.
* use of another Organization’s Plugin Identity.
* credentials shared across Organizations without explicit platform governance.
* cross-Organization external Resource mappings.
* capability grants targeting another Organization’s Resources.
* Workflow or Agent calls crossing tenant boundaries.

Provider callbacks must resolve to exactly one Installation and Organization.

---

# Required Indexes

Recommended indexes include:

```text
plugin_definitions (
    status,
    plugin_type
)

plugin_versions (
    plugin_definition_id,
    status
)

plugin_installations (
    organization_id,
    status
)

plugin_installations (
    organization_id,
    plugin_definition_id
)

plugin_credentials (
    organization_id,
    plugin_installation_id,
    status
)

plugin_capability_grants (
    organization_id,
    plugin_installation_id,
    status
)

plugin_webhook_deliveries (
    organization_id,
    plugin_installation_id,
    received_at
)

plugin_sync_states (
    organization_id,
    status,
    next_sync_at
)

plugin_executions (
    organization_id,
    status,
    requested_at
)

plugin_executions (
    organization_id,
    plugin_installation_id,
    requested_at
)

plugin_external_resources (
    organization_id,
    plugin_installation_id,
    external_resource_type,
    external_resource_id
)

plugin_health_checks (
    organization_id,
    plugin_installation_id,
    checked_at
)
```

---

# Retention

Plugin retention applies to:

* Installation records.
* configuration Versions.
* capability grants.
* credential metadata.
* webhook delivery metadata.
* synchronization state.
* Executions and attempts.
* external Resource mappings.
* health checks.
* lifecycle history.

Raw provider payloads should use short retention or should not be stored.

Execution records referenced by Incidents, Audit Records, or Legal Holds must be retained as required.

---

# Security Requirements

* Credentials must be stored only as secret references.
* Plugin input and output must be validated.
* Webhook signatures must be verified.
* Capability grants must follow least privilege.
* Plugins cannot grant themselves new permissions.
* Protected Actions require execution-time authorization.
* Provider errors must not expose credentials.
* Plugin payloads must not contain unnecessary sensitive data.
* Suspended Installations cannot execute normal operations.
* Cross-Organization access must be rejected.

---

# Audit Requirements

Auditable operations include:

* Plugin installation.
* Version upgrade.
* configuration activation.
* credential creation or rotation.
* capability grant or revocation.
* activation.
* suspension.
* archival.
* protected Plugin Actions.
* sensitive data access.
* manual synchronization.
* webhook secret rotation.

Execution history remains separate from general Audit Records.

---

# Business Rules

* Plugin Definitions are platform-owned.
* Every Plugin Installation belongs to exactly one Organization.
* Every Installation uses one distinct Plugin Identity.
* Plugin Versions are immutable.
* Installation configuration must not contain credentials.
* Credentials must use secure secret references.
* Granted capabilities cannot exceed declared capabilities.
* Plugins cannot grant themselves access.
* Every execution preserves the Plugin Version used.
* Provider callbacks must be authenticated and idempotent.
* Imported data must preserve source attribution.
* Plugin records do not become authoritative domain records automatically.
* Protected Actions execute through the owning capability.
* Suspended Installations cannot begin normal Executions.
* Cross-Organization relationships are prohibited.
* Missing or conflicting Organization ownership results in rejection.

---

# Success Criteria

The Plugin schema is successful when:

* Plugin Definitions and Installations remain clearly separated.
* every Installation remains tenant-scoped.
* historical configuration and Version usage can be reconstructed.
* credentials remain protected.
* capabilities remain explicit and revocable.
* callbacks and synchronization are idempotent.
* Plugin Executions remain attributable.
* imported data retains provider lineage.
* provider failures remain recoverable.
* unsafe Installations can be suspended.
* Plugins cannot bypass domain ownership or Policy controls.
* tenant isolation is preserved.

---

# Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/05-event-signal-schema.md`
* `docs/database/09-agent-schema.md`
* `docs/database/11-notification-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/15-retention-archival.md`
* `docs/functional/10-plugin-platform.md`
* `docs/functional/16-public-api.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/06-plugin-system.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-002-plugin-architecture.md`
* `docs/adr/ADR-008-security-policy-layer.md`
