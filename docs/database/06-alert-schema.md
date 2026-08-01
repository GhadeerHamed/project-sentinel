# Alert Schema

## Purpose

This document defines the database schema for Alert Management.

It covers:

* Alert Rules.
* Alerts.
* Alert evidence.
* occurrences.
* lifecycle history.
* assignment.
* acknowledgement.
* suppression.
* correlation.
* escalation.
* Incident relationships.

Alert Management owns the authoritative Alert state.

---

## Design Principles

* Every Alert belongs to one Organization.
* Every Alert must reference supporting evidence.
* Alert lifecycle history is immutable.
* Severity and priority changes remain traceable.
* Duplicate operational conditions should update an existing Alert where appropriate.
* Suppression must not delete evidence.
* Assignment and acknowledgement are separate concepts.
* Alert and Incident lifecycles remain separate.
* Cross-Organization relationships are prohibited.
* Mutable records use optimistic concurrency.

---

## Schema Overview

```text
alert_rules
└── alert_rule_versions

alerts
├── alert_occurrences
├── alert_evidence
├── alert_lifecycle_history
├── alert_assignments
├── alert_acknowledgements
├── alert_suppressions
├── alert_correlations
├── alert_escalations
└── alert_incident_links
```

---

# Alert Rules

## `alert_rules`

Represents the logical identity and lifecycle of an Alert Rule.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `slug`                   | VARCHAR     | Required    |
| `description`            | TEXT        | Optional    |
| `status`                 | VARCHAR     | Required    |
| `owner_type`             | VARCHAR     | Optional    |
| `owner_id`               | UUID        | Optional    |
| `active_version_id`      | UUID        | Optional    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Supported status values:

* `draft`
* `active`
* `suspended`
* `deprecated`
* `archived`

Constraints:

```text
UNIQUE (organization_id, id)
UNIQUE (organization_id, slug)
```

---

## `alert_rule_versions`

Stores immutable Alert Rule versions.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `alert_rule_id`            | UUID        | Required    |
| `version_number`           | BIGINT      | Required    |
| `condition_definition`     | JSONB       | Required    |
| `severity_definition`      | JSONB       | Required    |
| `priority_definition`      | JSONB       | Required    |
| `deduplication_definition` | JSONB       | Required    |
| `scope_definition`         | JSONB       | Required    |
| `created_by_identity_id`   | UUID        | Required    |
| `created_at`               | TIMESTAMPTZ | Required    |
| `activated_at`             | TIMESTAMPTZ | Optional    |

Constraints:

```text
UNIQUE (
    organization_id,
    alert_rule_id,
    version_number
)
```

Active Alert Rule Versions are immutable.

---

# Alerts

## `alerts`

Stores the current authoritative Alert state.

| Column                  | Type        | Rules       |
| ----------------------- | ----------- | ----------- |
| `id`                    | UUID        | Primary key |
| `organization_id`       | UUID        | Required    |
| `project_id`            | UUID        | Optional    |
| `application_id`        | UUID        | Optional    |
| `environment_id`        | UUID        | Optional    |
| `alert_rule_id`         | UUID        | Optional    |
| `alert_rule_version_id` | UUID        | Optional    |
| `title`                 | VARCHAR     | Required    |
| `description`           | TEXT        | Optional    |
| `status`                | VARCHAR     | Required    |
| `severity`              | VARCHAR     | Required    |
| `priority`              | VARCHAR     | Required    |
| `fingerprint`           | VARCHAR     | Required    |
| `source_type`           | VARCHAR     | Required    |
| `source_id`             | UUID        | Optional    |
| `occurrence_count`      | BIGINT      | Required    |
| `first_observed_at`     | TIMESTAMPTZ | Required    |
| `last_observed_at`      | TIMESTAMPTZ | Required    |
| `acknowledged_at`       | TIMESTAMPTZ | Optional    |
| `resolved_at`           | TIMESTAMPTZ | Optional    |
| `closed_at`             | TIMESTAMPTZ | Optional    |
| `created_at`            | TIMESTAMPTZ | Required    |
| `updated_at`            | TIMESTAMPTZ | Required    |
| `version`               | BIGINT      | Required    |

Supported status values:

* `open`
* `acknowledged`
* `investigating`
* `resolved`
* `closed`
* `suppressed`
* `cancelled`

Supported severity values:

* `critical`
* `high`
* `medium`
* `low`
* `informational`

Suggested priority values:

* `urgent`
* `high`
* `normal`
* `low`

Constraints:

