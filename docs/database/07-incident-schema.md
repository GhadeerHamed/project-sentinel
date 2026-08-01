# Incident Schema

## Purpose

This document defines the database schema for Incident Management.

It covers:

* Incidents.
* lifecycle history.
* responder roles.
* affected scope.
* timeline entries.
* evidence.
* hypotheses.
* Findings.
* Decisions.
* Response Actions.
* status updates.
* Alert relationships.
* Post-Incident Reviews.
* Follow-Up Actions.

Incident Management owns the authoritative Incident state.

---

## Design Principles

* Every Incident belongs to one Organization.
* Incident state and Workflow state remain separate.
* Timeline and history records are immutable.
* Evidence remains owned by its source capability.
* Facts, hypotheses, Findings, and Decisions remain distinguishable.
* Severity changes remain traceable.
* Response Actions require explicit ownership and status.
* Resolution and closure are separate lifecycle decisions.
* Unknown root cause is valid.
* Mutable records use optimistic concurrency.
* Cross-Organization relationships are prohibited.

---

## Schema Overview

```text
incidents
├── incident_lifecycle_history
├── incident_roles
├── incident_scope
├── incident_timeline
├── incident_evidence
├── incident_hypotheses
├── incident_findings
├── incident_decisions
├── incident_actions
├── incident_status_updates
├── incident_alert_links
├── incident_relationships
├── post_incident_reviews
└── incident_follow_up_actions
```

---

## Incidents

### `incidents`

Stores the current authoritative Incident state.

| Column               | Type        | Rules       |
| -------------------- | ----------- | ----------- |
| `id`                 | UUID        | Primary key |
| `organization_id`    | UUID        | Required    |
| `project_id`         | UUID        | Optional    |
| `application_id`     | UUID        | Optional    |
| `environment_id`     | UUID        | Optional    |
| `title`              | VARCHAR     | Required    |
| `summary`            | TEXT        | Required    |
| `status`             | VARCHAR     | Required    |
| `severity`           | VARCHAR     | Required    |
| `impact_summary`     | TEXT        | Optional    |
| `root_cause_status`  | VARCHAR     | Required    |
| `root_cause_summary` | TEXT        | Optional    |
| `declared_by_type`   | VARCHAR     | Required    |
| `declared_by_id`     | UUID        | Optional    |
| `declared_at`        | TIMESTAMPTZ | Required    |
| `mitigated_at`       | TIMESTAMPTZ | Optional    |
| `resolved_at`        | TIMESTAMPTZ | Optional    |
| `closed_at`          | TIMESTAMPTZ | Optional    |
| `cancelled_at`       | TIMESTAMPTZ | Optional    |
| `created_at`         | TIMESTAMPTZ | Required    |
| `updated_at`         | TIMESTAMPTZ | Required    |
| `version`            | BIGINT      | Required    |

Supported statuses:

* `declared`
* `investigating`
* `mitigating`
* `monitoring`
* `resolved`
* `closed`
* `cancelled`
* `merged`

Supported severity values:

* `critical`
* `high`
* `medium`
* `low`

Supported root-cause statuses:

* `unknown`
* `suspected`
* `probable`
* `confirmed`
* `multiple`
* `external`
* `not_applicable`

Constraints:

```text
UNIQUE (organization_id, id)
```

---

## Incident Lifecycle History

### `incident_lifecycle_history`

Stores immutable lifecycle and severity changes.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `incident_id`       | UUID        | Required    |
| `change_type`       | VARCHAR     | Required    |
| `previous_status`   | VARCHAR     | Optional    |
| `new_status`        | VARCHAR     | Optional    |
| `previous_severity` | VARCHAR     | Optional    |
| `new_severity`      | VARCHAR     | Optional    |
| `reason`            | TEXT        | Required    |
| `changed_by_type`   | VARCHAR     | Required    |
| `changed_by_id`     | UUID        | Optional    |
| `changed_at`        | TIMESTAMPTZ | Required    |
| `correlation_id`    | UUID        | Optional    |

Common change types:

* `declared`
* `status_changed`
* `severity_changed`
* `reopened`
* `cancelled`
* `merged`
* `scope_changed`

History records are append-only.

---

## Incident Roles

### `incident_roles`

Stores responder and leadership assignments.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `incident_id`             | UUID        | Required    |
| `role_type`               | VARCHAR     | Required    |
| `assignee_type`           | VARCHAR     | Required    |
| `assignee_id`             | UUID        | Required    |
| `assigned_by_identity_id` | UUID        | Required    |
| `assigned_at`             | TIMESTAMPTZ | Required    |
| `released_at`             | TIMESTAMPTZ | Optional    |
| `release_reason`          | TEXT        | Optional    |

