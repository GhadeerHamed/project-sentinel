# Knowledge Schema

## Purpose

This document defines the database schema for Knowledge Management.

It covers:

* Knowledge Entries.
* immutable Knowledge Versions.
* ownership.
* applicability.
* source evidence.
* review and approval.
* relationships.
* feedback.
* review schedules.
* lifecycle history.

Knowledge Management owns the authoritative Knowledge state.

---

## Design Principles

* Every Knowledge Entry belongs to one Organization.
* Every material content change creates a new immutable Version.
* Published Knowledge must reference an approved Version.
* Source evidence remains owned by its original capability.
* Applicability and confidence must remain explicit.
* Agent-generated content begins as Draft.
* Review and approval decisions remain traceable.
* Deprecated Knowledge remains historically available.
* Cross-Organization relationships are prohibited.
* Mutable Knowledge Entries use optimistic concurrency.

---

## Schema Overview

```text
knowledge_entries
├── knowledge_versions
├── knowledge_owners
├── knowledge_applicability
├── knowledge_evidence
├── knowledge_reviews
├── knowledge_approvals
├── knowledge_relationships
├── knowledge_feedback
├── knowledge_usage
├── knowledge_review_schedules
└── knowledge_lifecycle_history
```

---

## Knowledge Entries

### `knowledge_entries`

Represents the logical identity and current lifecycle state of a Knowledge item.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `knowledge_type`       | VARCHAR     | Required    |
| `title`                | VARCHAR     | Required    |
| `slug`                 | VARCHAR     | Required    |
| `status`               | VARCHAR     | Required    |
| `confidence`           | VARCHAR     | Required    |
| `current_version_id`   | UUID        | Optional    |
| `published_version_id` | UUID        | Optional    |
| `created_by_type`      | VARCHAR     | Required    |
| `created_by_id`        | UUID        | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |
| `updated_at`           | TIMESTAMPTZ | Required    |
| `published_at`         | TIMESTAMPTZ | Optional    |
| `deprecated_at`        | TIMESTAMPTZ | Optional    |
| `archived_at`          | TIMESTAMPTZ | Optional    |
| `version`              | BIGINT      | Required    |

Supported Knowledge Types:

* `runbook`
* `troubleshooting_guide`
* `known_error`
* `remediation_guide`
* `recovery_procedure`
* `prevention_guidance`
* `operational_decision`
* `incident_learning`
* `integration_guide`
* `reference`

Supported statuses:

* `draft`
* `in_review`
* `approved`
* `published`
* `deprecated`
* `archived`
* `rejected`
* `withdrawn`

Supported confidence values:

* `high`
* `medium`
* `low`
* `unknown`

Constraints:

```text
UNIQUE (organization_id, id)
UNIQUE (organization_id, slug)
```

---

## Knowledge Versions

### `knowledge_versions`

Stores immutable content Versions.

| Column               | Type        | Rules       |
| -------------------- | ----------- | ----------- |
| `id`                 | UUID        | Primary key |
| `organization_id`    | UUID        | Required    |
| `knowledge_entry_id` | UUID        | Required    |
| `version_number`     | BIGINT      | Required    |
| `title`              | VARCHAR     | Required    |
| `summary`            | TEXT        | Optional    |
| `content`            | TEXT        | Required    |
| `content_format`     | VARCHAR     | Required    |
| `confidence`         | VARCHAR     | Required    |
| `assumptions`        | JSONB       | Required    |
| `risks`              | JSONB       | Required    |
| `limitations`        | JSONB       | Required    |
| `change_summary`     | TEXT        | Required    |
| `created_by_type`    | VARCHAR     | Required    |
| `created_by_id`      | UUID        | Optional    |
| `created_at`         | TIMESTAMPTZ | Required    |

Supported content formats may include:

* `markdown`
* `structured_json`

Constraints:

```text
UNIQUE (
    organization_id,
    knowledge_entry_id,
    version_number
)

UNIQUE (
    organization_id,
    id
)
```

Knowledge Versions must not be modified after creation.

---

## Knowledge Ownership

### `knowledge_owners`

Assigns responsibility for maintaining a Knowledge Entry.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `knowledge_entry_id`      | UUID        | Required    |
| `owner_type`              | VARCHAR     | Required    |
| `owner_id`                | UUID        | Required    |
| `ownership_role`          | VARCHAR     | Required    |
| `assigned_by_identity_id` | UUID        | Required    |
| `assigned_at`             | TIMESTAMPTZ | Required    |
| `removed_at`              | TIMESTAMPTZ | Optional    |