```text
UNIQUE (organization_id, id)
```

A partial uniqueness rule may prevent multiple active Alerts with the same fingerprint:

```text
UNIQUE (
    organization_id,
    fingerprint
)
WHERE status IN (
    'open',
    'acknowledged',
    'investigating',
    'suppressed'
)
```

The exact active-state uniqueness rule depends on recurrence behavior.

---

## Alert Source Types

Possible `source_type` values include:

* `signal`
* `rule`
* `member`
* `agent`
* `plugin`
* `internal_process`

The source identifies how the Alert was created.

It does not replace evidence references.

---

# Alert Occurrences

## `alert_occurrences`

Records each qualifying recurrence of an Alert condition.

| Column             | Type        | Rules       |
| ------------------ | ----------- | ----------- |
| `id`               | UUID        | Primary key |
| `organization_id`  | UUID        | Required    |
| `alert_id`         | UUID        | Required    |
| `signal_id`        | UUID        | Optional    |
| `event_id`         | UUID        | Optional    |
| `occurred_at`      | TIMESTAMPTZ | Required    |
| `recorded_at`      | TIMESTAMPTZ | Required    |
| `severity`         | VARCHAR     | Optional    |
| `source_reference` | VARCHAR     | Optional    |
| `created_at`       | TIMESTAMPTZ | Required    |

Occurrences are append-only.

They support:

* Deduplication.
* recurrence tracking.
* severity reevaluation.
* escalation.
* historical analysis.

---

# Alert Evidence

## `alert_evidence`

Connects an Alert to supporting evidence.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `alert_id`          | UUID        | Required    |
| `evidence_type`     | VARCHAR     | Required    |
| `evidence_id`       | UUID        | Required    |
| `relationship_type` | VARCHAR     | Required    |
| `added_by_type`     | VARCHAR     | Required    |
| `added_by_id`       | UUID        | Optional    |
| `created_at`        | TIMESTAMPTZ | Required    |

Common evidence types:

* `event`
* `signal`
* `deployment`
* `metric`
* `log`
* `trace`
* `agent_finding`
* `plugin_execution`

Possible relationship types:

* `triggered`
* `supports`
* `contradicts`
* `correlates`
* `recovery_evidence`

Evidence must belong to the same Organization as the Alert.

---

# Alert Lifecycle History

## `alert_lifecycle_history`

Stores immutable Alert changes.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `alert_id`          | UUID        | Required    |
| `change_type`       | VARCHAR     | Required    |
| `previous_status`   | VARCHAR     | Optional    |
| `new_status`        | VARCHAR     | Optional    |
| `previous_severity` | VARCHAR     | Optional    |
| `new_severity`      | VARCHAR     | Optional    |
| `previous_priority` | VARCHAR     | Optional    |
| `new_priority`      | VARCHAR     | Optional    |
| `reason`            | TEXT        | Required    |
| `changed_by_type`   | VARCHAR     | Required    |
| `changed_by_id`     | UUID        | Optional    |
| `changed_at`        | TIMESTAMPTZ | Required    |
| `correlation_id`    | UUID        | Optional    |

Common change types:

* `created`
* `status_changed`
* `severity_changed`
* `priority_changed`
* `reopened`
* `cancelled`
* `scope_changed`

History records must not be modified after creation.

---

# Assignment

## `alert_assignments`

Stores Alert ownership history.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `alert_id`                | UUID        | Required    |
| `assignee_type`           | VARCHAR     | Required    |
| `assignee_id`             | UUID        | Required    |
| `assignment_role`         | VARCHAR     | Required    |
| `assigned_by_identity_id` | UUID        | Required    |
| `assigned_at`             | TIMESTAMPTZ | Required    |
| `released_at`             | TIMESTAMPTZ | Optional    |
| `release_reason`          | TEXT        | Optional    |

Supported assignee types:

* `membership`
* `team`

Suggested assignment roles:

* `owner`
* `responder`
* `reviewer`

Only active assignments contribute to current Alert ownership.

---

# Acknowledgement

## `alert_acknowledgements`

Records acknowledgement decisions.

| Column                        | Type        | Rules       |
| ----------------------------- | ----------- | ----------- |
| `id`                          | UUID        | Primary key |
| `organization_id`             | UUID        | Required    |
| `alert_id`                    | UUID        | Required    |
| `acknowledged_by_identity_id` | UUID        | Required    |
| `acknowledged_at`             | TIMESTAMPTZ | Required    |
| `note`                        | TEXT        | Optional    |
| `assignment_id`               | UUID        | Optional    |

