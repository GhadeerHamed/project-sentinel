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

# Event Sources and Credentials

## `event_sources`

Represents the authenticated source that submits operational Events. The first Event Ingestion slice supports only the `api_client` source type.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `project_id`             | UUID        | Required    |
| `application_id`         | UUID        | Required    |
| `environment_id`         | UUID        | Required    |
| `source_type`            | VARCHAR     | Required: `api_client` |
| `name`                   | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required: `active` or `inactive` |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

`id` uses UUIDv7. The source's Organization and hierarchy scope must match every Event it accepts. At issuance, the server derives all four ownership identifiers from the nested route after validating an active Organization → Project → Application → Environment chain; clients provide only the required `name`. Plugin, Agent, webhook, manual, platform, external-provider, and configuration fields are deferred.

Constraints:

```text
UNIQUE (organization_id, id)
FOREIGN KEY (organization_id, project_id, application_id)
REFERENCES applications (organization_id, project_id, id)
FOREIGN KEY (organization_id, application_id, environment_id)
REFERENCES environments (organization_id, application_id, id)
INDEX (organization_id, status)
INDEX (organization_id, application_id, environment_id)
```

---

## `event_source_credentials`

Stores one active ingestion credential for an Event Source.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `event_source_id`   | UUID        | Required    |
| `secret_hash`       | VARCHAR     | Required    |
| `status`            | VARCHAR     | Required    |
| `created_at`        | TIMESTAMPTZ | Required    |
| `revoked_at`        | TIMESTAMPTZ | Optional    |
| `expires_at`        | TIMESTAMPTZ | Optional    |

The presented credential is `esc_{credential_id}.{secret}` in the HTTP Bearer header. Only `secret_hash` is stored; plaintext secrets are never persisted or logged. Credential statuses are `active`, `revoked`, `expired`, or `disabled`; any non-active credential fails authentication immediately.

Constraints:

```text
PRIMARY KEY (id)
UNIQUE (organization_id, id)
INDEX (organization_id, event_source_id, status)
CREATE UNIQUE INDEX event_source_credentials_one_active_per_source
ON event_source_credentials (organization_id, event_source_id)
WHERE status = 'active'
FOREIGN KEY (organization_id, event_source_id)
REFERENCES event_sources (organization_id, id)
```

Credential issuance is required before ingestion. The public `credential_id` after `esc_` is the indexed lookup identifier; the secret is verified only against `secret_hash`. The MVP supports one active credential per Event Source; rotation and concurrent credentials are deferred. The credential secret is not copied into the idempotency record used by Event Source issuance, so an idempotent retry returns the created public Event Source without re-disclosing that secret.

---

## Deferred Event Source Types

Future source types may include:

* `sdk`
* `plugin`
* `webhook`
* `agent`
* `manual`
* `internal`
* `import`

---

# Event Ingestion

## `event_ingestion_records`

Represents one received ingestion request.

| Column                | Type        | Rules       |
| --------------------- | ----------- | ----------- |
| `id`                  | UUID        | Primary key |
| `organization_id`     | UUID        | Required    |
| `event_source_id`     | UUID        | Required    |
| `operation`           | VARCHAR     | Required: `event.ingest` |
| `idempotency_key`     | VARCHAR     | Required    |
| `request_fingerprint` | VARCHAR     | Required    |
| `status`              | VARCHAR     | Required    |
| `received_at`         | TIMESTAMPTZ | Required    |
| `event_id`            | UUID        | Optional    |
| `rejection_code`      | VARCHAR     | Optional    |
| `rejection_reason`    | TEXT        | Optional    |
| `correlation_id`      | UUID        | Optional    |

Supported status values:

* `accepted`
* `duplicate`
* `rejected`
* `failed`

Possible uniqueness rules:

```text
UNIQUE (
    organization_id,
    event_source_id,
    operation,
    idempotency_key
)
```

The same idempotency key with a different normalized request fingerprint is rejected. A replay stores or returns the original Event reference. Source-event duplicate detection is enforced on the Event row and remains independent of this record.

---

# Canonical Events

## `events`

Stores normalized immutable operational Events.