Supported roles:

* `incident_commander`
* `technical_lead`
* `communications_lead`
* `scribe`
* `responder`
* `subject_matter_expert`
* `reviewer`

Supported assignee types:

* `membership`
* `team`

Only active role assignments contribute to current response ownership.

A High or Critical active Incident should have one active Incident Commander.

---

## Affected Scope

### `incident_scope`

Stores Resources affected or potentially affected by an Incident.

| Column            | Type        | Rules       |
| ----------------- | ----------- | ----------- |
| `id`              | UUID        | Primary key |
| `organization_id` | UUID        | Required    |
| `incident_id`     | UUID        | Required    |
| `resource_type`   | VARCHAR     | Required    |
| `resource_id`     | UUID        | Required    |
| `impact_type`     | VARCHAR     | Required    |
| `impact_status`   | VARCHAR     | Required    |
| `description`     | TEXT        | Optional    |
| `added_by_type`   | VARCHAR     | Required    |
| `added_by_id`     | UUID        | Optional    |
| `added_at`        | TIMESTAMPTZ | Required    |
| `removed_at`      | TIMESTAMPTZ | Optional    |

Possible Resource Types:

* `project`
* `application`
* `environment`
* `service`
* `dependency`
* `region`
* `customer_group`
* `business_process`

Possible impact statuses:

* `suspected`
* `confirmed`
* `recovered`
* `not_affected`

---

## Incident Timeline

### `incident_timeline`

Stores the authoritative ordered Incident timeline.

| Column                  | Type        | Rules       |
| ----------------------- | ----------- | ----------- |
| `id`                    | UUID        | Primary key |
| `organization_id`       | UUID        | Required    |
| `incident_id`           | UUID        | Required    |
| `entry_type`            | VARCHAR     | Required    |
| `summary`               | TEXT        | Required    |
| `occurred_at`           | TIMESTAMPTZ | Required    |
| `recorded_at`           | TIMESTAMPTZ | Required    |
| `author_type`           | VARCHAR     | Required    |
| `author_id`             | UUID        | Optional    |
| `related_resource_type` | VARCHAR     | Optional    |
| `related_resource_id`   | UUID        | Optional    |
| `correction_of_id`      | UUID        | Optional    |
| `visibility`            | VARCHAR     | Required    |

Possible entry types:

* `declaration`
* `status_change`
* `severity_change`
* `scope_change`
* `evidence_added`
* `hypothesis`
* `finding`
* `decision`
* `action`
* `mitigation`
* `status_update`
* `resolution`
* `closure`

Timeline entries are immutable.

Corrections create new entries referencing the original entry.

---

## Incident Evidence

### `incident_evidence`

Connects an Incident to authoritative evidence.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `incident_id`       | UUID        | Required    |
| `evidence_type`     | VARCHAR     | Required    |
| `evidence_id`       | UUID        | Required    |
| `relationship_type` | VARCHAR     | Required    |
| `added_by_type`     | VARCHAR     | Required    |
| `added_by_id`       | UUID        | Optional    |
| `created_at`        | TIMESTAMPTZ | Required    |

Evidence types may include:

* `event`
* `signal`
* `alert`
* `deployment`
* `metric`
* `log`
* `trace`
* `agent_finding`
* `plugin_execution`
* `audit_record`
* `file`

Possible relationship types:

* `supports`
* `contradicts`
* `triggered`
* `correlates`
* `recovery_evidence`
* `resolution_evidence`

Evidence remains owned by its source capability.

---

## Hypotheses

### `incident_hypotheses`

Stores possible explanations that are not yet confirmed.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `incident_id`       | UUID        | Required    |
| `statement`         | TEXT        | Required    |
| `status`            | VARCHAR     | Required    |
| `confidence`        | VARCHAR     | Required    |
| `owner_identity_id` | UUID        | Optional    |
| `created_by_type`   | VARCHAR     | Required    |
| `created_by_id`     | UUID        | Optional    |
| `created_at`        | TIMESTAMPTZ | Required    |
| `updated_at`        | TIMESTAMPTZ | Required    |
| `resolved_at`       | TIMESTAMPTZ | Optional    |
| `version`           | BIGINT      | Required    |

Supported statuses:

* `proposed`
* `investigating`
* `supported`
* `rejected`
* `inconclusive`

Supported confidence values:

* `high`
* `medium`
* `low`
* `unknown`

---

## Hypothesis Evidence