Acknowledgement confirms awareness or responsibility.

It does not resolve the Alert.

---

# Suppression

## `alert_suppressions`

Stores Alert-specific suppression decisions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `alert_id`               | UUID        | Required    |
| `reason`                 | TEXT        | Required    |
| `scope`                  | JSONB       | Required    |
| `started_at`             | TIMESTAMPTZ | Required    |
| `expires_at`             | TIMESTAMPTZ | Optional    |
| `created_by_identity_id` | UUID        | Required    |
| `ended_at`               | TIMESTAMPTZ | Optional    |
| `ended_by_identity_id`   | UUID        | Optional    |

Suppression may be associated with:

* Maintenance.
* duplicate conditions.
* known operational exceptions.
* active Incident coverage.
* approved manual suppression.

Suppression must not remove evidence or Alert history.

---

# Alert Correlation

## `alert_correlations`

Connects related Alerts.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `alert_id`          | UUID        | Required    |
| `related_alert_id`  | UUID        | Required    |
| `relationship_type` | VARCHAR     | Required    |
| `confidence`        | VARCHAR     | Optional    |
| `reason`            | TEXT        | Optional    |
| `created_by_type`   | VARCHAR     | Required    |
| `created_by_id`     | UUID        | Optional    |
| `created_at`        | TIMESTAMPTZ | Required    |

Possible relationship types:

* `related`
* `duplicate`
* `parent`
* `child`
* `same_dependency`
* `same_deployment`
* `similar_history`

Constraints:

```text
CHECK (alert_id <> related_alert_id)
```

Both Alerts must belong to the same Organization.

Correlation does not prove causation.

---

# Escalation

## `alert_escalations`

Records Alert escalation activity.

| Column             | Type        | Rules       |
| ------------------ | ----------- | ----------- |
| `id`               | UUID        | Primary key |
| `organization_id`  | UUID        | Required    |
| `alert_id`         | UUID        | Required    |
| `escalation_level` | INTEGER     | Required    |
| `reason`           | TEXT        | Required    |
| `trigger_type`     | VARCHAR     | Required    |
| `target_type`      | VARCHAR     | Optional    |
| `target_id`        | UUID        | Optional    |
| `created_at`       | TIMESTAMPTZ | Required    |
| `resolved_at`      | TIMESTAMPTZ | Optional    |

Possible escalation triggers:

* `missing_acknowledgement`
* `severity_increase`
* `priority_increase`
* `duration`
* `recurrence`
* `scope_expansion`
* `manual`
* `policy`

Escalation history is append-only.

---

# Incident Relationships

## `alert_incident_links`

Connects Alerts to Incidents.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `alert_id`          | UUID        | Required    |
| `incident_id`       | UUID        | Required    |
| `relationship_type` | VARCHAR     | Required    |
| `reason`            | TEXT        | Optional    |
| `linked_by_type`    | VARCHAR     | Required    |
| `linked_by_id`      | UUID        | Optional    |
| `linked_at`         | TIMESTAMPTZ | Required    |
| `unlinked_at`       | TIMESTAMPTZ | Optional    |

Possible relationship types:

* `triggered`
* `symptom`
* `related`
* `recovery_evidence`
* `recurrence`

Constraints:

```text
UNIQUE (
    organization_id,
    alert_id,
    incident_id,
    relationship_type
)
```

Incident Management owns Incident lifecycle.

Alert Management owns Alert lifecycle and the Alert-side relationship.

---

# Alert Creation Transaction

Alert creation should normally create within one transaction:

1. Alert.
2. initial occurrence.
3. supporting evidence.
4. lifecycle history.
5. initial assignment where applicable.
6. Outbox message.

External Notifications or Plugin actions must not occur inside the transaction.

---

# Alert Update Transaction

A lifecycle transition should normally create within one transaction:

1. Updated Alert state.
2. lifecycle history.
3. related assignment, acknowledgement, or suppression record.
4. Outbox message.

The expected Alert Version must match the current Version.

---

# Optimistic Concurrency

Alerts use optimistic concurrency through `version`.

Example:

```sql
UPDATE alerts
SET
    status = :new_status,
    version = version + 1,
    updated_at = :updated_at
WHERE organization_id = :organization_id
  AND id = :alert_id
  AND version = :expected_version;
```

If no row is updated, Sentinel returns a concurrency conflict.

---

# Deduplication

When a matching active Alert exists, Sentinel may:

