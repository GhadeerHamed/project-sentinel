# Event and Signal Schema

## Purpose

This document defines the database schema for:

* Canonical Events.
* Event ingestion.
* Event sources.
* duplicate detection.
* processing state.
* Signals.
* Signal evidence.
* Signal lifecycle history.

Events represent immutable operational facts.

Signals represent interpreted observations derived from one or more Events.

Event Processing owns both Event and Signal records.

---

## Design Principles

* Every Event and Signal belongs to one Organization.
* Events are immutable after acceptance.
* Provider payloads are normalized into the Canonical Event Model.
* Occurrence time and ingestion time remain separate.
* Duplicate ingestion must not create duplicate Events.
* Signals must reference supporting evidence.
* Signal interpretation must remain explainable.
* Large Event tables must support future partitioning.
* Search and analytics remain derived from authoritative records.
* Raw secrets and credentials must never be stored in Event payloads.

---

## Schema Overview

```text
event_sources
    └── event_ingestion_records
            └── events
                    ├── event_attributes
                    ├── event_relationships
                    └── signal_evidence
                            └── signals
                                    └── signal_lifecycle_history
```

---

# Event Sources

## `event_sources`

Represents a source that submits operational Events.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `application_id`         | UUID        | Optional    |
| `environment_id`         | UUID        | Optional    |
| `source_type`            | VARCHAR     | Required    |
| `name`                   | VARCHAR     | Required    |
| `external_provider`      | VARCHAR     | Optional    |
| `plugin_installation_id` | UUID        | Optional    |
| `status`                 | VARCHAR     | Required    |
| `configuration`          | JSONB       | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Supported source types may include:

* `sdk`
* `api`
* `plugin`
* `webhook`
* `agent`
* `manual`
* `internal`
* `import`

Supported status values:

* `active`
* `suspended`
* `disabled`
* `archived`

Constraints:

```text
UNIQUE (organization_id, id)
UNIQUE (organization_id, name)
```

Application and Environment references must belong to the same Organization.

---

# Event Ingestion

## `event_ingestion_records`

Represents one received ingestion request.

| Column                | Type        | Rules       |
| --------------------- | ----------- | ----------- |
| `id`                  | UUID        | Primary key |
| `organization_id`     | UUID        | Required    |
| `event_source_id`     | UUID        | Required    |
| `external_event_id`   | VARCHAR     | Optional    |
| `idempotency_key`     | VARCHAR     | Optional    |
| `request_fingerprint` | VARCHAR     | Required    |
| `status`              | VARCHAR     | Required    |
| `received_at`         | TIMESTAMPTZ | Required    |
| `processed_at`        | TIMESTAMPTZ | Optional    |
| `event_id`            | UUID        | Optional    |
| `rejection_code`      | VARCHAR     | Optional    |
| `rejection_reason`    | TEXT        | Optional    |
| `correlation_id`      | UUID        | Optional    |

Supported status values:

* `received`
* `validated`
* `accepted`
* `duplicate`
* `rejected`
* `failed`

Possible uniqueness rules:

```text
UNIQUE (
    organization_id,
    event_source_id,
    external_event_id
)
```

This constraint applies when `external_event_id` exists.

Idempotency may also use:

```text
UNIQUE (
    organization_id,
    event_source_id,
    idempotency_key
)
```

The same idempotency key with a different request fingerprint must be rejected.

---

# Canonical Events

## `events`

Stores normalized immutable operational Events.

| Column             | Type        | Rules       |
| ------------------ | ----------- | ----------- |
| `id`               | UUID        | Primary key |
| `organization_id`  | UUID        | Required    |
| `project_id`       | UUID        | Optional    |
| `application_id`   | UUID        | Optional    |
| `environment_id`   | UUID        | Optional    |
| `event_source_id`  | UUID        | Required    |
| `event_type`       | VARCHAR     | Required    |
| `category`         | VARCHAR     | Required    |
| `severity`         | VARCHAR     | Optional    |
| `title`            | VARCHAR     | Required    |
| `description`      | TEXT        | Optional    |
| `occurred_at`      | TIMESTAMPTZ | Required    |
| `recorded_at`      | TIMESTAMPTZ | Required    |
| `processed_at`     | TIMESTAMPTZ | Optional    |
| `source_reference` | VARCHAR     | Optional    |
| `correlation_id`   | UUID        | Optional    |
| `causation_id`     | UUID        | Optional    |
| `trace_id`         | VARCHAR     | Optional    |
| `payload`          | JSONB       | Required    |
| `schema_version`   | VARCHAR     | Required    |
| `fingerprint`      | VARCHAR     | Optional    |
| `created_at`       | TIMESTAMPTZ | Required    |

Constraints:

```text
UNIQUE (organization_id, id)
```

Events do not require `updated_at` or optimistic concurrency because they are immutable.