### `incident_hypothesis_evidence`

Connects evidence to a Hypothesis.

| Column                  | Type        | Rules    |
| ----------------------- | ----------- | -------- |
| `organization_id`       | UUID        | Required |
| `hypothesis_id`         | UUID        | Required |
| `evidence_id`           | UUID        | Required |
| `evidence_relationship` | VARCHAR     | Required |
| `created_at`            | TIMESTAMPTZ | Required |

Possible evidence relationships:

* `supports`
* `contradicts`

Primary key:

```text
PRIMARY KEY (
    organization_id,
    hypothesis_id,
    evidence_id,
    evidence_relationship
)
```

---

## Findings

### `incident_findings`

Stores supported Incident conclusions.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `incident_id`             | UUID        | Required    |
| `statement`               | TEXT        | Required    |
| `confidence`              | VARCHAR     | Required    |
| `status`                  | VARCHAR     | Required    |
| `source_type`             | VARCHAR     | Required    |
| `source_id`               | UUID        | Optional    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `reviewed_by_identity_id` | UUID        | Optional    |
| `reviewed_at`             | TIMESTAMPTZ | Optional    |
| `superseded_at`           | TIMESTAMPTZ | Optional    |

Supported statuses:

* `proposed`
* `accepted`
* `rejected`
* `superseded`
* `inconclusive`

Possible source types:

* `member`
* `team`
* `agent`
* `plugin`
* `internal_process`

Agent-generated Findings must remain distinguishable from human-reviewed Findings.

---

## Finding Evidence

### `incident_finding_evidence`

Connects Findings to supporting evidence.