Supported owner types:

* `membership`
* `team`

Supported ownership roles:

* `primary`
* `technical`
* `review`
* `security`

Every active Published Knowledge Entry should have an active owner.

---

## Applicability

### `knowledge_applicability`

Defines where a Knowledge Version may be used.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `knowledge_version_id` | UUID        | Required    |
| `scope_type`           | VARCHAR     | Required    |
| `scope_id`             | UUID        | Optional    |
| `condition_definition` | JSONB       | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |

Supported scope types:

* `organization`
* `project`
* `application`
* `environment`
* `service`
* `plugin`
* `provider`
* `technology`
* `incident_type`

Organization-wide applicability may use a null `scope_id`.

Every Published Version must define at least one applicability record.

---

## Source Evidence

### `knowledge_evidence`

Connects a Knowledge Version to supporting evidence.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `knowledge_version_id` | UUID        | Required    |
| `evidence_type`        | VARCHAR     | Required    |
| `evidence_id`          | UUID        | Required    |
| `relationship_type`    | VARCHAR     | Required    |
| `added_by_type`        | VARCHAR     | Required    |
| `added_by_id`          | UUID        | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |

Possible evidence types:

* `incident`
* `alert`
* `event`
* `signal`
* `finding`
* `response_action`
* `post_incident_review`
* `deployment`
* `test_result`
* `plugin_record`
* `external_document`

Possible relationships:

* `derived_from`
* `supports`
* `validates`
* `contradicts`
* `supersedes`

Evidence must belong to the same Organization unless it is an approved platform-owned source.

---

## Reviews

### `knowledge_reviews`

Stores review activity for a specific Knowledge Version.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `knowledge_entry_id`   | UUID        | Required    |
| `knowledge_version_id` | UUID        | Required    |
| `reviewer_identity_id` | UUID        | Required    |
| `review_type`          | VARCHAR     | Required    |
| `status`               | VARCHAR     | Required    |
| `comment`              | TEXT        | Optional    |
| `requested_at`         | TIMESTAMPTZ | Required    |
| `completed_at`         | TIMESTAMPTZ | Optional    |

Supported review types:

* `technical`
* `operational`
* `security`
* `compliance`
* `editorial`

Supported statuses:

* `requested`
* `in_progress`
* `approved`
* `changes_requested`
* `rejected`
* `cancelled`

A review applies only to the referenced Version.

---

## Approvals

### `knowledge_approvals`

Records formal approval decisions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `knowledge_entry_id`     | UUID        | Required    |
| `knowledge_version_id`   | UUID        | Required    |
| `decision`               | VARCHAR     | Required    |
| `approved_scope`         | JSONB       | Optional    |
| `conditions`             | JSONB       | Optional    |
| `decided_by_identity_id` | UUID        | Required    |
| `reason`                 | TEXT        | Optional    |
| `decided_at`             | TIMESTAMPTZ | Required    |

Supported decisions:

* `approved`
* `rejected`
* `revoked`

Approval decisions are immutable.

A material Version change requires new review and approval.

---

## Knowledge Relationships

### `knowledge_relationships`

Connects related Knowledge Entries or Versions.

| Column                       | Type        | Rules       |
| ---------------------------- | ----------- | ----------- |
| `id`                         | UUID        | Primary key |
| `organization_id`            | UUID        | Required    |
| `knowledge_entry_id`         | UUID        | Required    |
| `related_knowledge_entry_id` | UUID        | Required    |
| `relationship_type`          | VARCHAR     | Required    |
| `reason`                     | TEXT        | Optional    |
| `created_by_type`            | VARCHAR     | Required    |
| `created_by_id`              | UUID        | Optional    |
| `created_at`                 | TIMESTAMPTZ | Required    |
| `removed_at`                 | TIMESTAMPTZ | Optional    |

Supported relationship types:

* `related`
* `prerequisite`
* `replacement`
* `conflicts_with`
* `more_specific`
* `more_general`
* `derived_from`
* `superseded_by`

Constraint:

```text
CHECK (
    knowledge_entry_id <> related_knowledge_entry_id
)
```

Both entries must belong to the same Organization.

---

## Feedback

### `knowledge_feedback`