---

## Canonical Event Categories

Event categories may include:

* `availability`
* `performance`
* `error`
* `security`
* `deployment`
* `configuration`
* `dependency`
* `capacity`
* `business`
* `administrative`
* `integration`
* `platform`

Category values should remain broad and stable.

Provider-specific types remain in normalized attributes or source metadata.

---

## Event Severity

Possible Event severity values:

* `critical`
* `high`
* `medium`
* `low`
* `informational`
* `unknown`

Event severity describes the source Event.

It does not automatically determine Alert or Incident severity.

---

## Event Payload

The `payload` field stores normalized event-specific information.

It may include:

* Error details.
* metric values.
* deployment metadata.
* dependency information.
* affected component.
* provider metadata.
* source labels.
* safe diagnostic context.

The payload must not contain:

* Passwords.
* API Keys.
* access tokens.
* Plugin credentials.
* unrestricted personal data.
* unnecessary confidential content.

Frequently queried fields should use dedicated columns rather than relying only on JSONB.

---

# Event Attributes

## `event_attributes`

Stores normalized searchable Event attributes.

| Column            | Type        | Rules    |
| ----------------- | ----------- | -------- |
| `organization_id` | UUID        | Required |
| `event_id`        | UUID        | Required |
| `attribute_key`   | VARCHAR     | Required |
| `attribute_value` | TEXT        | Required |
| `attribute_type`  | VARCHAR     | Required |
| `is_sensitive`    | BOOLEAN     | Required |
| `created_at`      | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (
    organization_id,
    event_id,
    attribute_key
)
```

Supported attribute types may include:

* `string`
* `integer`
* `decimal`
* `boolean`
* `timestamp`
* `identifier`

Event attributes should be used only for fields that require filtering or correlation.

---

# Event Relationships

## `event_relationships`

Connects Events to related Events or operational Resources.

| Column                  | Type        | Rules       |
| ----------------------- | ----------- | ----------- |
| `id`                    | UUID        | Primary key |
| `organization_id`       | UUID        | Required    |
| `event_id`              | UUID        | Required    |
| `relationship_type`     | VARCHAR     | Required    |
| `related_resource_type` | VARCHAR     | Required    |
| `related_resource_id`   | UUID        | Required    |
| `created_at`            | TIMESTAMPTZ | Required    |

Possible relationship types:

* `caused_by`
* `resulted_from`
* `related_to`
* `duplicate_of`
* `part_of`
* `observed_during`
* `affected`

Relationships must not be treated as proof of causation unless explicitly validated.

---

# Event Validation

An Event should be accepted only when:

* Organization context is valid.
* Event Source is Active.
* required fields are present.
* timestamps are valid.
* referenced Resources belong to the Organization.
* payload size is within limits.
* schema version is supported.
* sensitive data rules are satisfied.
* duplicate checks pass.

Rejected input remains represented through its ingestion record.

Invalid input must not create an authoritative Event.

---

# Event Immutability

After an Event is accepted:

* Core Event fields must not change.
* payload must not change.
* source ownership must not change.
* occurrence time must not change.
* Organization ownership must not change.

Corrections create a new Event referencing the original Event.

Possible correction relationship:

```text
corrects
```

---

# Duplicate Detection

Duplicate protection may use:

* External Event identifier.
* idempotency key.
* deterministic fingerprint.
* source reference.
* payload fingerprint.
* configured time window.

A duplicate ingestion record should reference the existing Event.

Duplicate detection must not merge materially different Events.

---

# Signals

## `signals`

Represents an interpreted operational observation.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `project_id`        | UUID        | Optional    |
| `application_id`    | UUID        | Optional    |
| `environment_id`    | UUID        | Optional    |
| `signal_type`       | VARCHAR     | Required    |
| `status`            | VARCHAR     | Required    |
| `severity`          | VARCHAR     | Required    |
| `title`             | VARCHAR     | Required    |
| `description`       | TEXT        | Optional    |
| `fingerprint`       | VARCHAR     | Optional    |
| `confidence`        | VARCHAR     | Required    |
| `first_observed_at` | TIMESTAMPTZ | Required    |
| `last_observed_at`  | TIMESTAMPTZ | Required    |
| `occurrence_count`  | BIGINT      | Required    |
| `created_by_type`   | VARCHAR     | Required    |
| `created_by_id`     | UUID        | Optional    |
| `explanation`       | JSONB       | Required    |
| `created_at`        | TIMESTAMPTZ | Required    |
| `updated_at`        | TIMESTAMPTZ | Required    |
| `resolved_at`       | TIMESTAMPTZ | Optional    |
| `version`           | BIGINT      | Required    |

Supported status values:

* `active`
* `resolved`
* `suppressed`
* `invalidated`

Supported confidence values:

* `high`
* `medium`
* `low`
* `unknown`

Supported creator types may include:

* `rule`
* `member`
* `agent`
* `plugin`
* `internal_process`

Constraints:

```text
UNIQUE (organization_id, id)
```

---

## Signal Types

Signal Types may represent observations such as:

* Error-rate increase.
* latency degradation.
* availability reduction.
* repeated failure.
* deployment-related change.
* dependency failure.
* capacity risk.
* suspicious activity.
* operational anomaly.
* recovery indication.

Signal Type values should represent Sentinel business meaning rather than provider-specific terminology.

---

# Signal Evidence

## `signal_evidence`

Connects Signals to supporting evidence.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `signal_id`         | UUID        | Required    |
| `evidence_type`     | VARCHAR     | Required    |
| `evidence_id`       | UUID        | Required    |
| `relationship_type` | VARCHAR     | Required    |
| `added_by_type`     | VARCHAR     | Required    |
| `added_by_id`       | UUID        | Optional    |
| `created_at`        | TIMESTAMPTZ | Required    |

Common evidence types include:

* `event`
* `signal`
* `deployment`
* `metric`
* `log`
* `trace`
* `plugin_execution`
* `agent_execution`

Possible relationship types:

* `supports`
* `contradicts`
* `triggered`
* `correlates`
* `recovery_evidence`

A Signal should normally have at least one supporting evidence record.

---

# Signal Lifecycle History

## `signal_lifecycle_history`

Stores immutable Signal lifecycle changes.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `signal_id`         | UUID        | Required    |
| `previous_status`   | VARCHAR     | Optional    |
| `new_status`        | VARCHAR     | Required    |
| `previous_severity` | VARCHAR     | Optional    |
| `new_severity`      | VARCHAR     | Optional    |
| `reason`            | TEXT        | Required    |
| `changed_by_type`   | VARCHAR     | Required    |
| `changed_by_id`     | UUID        | Optional    |
| `changed_at`        | TIMESTAMPTZ | Required    |
| `correlation_id`    | UUID        | Optional    |

History records are append-only.

---

# Signal Lifecycle

```text
Active