| Column              | Type        | Rules    |
| ------------------- | ----------- | -------- |
| `organization_id`   | UUID        | Required |
| `finding_id`        | UUID        | Required |
| `evidence_id`       | UUID        | Required |
| `relationship_type` | VARCHAR     | Required |
| `created_at`        | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (
    organization_id,
    finding_id,
    evidence_id
)
```

---

## Decisions

### `incident_decisions`

Stores important operational decisions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `incident_id`            | UUID        | Required    |
| `decision`               | TEXT        | Required    |
| `rationale`              | TEXT        | Required    |
| `decision_status`        | VARCHAR     | Required    |
| `decided_by_identity_id` | UUID        | Required    |
| `decided_at`             | TIMESTAMPTZ | Required    |
| `approval_id`            | UUID        | Optional    |
| `supersedes_decision_id` | UUID        | Optional    |

Possible statuses:

* `active`
* `superseded`
* `reversed`
* `cancelled`

Decisions are immutable.

A changed decision creates a new record.

---

## Response Actions

### `incident_actions`

Stores investigation, mitigation, recovery, and validation work.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `incident_id`          | UUID        | Required    |
| `action_type`          | VARCHAR     | Required    |
| `description`          | TEXT        | Required    |
| `status`               | VARCHAR     | Required    |
| `priority`             | VARCHAR     | Required    |
| `owner_type`           | VARCHAR     | Required    |
| `owner_id`             | UUID        | Required    |
| `expected_result`      | TEXT        | Optional    |
| `actual_result`        | TEXT        | Optional    |
| `requires_approval`    | BOOLEAN     | Required    |
| `approval_id`          | UUID        | Optional    |
| `workflow_instance_id` | UUID        | Optional    |
| `due_at`               | TIMESTAMPTZ | Optional    |
| `started_at`           | TIMESTAMPTZ | Optional    |
| `completed_at`         | TIMESTAMPTZ | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |
| `updated_at`           | TIMESTAMPTZ | Required    |
| `version`              | BIGINT      | Required    |

Supported statuses:

* `planned`
* `authorized`
* `in_progress`
* `blocked`
* `completed`
* `failed`
* `cancelled`
* `rejected`

Possible action types:

* `investigation`
* `mitigation`
* `recovery`
* `validation`
* `communication`
* `follow_up`

---

## Action Evidence

### `incident_action_evidence`

Connects Response Actions to supporting or resulting evidence.

| Column               | Type        | Rules    |
| -------------------- | ----------- | -------- |
| `organization_id`    | UUID        | Required |
| `incident_action_id` | UUID        | Required |
| `evidence_type`      | VARCHAR     | Required |
| `evidence_id`        | UUID        | Required |
| `relationship_type`  | VARCHAR     | Required |
| `created_at`         | TIMESTAMPTZ | Required |

Possible relationships:

* `input`
* `result`
* `verification`
* `failure`
* `rollback`

---

## Status Updates

### `incident_status_updates`

Stores formal Incident communication updates.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `incident_id`            | UUID        | Required    |
| `summary`                | TEXT        | Required    |
| `current_impact`         | TEXT        | Optional    |
| `current_status`         | TEXT        | Required    |
| `next_actions`           | TEXT        | Optional    |
| `next_update_at`         | TIMESTAMPTZ | Optional    |
| `visibility`             | VARCHAR     | Required    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |

Possible visibility values:

* `responders`
* `organization`
* `restricted`
* `external_approved`

Publishing externally requires separate authorization and Notification or Status Page processing.

---

## Alert Relationships

### `incident_alert_links`

Connects Incidents with related Alerts.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `incident_id`       | UUID        | Required    |
| `alert_id`          | UUID        | Required    |
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
* `recurrence`
* `recovery_evidence`

Constraint:

```text
UNIQUE (
    organization_id,
    incident_id,
    alert_id,
    relationship_type
)
```

---

## Incident Relationships

### `incident_relationships`

Connects related Incidents.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `incident_id`            | UUID        | Required    |
| `related_incident_id`    | UUID        | Required    |
| `relationship_type`      | VARCHAR     | Required    |
| `reason`                 | TEXT        | Optional    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |

Possible relationship types:

* `duplicate`
* `recurrence`
* `predecessor`
* `consequence`
* `shared_dependency`
* `similar`
* `merged_into`

Constraint:

```text
CHECK (incident_id <> related_incident_id)
```

Both Incidents must belong to the same Organization.

---

## Post-Incident Reviews

### `post_incident_reviews`

Stores the current Post-Incident Review state.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `incident_id`             | UUID        | Required    |
| `status`                  | VARCHAR     | Required    |
| `owner_identity_id`       | UUID        | Required    |
| `summary`                 | TEXT        | Optional    |
| `impact_analysis`         | TEXT        | Optional    |
| `response_analysis`       | TEXT        | Optional    |
| `root_cause_analysis`     | TEXT        | Optional    |
| `lessons_learned`         | TEXT        | Optional    |
| `due_at`                  | TIMESTAMPTZ | Optional    |
| `completed_at`            | TIMESTAMPTZ | Optional    |
| `approved_by_identity_id` | UUID        | Optional    |
| `approved_at`             | TIMESTAMPTZ | Optional    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `updated_at`              | TIMESTAMPTZ | Required    |
| `version`                 | BIGINT      | Required    |

Supported statuses:

* `not_required`
* `required`
* `draft`
* `in_review`
* `approved`
* `completed`
* `cancelled`

Constraint:

```text
UNIQUE (organization_id, incident_id)
```

---

## Follow-Up Actions

### `incident_follow_up_actions`

Stores work required after active Incident response.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `incident_id`              | UUID        | Required    |
| `post_incident_review_id`  | UUID        | Optional    |
| `title`                    | VARCHAR     | Required    |
| `description`              | TEXT        | Required    |
| `status`                   | VARCHAR     | Required    |
| `priority`                 | VARCHAR     | Required    |
| `owner_type`               | VARCHAR     | Required    |
| `owner_id`                 | UUID        | Required    |
| `due_at`                   | TIMESTAMPTZ | Optional    |
| `completed_at`             | TIMESTAMPTZ | Optional    |
| `completion_evidence_type` | VARCHAR     | Optional    |
| `completion_evidence_id`   | UUID        | Optional    |
| `created_at`               | TIMESTAMPTZ | Required    |
| `updated_at`               | TIMESTAMPTZ | Required    |
| `version`                  | BIGINT      | Required    |

Supported statuses:

* `open`
* `in_progress`
* `blocked`
* `completed`
* `cancelled`

Follow-Up Actions remain distinct from active Incident Response Actions.

---

## Incident Declaration Transaction

Incident declaration should normally create within one transaction:

1. Incident.
2. initial lifecycle history.
3. initial affected scope.
4. initial Alert links where applicable.
5. initial role assignment.
6. initial timeline entry.
7. Outbox message.

Notifications and external actions occur after commit.

---

## Lifecycle Transition Transaction

A lifecycle transition should normally create within one transaction:

1. Updated Incident state.
2. lifecycle history.
3. timeline entry.
4. related resolution or closure data.
5. Outbox message.

Resolution must include supporting recovery evidence.

---

## Optimistic Concurrency

Mutable Incident records use `version`.

Example:

```sql
UPDATE incidents
SET
    status = :new_status,
    version = version + 1,
    updated_at = :updated_at
WHERE organization_id = :organization_id
  AND id = :incident_id
  AND version = :expected_version;