| Column             | Type        | Rules       |
| ------------------ | ----------- | ----------- |
| `id`               | UUID        | Primary key |
| `organization_id`  | UUID        | Required    |
| `project_id`       | UUID        | Required    |
| `application_id`   | UUID        | Required    |
| `environment_id`   | UUID        | Required    |
| `event_source_id`  | UUID        | Required    |
| `external_event_id` | VARCHAR    | Optional    |
| `event_type`       | VARCHAR     | Required    |
| `category`         | VARCHAR     | Required    |
| `severity`         | VARCHAR     | Optional    |
| `occurred_at`      | TIMESTAMPTZ | Required    |
| `received_at`      | TIMESTAMPTZ | Required    |
| `correlation_id`   | UUID        | Optional    |
| `causation_id`     | UUID        | Optional    |
| `trace_id`         | VARCHAR     | Optional    |
| `payload`          | JSONB       | Required    |
| `attributes`       | JSONB       | Required, default `{}` |
| `schema_version`   | VARCHAR     | Required    |
| `normalized_event_hash` | VARCHAR  | Required, server-derived |
| `created_at`       | TIMESTAMPTZ | Required    |

Constraints:

```text
UNIQUE (organization_id, id)
CREATE UNIQUE INDEX events_source_external_event_id_unique
ON events (organization_id, event_source_id, external_event_id)
WHERE external_event_id IS NOT NULL
```

Event hierarchy references are validated as one active Organization → Project → Application → Environment chain before insertion. Tenant-aware foreign keys must enforce matching Organization ownership for the Event Source and each hierarchy reference. `normalized_event_hash` is computed from the normalized client envelope excluding `Idempotency-Key` and server-owned fields; it distinguishes matching and altered `external_event_id` submissions. Events do not require `updated_at`, status, or optimistic concurrency because they are immutable. `received_at` is the server acceptance timestamp; `created_at` is the persistence timestamp.

---

## Canonical Event Categories

The MVP categories are derived from this exact Event-type catalog; provider aliases are not normalized:

```text
application.error -> error
application.health_failed -> availability
application.health_recovered -> availability
deployment.completed -> deployment
custom.operational_event -> platform
```

The resulting categories are:

* `availability`
* `error`
* `deployment`
* `platform`

Additional categories are deferred. Category values remain broad and stable.

Provider-specific type mappings and a broader Event-type catalog are deferred.

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

The encoded `payload` plus optional `attributes` must not exceed 64 KiB. `payload` is a required JSON object; nested objects and arrays are permitted, and an empty object is valid. `attributes` is an optional flat JSON object with scalar values. There are no separate `metadata` or `tags` fields in the MVP.

---

# Deferred Event Attribute Projection

## `event_attributes`

The first slice preserves optional normalized attributes on `events.attributes` JSONB. A separate searchable attribute projection is deferred until attribute filtering or correlation requires it.

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
* Event Source credential is active.
* required fields are present.
* timestamps are valid.
* referenced Project, Application, and Environment form one active hierarchy in the Organization.
* `payload` plus optional `attributes` are within the 64 KiB limit.
* canonical envelope schema version is `1`.
* Event type is in the MVP catalog.
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

* required request idempotency.
* optional source External Event identifier.

The idempotency scope is Organization, Event Source, operation, key, and normalized request fingerprint. A same-key/same-request retry returns the existing Event; changed input conflicts.

When supplied, `external_event_id` is independently unique within `(organization_id, event_source_id, external_event_id)`. A matching `normalized_event_hash` returns the existing Event and records a `duplicate` ingestion result. Changed input with that external identifier conflicts.

Fingerprint and time-window semantic deduplication are deferred. Duplicate detection must not merge materially different Events.

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
3. `event.accepted` Outbox message.

Signal creation should normally create within one transaction:

1. Signal.
2. Signal evidence references.
3. Signal lifecycle history.
4. Outbox message.

External provider calls must not occur inside these transactions.

---

# Idempotency

Event ingestion requires idempotency.

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
* Events referencing another Organization’s Project.
* Signals using evidence from another Organization.
* Event Sources using another Organization’s Project, Application, or Environment.
* processing records crossing Organization boundaries.

Organization ownership must be validated before every insert and query.

---

# Partitioning Readiness

The `events` table is expected to become one of Sentinel’s largest tables.

Future partitioning may use:

* `received_at`
* `occurred_at`
* region
* Organization for selected dedicated tenants

Partitioning is deferred beyond the first Event ingestion slice and should be introduced only when actual volume justifies it.

Application contracts must not depend on physical partitions.

---

# Required Indexes

Recommended indexes include:

```text
event_sources (
    organization_id,
    status
)

event_source_credentials (
    organization_id,
    event_source_id,
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
    occurred_at,
    id
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
* Event Source credential issuance.
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