↓

Resolved
```

A Signal may also become:

* `suppressed`
* `invalidated`

A resolved Signal may become Active again if recurrence rules allow reopening.

Alternatively, a new Signal may be created when the new condition is materially separate.

---

# Signal Fingerprints

A Signal fingerprint may be derived from:

* Organization.
* Application.
* Environment.
* Signal Type.
* affected Service.
* dependency.
* error category.
* selected normalized attributes.

Fingerprints support:

* Signal grouping.
* occurrence counting.
* recurrence detection.
* downstream Alert deduplication.

Fingerprint logic must be deterministic and versioned where changes affect behavior.

---

# Signal Explanation

The `explanation` field should describe:

* Why the Signal was created.
* which rule, Agent, Plugin, or process created it.
* which evidence was used.
* how severity was determined.
* how confidence was determined.
* which assumptions or limitations apply.

Explanation data supports investigation and audit.

It must not replace evidence references.

---

# Processing Records

## `event_processing_attempts`

Records processing attempts for accepted Events.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `event_id`          | UUID        | Required    |
| `processor`         | VARCHAR     | Required    |
| `processor_version` | VARCHAR     | Required    |
| `status`            | VARCHAR     | Required    |
| `attempt_number`    | INTEGER     | Required    |
| `started_at`        | TIMESTAMPTZ | Required    |
| `completed_at`      | TIMESTAMPTZ | Optional    |
| `error_code`        | VARCHAR     | Optional    |
| `error_message`     | TEXT        | Optional    |

Supported status values:

* `running`
* `completed`
* `failed`
* `retrying`
* `cancelled`

Processing failures must not modify the immutable Event.

---

# Transactions

Event acceptance should normally create within one transaction:

1. Event ingestion result.
2. Canonical Event.
3. required Event attributes.
4. Outbox message.

Signal creation should normally create within one transaction:

1. Signal.
2. Signal evidence references.
3. Signal lifecycle history.
4. Outbox message.

External provider calls must not occur inside these transactions.

---

# Idempotency

Event ingestion must support idempotency.

Signal creation may also use idempotency for:

* Rule executions.
* Agent requests.
* Plugin callbacks.
* replayed domain events.

Repeated idempotent processing must return the existing Event or Signal rather than create duplicate business effects.

---

# Concurrency

Events do not require normal update concurrency because they are immutable.

Signals use optimistic concurrency through `version`.

Example:

```sql
UPDATE signals
SET
    severity = :severity,
    last_observed_at = :last_observed_at,
    occurrence_count = occurrence_count + 1,
    version = version + 1,
    updated_at = :updated_at
WHERE organization_id = :organization_id
  AND id = :signal_id
  AND version = :expected_version;