```

If no row is updated, Sentinel returns a concurrency conflict.

---

## Tenant Isolation

Every Incident-related table must include `organization_id`.

The schema must prevent:

* Incident evidence from another Organization.
* Alert links across Organizations.
* responder assignment across Organizations.
* related Incidents across Organizations.
* Workflow references across Organizations.
* Follow-Up ownership across Organizations.

Organization ownership must be validated before every write.

---

## Required Indexes

Recommended indexes include:

```text
incidents (
    organization_id,
    status,
    severity
)

incidents (
    organization_id,
    application_id,
    status
)

incidents (
    organization_id,
    environment_id,
    status
)

incidents (
    organization_id,
    declared_at
)

incident_roles (
    organization_id,
    incident_id,
    released_at
)

incident_roles (
    organization_id,
    assignee_type,
    assignee_id,
    released_at
)

incident_scope (
    organization_id,
    incident_id,
    impact_status
)

incident_timeline (
    organization_id,
    incident_id,
    occurred_at
)

incident_evidence (
    organization_id,
    incident_id
)

incident_evidence (
    organization_id,
    evidence_type,
    evidence_id
)

incident_hypotheses (
    organization_id,
    incident_id,
    status
)

incident_findings (
    organization_id,
    incident_id,
    status
)

incident_actions (
    organization_id,
    incident_id,
    status
)

incident_actions (
    organization_id,
    owner_type,
    owner_id,
    status
)

incident_alert_links (
    organization_id,
    alert_id,
    unlinked_at
)

incident_follow_up_actions (
    organization_id,
    owner_type,
    owner_id,
    status
)
```

---

## Retention

Incident retention applies to:

* Incident records.
* lifecycle history.
* timeline entries.
* evidence references.
* roles.
* hypotheses.
* Findings.
* Decisions.
* Response Actions.
* status updates.
* Post-Incident Reviews.
* Follow-Up Actions.

Security, compliance, and customer-impact Incidents may require extended retention.

Incident evidence must not be removed while required by retained Incident records or Legal Holds.

---

## Security Requirements

* Incident access must respect Organization and Resource Scope.
* Restricted evidence requires explicit permission.
* Critical lifecycle changes may require approval.
* Response Actions must not bypass authorization.
* Agent Findings remain distinguishable from human Findings.
* external status updates require explicit publication approval.
* secrets must not appear in Incident text fields.
* cross-Organization references must be rejected.

---

## Audit Requirements

Auditable operations include:

* Incident declaration.
* severity changes.
* lifecycle transitions.
* role assignments.
* scope changes.
* Decision creation.
* protected Response Actions.
* mitigation.
* resolution.
* closure.
* cancellation.
* reopening.
* merging.
* Post-Incident Review approval.

Domain timeline and lifecycle history remain separate from general Audit Records.

---

## Business Rules

* Every Incident belongs to exactly one Organization.
* Every Incident has one current status and severity.
* Incident lifecycle history is immutable.
* Timeline entries are immutable.
* Evidence remains owned by its source capability.
* Facts, hypotheses, Findings, and Decisions remain distinct.
* Agent-generated Findings must preserve source identity.
* High and Critical active Incidents require clear ownership.
* Response Actions have their own lifecycle.
* Approval does not replace execution-time authorization.
* Resolution requires recovery evidence.
* Resolution and closure are separate decisions.
* Root cause may remain unknown.
* Merging must preserve both original Incident records.
* Follow-Up Actions remain accountable after Incident resolution.
* Mutable records require optimistic concurrency.
* Cross-Organization relationships are prohibited.
* Missing or conflicting Organization ownership results in rejection.

---

## Success Criteria

The Incident schema is successful when:

* Incident state is easy to query.
* complete lifecycle history can be reconstructed.
* responder ownership remains clear.
* affected scope remains visible.
* evidence supports investigation and resolution.
* hypotheses and confirmed Findings remain distinguishable.
* Decisions and Response Actions remain traceable.
* Alert relationships remain explicit.
* Post-Incident learning can be preserved.
* Follow-Up Actions remain accountable.
* concurrent updates do not overwrite each other.
* tenant isolation is preserved.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/04-application-environment-schema.md`
* `docs/database/05-event-signal-schema.md`
* `docs/database/06-alert-schema.md`
* `docs/database/08-knowledge-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/database/16-indexing-strategy.md`
* `docs/functional/07-incident-management.md`
* `docs/functional/08-knowledge-management.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/04-data-flow.md`
* `docs/adr/ADR-004-operational-processing-model.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