Stores feedback about one Knowledge Version.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `knowledge_entry_id`      | UUID        | Required    |
| `knowledge_version_id`    | UUID        | Required    |
| `feedback_type`           | VARCHAR     | Required    |
| `comment`                 | TEXT        | Optional    |
| `submitted_by_type`       | VARCHAR     | Required    |
| `submitted_by_id`         | UUID        | Optional    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `resolved_at`             | TIMESTAMPTZ | Optional    |
| `resolved_by_identity_id` | UUID        | Optional    |

Supported feedback types:

* `helpful`
* `not_helpful`
* `successful`
* `failed`
* `unsafe`
* `incorrect`
* `outdated`
* `missing_step`
* `scope_issue`
* `suggested_change`

Feedback does not modify Published content directly.

---

## Knowledge Usage

### `knowledge_usage`

Records where a Knowledge Version was used.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `knowledge_entry_id`   | UUID        | Required    |
| `knowledge_version_id` | UUID        | Required    |
| `usage_type`           | VARCHAR     | Required    |
| `resource_type`        | VARCHAR     | Optional    |
| `resource_id`          | UUID        | Optional    |
| `used_by_type`         | VARCHAR     | Required    |
| `used_by_id`           | UUID        | Optional    |
| `outcome`              | VARCHAR     | Optional    |
| `used_at`              | TIMESTAMPTZ | Required    |

Possible usage types:

* `alert_investigation`
* `incident_response`
* `agent_retrieval`
* `plugin_workflow`
* `manual_troubleshooting`
* `response_action`
* `post_incident_review`

Possible outcomes:

* `successful`
* `partially_successful`
* `failed`
* `unknown`

Usage alone does not prove effectiveness.

---

## Review Schedules

### `knowledge_review_schedules`

Stores the next required review date.

| Column                   | Type        | Rules    |
| ------------------------ | ----------- | -------- |
| `organization_id`        | UUID        | Required |
| `knowledge_entry_id`     | UUID        | Required |
| `review_interval_days`   | INTEGER     | Optional |
| `next_review_at`         | TIMESTAMPTZ | Required |
| `status`                 | VARCHAR     | Required |
| `assigned_reviewer_type` | VARCHAR     | Optional |
| `assigned_reviewer_id`   | UUID        | Optional |
| `updated_at`             | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (
    organization_id,
    knowledge_entry_id
)
```

Supported statuses:

* `current`
* `due`
* `overdue`
* `under_review`
* `exempt`

---

## Lifecycle History

### `knowledge_lifecycle_history`

Stores immutable lifecycle changes.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `knowledge_entry_id`   | UUID        | Required    |
| `knowledge_version_id` | UUID        | Optional    |
| `previous_status`      | VARCHAR     | Optional    |
| `new_status`           | VARCHAR     | Required    |
| `reason`               | TEXT        | Required    |
| `changed_by_type`      | VARCHAR     | Required    |
| `changed_by_id`        | UUID        | Optional    |
| `changed_at`           | TIMESTAMPTZ | Required    |
| `correlation_id`       | UUID        | Optional    |

Lifecycle history is append-only.

---

## Knowledge Lifecycle

```text
Draft

↓

In Review

↓

Approved

↓

Published

↓

Deprecated

↓

Archived
```

Alternative terminal states include:

* `rejected`
* `withdrawn`

Only an approved Version may become Published.

---

## Creation Transaction

Creating a Knowledge Entry should normally create:

1. Knowledge Entry.
2. first Knowledge Version.
3. initial owner.
4. applicability records where known.
5. evidence references where available.
6. lifecycle history.
7. Outbox message.

---

## Publication Transaction

Publication should normally create or update:

1. Knowledge Entry status.
2. `published_version_id`.
3. publication timestamp.
4. lifecycle history.
5. review schedule.
6. Outbox message.

The Version being Published must already have the required approvals.

---

## Optimistic Concurrency

Knowledge Entries use the `version` column.

Example:

```sql
UPDATE knowledge_entries
SET
    status = :new_status,
    current_version_id = :current_version_id,
    version = version + 1,
    updated_at = :updated_at
WHERE organization_id = :organization_id
  AND id = :knowledge_entry_id
  AND version = :expected_version;