```

A failed Version match produces a concurrency conflict.

---

# Tenant Isolation

Every Event-related and Signal-related table must preserve `organization_id`.

The schema must prevent:

* Events referencing another Organization’s Application.
* Events referencing another Organization’s Environment.
* Signals using evidence from another Organization.
* Event Sources using another Organization’s Plugin Installation.
* processing records crossing Organization boundaries.

Organization ownership must be validated before every insert and query.

---

# Partitioning Readiness

The `events` table is expected to become one of Sentinel’s largest tables.

Future partitioning may use:

* `recorded_at`
* `occurred_at`
* region
* Organization for selected dedicated tenants

Initial partitioning should be introduced only when actual volume justifies it.

Application contracts must not depend on physical partitions.

---

# Required Indexes

Recommended indexes include:

```text
event_sources (
    organization_id,
    status
)

event_ingestion_records (
    organization_id,
    event_source_id,
    received_at
)

event_ingestion_records (
    organization_id,
    status,
    received_at
)

events (
    organization_id,
    recorded_at
)

events (
    organization_id,
    occurred_at
)

events (
    organization_id,
    application_id,
    occurred_at
)

events (
    organization_id,
    environment_id,
    occurred_at
)

events (
    organization_id,
    event_type,
    occurred_at
)

events (
    organization_id,
    category,
    severity,
    occurred_at
)

events (
    organization_id,
    correlation_id
)

events (
    organization_id,
    fingerprint,
    occurred_at
)

signals (
    organization_id,
    status,
    severity
)

signals (
    organization_id,
    application_id,
    status
)

signals (
    organization_id,
    environment_id,
    status
)

signals (
    organization_id,
    fingerprint,
    status
)

signal_evidence (
    organization_id,
    signal_id
)

signal_evidence (
    organization_id,
    evidence_type,
    evidence_id
)

event_processing_attempts (
    organization_id,
    event_id,
    processor
)
```

Indexes should remain bounded to avoid excessive Event ingestion cost.

---

# Retention

Retention may differ between:

* Accepted Events.
* rejected ingestion records.
* processing attempts.
* Event attributes.
* Signals.
* Signal history.
* Signal evidence.

Retention processing must preserve:

* Legal Holds.
* Incident evidence.
* security requirements.
* contractual requirements.
* required Audit history.

Deleting an Event must also remove or update:

* Search Projections.
* derived analytics.
* semantic representations.
* caches.
* unneeded object-storage evidence.

References required by retained Incidents may require archival rather than deletion.

---

# Security Requirements

* Event payloads must be validated and size-limited.
* Sensitive fields must be removed or protected.
* credentials must never enter Event payloads.
* source authentication must remain traceable.
* manual Event creation requires authorization.
* rejected Event input must not expose internal validation details.
* Event and Signal access must respect Resource Scope.
* suspicious ingestion may produce security Signals.

---

# Audit Requirements

Auditable operations include:

* Event Source creation.
* Event Source suspension.
* manual Event creation.
* Event correction.
* Event replay request.
* Signal manual creation.
* Signal suppression.
* Signal invalidation.
* Signal severity changes.
* processing re-execution.

High-volume normal Event ingestion does not require one general Audit Record per Event when Event records already provide sufficient source attribution.

---

# Business Rules

* Every Event belongs to exactly one Organization.
* Every Signal belongs to exactly one Organization.
* Accepted Events are immutable.
* Invalid input does not create an Event.
* Occurrence time and ingestion time remain separate.
* Duplicate ingestion must not create duplicate Events.
* Provider-specific data must be normalized.
* Event severity does not automatically determine Alert severity.
* Every Signal must have explainable creation context.
* Signals should reference supporting evidence.
* Evidence must belong to the same Organization.
* Signal confidence must remain explicit.
* Signal history is immutable.
* Events and Signals must not contain secrets.
* Search Projections are derived from Events and Signals.
* Processing failures must not modify accepted Events.
* Organization ownership must be present in all processing paths.
* Missing or conflicting ownership results in rejection.

---

# Success Criteria

The Event and Signal schema is successful when:

* Operational facts are stored immutably.
* provider input is normalized consistently.
* duplicate Event creation is prevented.
* Event timing remains accurate.
* Signal interpretation remains explainable.
* every Signal remains traceable to evidence.
* tenant isolation is preserved.
* high-volume Event ingestion can scale.
* processing failures remain recoverable.
* Search and analytics can be rebuilt.
* sensitive information remains protected.
* downstream Alert processing receives consistent Signals.

---

# Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/04-application-environment-schema.md`
* `docs/database/06-alert-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/database/16-indexing-strategy.md`
* `docs/functional/05-event-processing.md`
* `docs/functional/06-alert-management.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/05-event-model.md`
* `docs/adr/ADR-004-operational-processing-model.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-011-canonical-event-model.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