* Add an occurrence.
* add new evidence.
* update `last_observed_at`.
* increase `occurrence_count`.
* reevaluate severity.
* trigger escalation.

Deduplication must not merge conditions with materially different:

* Applications.
* Environments.
* Services.
* impact.
* security scope.

---

# Tenant Isolation

Every Alert-related table must include `organization_id`.

The schema must prevent:

* Alert evidence from another Organization.
* assignment to another Organization’s Member or Team.
* correlation with another Organization’s Alert.
* Incident links across Organizations.
* Alert Rules from another Organization.
* cross-Organization suppression or escalation.

Organization ownership must be validated before every write.

---

# Required Indexes

Recommended indexes include:

```text
alert_rules (
    organization_id,
    status
)

alerts (
    organization_id,
    status,
    severity
)

alerts (
    organization_id,
    priority,
    status
)

alerts (
    organization_id,
    application_id,
    status
)

alerts (
    organization_id,
    environment_id,
    status
)

alerts (
    organization_id,
    fingerprint,
    status
)

alerts (
    organization_id,
    first_observed_at
)

alerts (
    organization_id,
    last_observed_at
)

alert_occurrences (
    organization_id,
    alert_id,
    occurred_at
)

alert_evidence (
    organization_id,
    alert_id
)

alert_evidence (
    organization_id,
    evidence_type,
    evidence_id
)

alert_assignments (
    organization_id,
    alert_id,
    released_at
)

alert_assignments (
    organization_id,
    assignee_type,
    assignee_id,
    released_at
)

alert_suppressions (
    organization_id,
    alert_id,
    expires_at
)

alert_escalations (
    organization_id,
    alert_id,
    created_at
)

alert_incident_links (
    organization_id,
    incident_id,
    unlinked_at
)
```

Indexes should support active operational queries without creating excessive write overhead.

---

# Retention

Alert retention applies to:

* Alerts.
* occurrences.
* evidence references.
* lifecycle history.
* assignments.
* acknowledgements.
* suppressions.
* correlations.
* escalations.
* Incident links.

Closed and Cancelled Alerts may be archived according to Organization Policy.

Alert evidence required by retained Incidents must not be removed prematurely.

---

# Security Requirements

* Alert access must respect Organization and Resource Scope.
* Sensitive evidence must require appropriate permission.
* Alert titles and descriptions must not contain secrets.
* Manual suppression requires authorization.
* Critical severity reductions may require approval.
* Cross-Organization references must be rejected.
* Agent or Plugin-created Alerts must preserve acting identity and source.

---

# Audit Requirements

Auditable operations include:

* Manual Alert creation.
* severity changes.
* priority changes.
* assignment.
* acknowledgement.
* suppression.
* cancellation.
* reopening.
* correlation changes.
* escalation.
* Incident linking.

High-volume automatic occurrence updates may rely on Alert history and source attribution rather than separate general Audit Records for every occurrence.

---

# Business Rules

* Every Alert belongs to exactly one Organization.
* Every Alert has one current status, severity, and priority.
* Every Alert has a deterministic fingerprint.
* Every Alert must reference supporting evidence.
* Alert evidence remains immutable.
* Alert lifecycle history remains immutable.
* Duplicate conditions should update an existing active Alert where appropriate.
* Deduplication must not hide materially different scope or impact.
* Acknowledgement does not resolve an Alert.
* Suppression does not delete an Alert.
* Suppression requires a reason.
* Correlation does not prove causation.
* Alert and Incident lifecycles remain separate.
* Severity and priority changes remain historically traceable.
* Mutable Alert updates require optimistic concurrency.
* Cross-Organization Alert relationships are prohibited.
* Missing or conflicting Organization ownership results in rejection.

---

# Success Criteria

The Alert schema is successful when:

* Alerts remain traceable to evidence.
* duplicate Alert noise is reduced.
* current lifecycle state is easy to query.
* lifecycle history can be reconstructed.
* severity and priority changes remain explainable.
* assignment and acknowledgement remain clear.
* suppression remains governed.
* related Alerts can be correlated safely.
* Alert-to-Incident relationships remain traceable.
* concurrent updates do not overwrite each other.
* tenant isolation is preserved.
* active Alert queries remain efficient.

---

# Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/04-application-environment-schema.md`
* `docs/database/05-event-signal-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/database/16-indexing-strategy.md`
* `docs/functional/06-alert-management.md`
* `docs/functional/07-incident-management.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/05-event-model.md`
* `docs/adr/ADR-004-operational-processing-model.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