```

A failed Version match produces a concurrency conflict.

Knowledge Versions remain immutable and do not use normal update concurrency.

---

## Agent-Generated Knowledge

Agent-generated Knowledge must record:

```text
created_by_type = agent
created_by_id = agent_execution_id
```

Agent-generated content:

* Begins as Draft.
* must preserve supporting evidence.
* must not become Published automatically.
* requires the same review and approval process as other Knowledge.

---

## Deprecation

Deprecating Knowledge should record:

* Reason.
* deprecated Version.
* replacement Knowledge where available.
* deprecation time.
* acting identity.
* lifecycle history.

Deprecated Knowledge remains historically available but should not be recommended as current guidance.

---

## Tenant Isolation

Every Knowledge-related table must include `organization_id`.

The schema must prevent:

* Evidence from another Organization.
* ownership by another Organization’s Member or Team.
* review by an unauthorized identity.
* cross-Organization Knowledge relationships.
* usage references across Organizations.
* publishing another Organization’s Version.

Platform-owned public Knowledge must use a separate governed ownership model.

---

## Required Indexes

Recommended indexes include:

```text
knowledge_entries (
    organization_id,
    status,
    knowledge_type
)

knowledge_entries (
    organization_id,
    confidence,
    status
)

knowledge_entries (
    organization_id,
    updated_at
)

knowledge_versions (
    organization_id,
    knowledge_entry_id,
    version_number
)

knowledge_owners (
    organization_id,
    knowledge_entry_id,
    removed_at
)

knowledge_applicability (
    organization_id,
    scope_type,
    scope_id
)

knowledge_evidence (
    organization_id,
    knowledge_version_id
)

knowledge_evidence (
    organization_id,
    evidence_type,
    evidence_id
)

knowledge_reviews (
    organization_id,
    reviewer_identity_id,
    status
)

knowledge_feedback (
    organization_id,
    knowledge_entry_id,
    feedback_type
)

knowledge_usage (
    organization_id,
    knowledge_version_id,
    used_at
)

knowledge_review_schedules (
    organization_id,
    status,
    next_review_at
)

knowledge_relationships (
    organization_id,
    knowledge_entry_id,
    relationship_type
)
```

---

## Retention

Knowledge retention applies to:

* Knowledge Entries.
* all Versions.
* evidence references.
* reviews.
* approvals.
* relationships.
* feedback.
* usage records.
* lifecycle history.

Published and Deprecated Versions should remain available while referenced by retained Incidents, Actions, Workflows, or Audit Records.

Temporary Drafts may use shorter retention only when they have no operational or approval history.

---

## Security Requirements

* Sensitive Knowledge requires explicit authorization.
* Restricted evidence must not be exposed through Knowledge content.
* secrets must not appear in Knowledge content.
* external sharing requires separate approval.
* Agent-generated content must remain identifiable.
* approval and publication require authorized identities.
* cross-Organization references must be rejected.

---

## Audit Requirements

Auditable operations include:

* Knowledge creation.
* Version creation.
* review assignment.
* approval.
* publication.
* confidence changes.
* ownership changes.
* deprecation.
* archival.
* sensitive access.
* external sharing.

Domain lifecycle history remains separate from general Audit Records.

---

## Business Rules

* Every Knowledge Entry belongs to exactly one Organization.
* Every material change creates a new immutable Version.
* Historical Versions must not be overwritten.
* Only approved Versions may become Published.
* Every Published Version defines applicability.
* Confidence remains explicit.
* Source evidence remains traceable.
* Agent-generated content begins as Draft.
* Feedback does not directly modify Published content.
* Deprecated Knowledge must not be recommended as current guidance.
* Archived Knowledge must not appear as active guidance.
* Cross-Organization Knowledge relationships are prohibited.
* Mutable Entry updates require optimistic concurrency.
* Missing or conflicting Organization ownership results in rejection.

---

## Success Criteria

The Knowledge schema is successful when:

* Operational learning remains versioned.
* Published guidance is reviewed and approved.
* applicability and confidence remain clear.
* every Version remains traceable to evidence.
* Agent-generated content cannot bypass governance.
* ownership and review responsibility remain visible.
* stale and conflicting Knowledge can be identified.
* historical Knowledge remains available for Incident reconstruction.
* tenant isolation is preserved.
* current Published Knowledge can be queried efficiently.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/07-incident-schema.md`
* `docs/database/09-agent-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/functional/08-knowledge-management.md`
* `docs/functional/09-agent-platform.md`
* `docs/functional/17-operational-workflows.md`
* `docs/adr/ADR-006-agent-platform.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
