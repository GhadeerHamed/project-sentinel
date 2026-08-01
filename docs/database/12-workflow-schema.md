# Workflow Schema

## Purpose

This document defines the database schema for Operational Workflows.

It covers:

* Workflow Definitions.
* immutable Workflow Versions.
* Workflow Instances.
* Steps.
* Tasks.
* approvals.
* Actions.
* execution history.
* evidence.
* timeouts.
* retries.
* relationships to Alerts, Incidents, Agents, Plugins, and Notifications.

The Workflow capability owns Workflow execution state.

Other capabilities remain authoritative for their own Resources and Actions.

---

## Design Principles

* Every Organization-specific Workflow belongs to one Organization.
* Workflow Definitions and Workflow Instances remain separate.
* Activated Workflow Versions are immutable.
* Every Instance records the exact Version used.
* Workflow state does not replace Alert or Incident state.
* Steps are executed in a deterministic order defined by the Workflow Version.
* Protected Actions require authorization at execution time.
* Approvals are immutable decisions.
* Agent and Plugin executions remain governed by their owning capabilities.
* Workflow history is append-only.
* Failed processing must be retryable or recoverable.
* Cross-Organization execution is prohibited.
* Mutable records use optimistic concurrency.

---

## Schema Overview

```text id="3hnn0q"
workflow_definitions
└── workflow_versions
    ├── workflow_step_definitions
    └── workflow_transition_definitions

workflow_instances
├── workflow_instance_context
├── workflow_steps
│   ├── workflow_tasks
│   ├── workflow_approvals
│   ├── workflow_actions
│   ├── workflow_step_attempts
│   └── workflow_step_evidence
├── workflow_timers
├── workflow_relationships
└── workflow_history
```

---

# Workflow Definitions

## `workflow_definitions`

Represents the logical identity and lifecycle of a Workflow.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `slug`                   | VARCHAR     | Required    |
| `workflow_type`          | VARCHAR     | Required    |
| `description`            | TEXT        | Optional    |
| `status`                 | VARCHAR     | Required    |
| `active_version_id`      | UUID        | Optional    |
| `owner_type`             | VARCHAR     | Required    |
| `owner_id`               | UUID        | Required    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Supported Workflow Types:

* `alert_response`
* `incident_response`
* `approval`
* `remediation`
* `knowledge_review`
* `plugin_operation`
* `agent_operation`
* `administrative`
* `security_response`
* `custom`

Supported statuses:

* `draft`
* `validated`
* `active`
* `suspended`
* `deprecated`
* `archived`

Constraints:

```text id="lbbfv7"
UNIQUE (organization_id, id)
UNIQUE (organization_id, slug)
```

Only Active Workflows may create normal Instances.

---

# Workflow Versions

## `workflow_versions`

Stores immutable Workflow configuration Versions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `workflow_definition_id` | UUID        | Required    |
| `version_number`         | BIGINT      | Required    |
| `trigger_definition`     | JSONB       | Required    |
| `input_schema`           | JSONB       | Required    |
| `output_schema`          | JSONB       | Required    |
| `execution_policy`       | JSONB       | Required    |
| `failure_policy`         | JSONB       | Required    |
| `timeout_policy`         | JSONB       | Required    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `validated_at`           | TIMESTAMPTZ | Optional    |
| `activated_at`           | TIMESTAMPTZ | Optional    |

Constraints:

```text id="oqcg4f"
UNIQUE (
    organization_id,
    workflow_definition_id,
    version_number
)

UNIQUE (organization_id, id)
```

Activated Workflow Versions are immutable.

---

# Step Definitions

## `workflow_step_definitions`

Defines the Steps in one Workflow Version.

| Column                | Type        | Rules       |
| --------------------- | ----------- | ----------- |
| `id`                  | UUID        | Primary key |
| `organization_id`     | UUID        | Required    |
| `workflow_version_id` | UUID        | Required    |
| `step_key`            | VARCHAR     | Required    |
| `name`                | VARCHAR     | Required    |
| `step_type`           | VARCHAR     | Required    |
| `configuration`       | JSONB       | Required    |
| `input_mapping`       | JSONB       | Required    |
| `output_mapping`      | JSONB       | Required    |
| `required_permission` | VARCHAR     | Optional    |
| `requires_approval`   | BOOLEAN     | Required    |
| `timeout_seconds`     | INTEGER     | Optional    |
| `maximum_attempts`    | INTEGER     | Required    |
| `sequence_order`      | INTEGER     | Required    |
| `created_at`          | TIMESTAMPTZ | Required    |

Supported Step Types:

* `task`
* `approval`
* `decision`
* `domain_action`
* `agent_execution`
* `plugin_execution`
* `notification`
* `wait`
* `condition`
* `parallel_group`
* `manual`
* `completion`

Constraints:

```text id="t46ryp"
UNIQUE (
    organization_id,
    workflow_version_id,
    step_key
)
```

---

# Transition Definitions

## `workflow_transition_definitions`

Defines allowed movement between Workflow Steps.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `workflow_version_id`  | UUID        | Required    |
| `from_step_key`        | VARCHAR     | Required    |
| `to_step_key`          | VARCHAR     | Required    |
| `transition_type`      | VARCHAR     | Required    |
| `condition_definition` | JSONB       | Optional    |
| `priority`             | INTEGER     | Required    |
| `created_at`           | TIMESTAMPTZ | Required    |

Supported transition types:

* `success`
* `failure`
* `approved`
* `rejected`
* `timeout`
* `cancelled`
* `condition`
* `manual`

Transition definitions are immutable with their Workflow Version.

---

# Workflow Instances

## `workflow_instances`

Represents one execution of a Workflow Version.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `workflow_definition_id` | UUID        | Required    |
| `workflow_version_id`    | UUID        | Required    |
| `workflow_type`          | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `trigger_type`           | VARCHAR     | Required    |
| `trigger_resource_type`  | VARCHAR     | Optional    |
| `trigger_resource_id`    | UUID        | Optional    |
| `initiated_by_type`      | VARCHAR     | Required    |
| `initiated_by_id`        | UUID        | Optional    |
| `current_step_key`       | VARCHAR     | Optional    |
| `idempotency_key`        | VARCHAR     | Optional    |
| `started_at`             | TIMESTAMPTZ | Optional    |
| `completed_at`           | TIMESTAMPTZ | Optional    |
| `cancelled_at`           | TIMESTAMPTZ | Optional    |
| `failed_at`              | TIMESTAMPTZ | Optional    |
| `expires_at`             | TIMESTAMPTZ | Optional    |
| `failure_code`           | VARCHAR     | Optional    |
| `failure_message`        | TEXT        | Optional    |
| `correlation_id`         | UUID        | Optional    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Supported statuses:

* `created`
* `running`
* `waiting`
* `blocked`
* `completed`
* `failed`
* `cancelled`
* `timed_out`
* `suspended`

Constraints:

```text id="83eq2v"
UNIQUE (organization_id, id)
```

Optional idempotency constraint:

```text id="o90dcg"
UNIQUE (
    organization_id,
    workflow_definition_id,
    idempotency_key
)
WHERE idempotency_key IS NOT NULL
```

---

# Workflow Context

## `workflow_instance_context`

Stores references to Resources used by a Workflow Instance.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `workflow_instance_id` | UUID        | Required    |
| `context_type`         | VARCHAR     | Required    |
| `resource_type`        | VARCHAR     | Required    |
| `resource_id`          | UUID        | Required    |
| `relationship_type`    | VARCHAR     | Required    |
| `source_version`       | VARCHAR     | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |

Possible Context Types:

* `trigger`
* `input`
* `evidence`
* `target`
* `related`
* `output`

Workflow context should reference authoritative Resources rather than copy unrestricted payloads.

---

# Workflow Steps

## `workflow_steps`

Stores the runtime state of one Step.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `workflow_instance_id` | UUID        | Required    |
| `step_definition_id`   | UUID        | Required    |
| `step_key`             | VARCHAR     | Required    |
| `step_type`            | VARCHAR     | Required    |
| `status`               | VARCHAR     | Required    |
| `attempt_count`        | INTEGER     | Required    |
| `input_data`           | JSONB       | Required    |
| `output_data`          | JSONB       | Optional    |
| `started_at`           | TIMESTAMPTZ | Optional    |
| `completed_at`         | TIMESTAMPTZ | Optional    |
| `failed_at`            | TIMESTAMPTZ | Optional    |
| `due_at`               | TIMESTAMPTZ | Optional    |
| `failure_code`         | VARCHAR     | Optional    |
| `failure_message`      | TEXT        | Optional    |
| `version`              | BIGINT      | Required    |

Supported statuses:

* `pending`
* `ready`
* `running`
* `waiting`
* `blocked`
* `completed`
* `failed`
* `rejected`
* `cancelled`
* `skipped`
* `timed_out`

Constraints:

```text id="2pbzv0"
UNIQUE (
    organization_id,
    workflow_instance_id,
    step_key
)
```

---

# Workflow Tasks

## `workflow_tasks`

Represents manual work assigned to a Member or Team.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `workflow_instance_id` | UUID        | Required    |
| `workflow_step_id`     | UUID        | Required    |
| `title`                | VARCHAR     | Required    |
| `description`          | TEXT        | Required    |
| `status`               | VARCHAR     | Required    |
| `priority`             | VARCHAR     | Required    |
| `assignee_type`        | VARCHAR     | Required    |
| `assignee_id`          | UUID        | Required    |
| `created_at`           | TIMESTAMPTZ | Required    |
| `started_at`           | TIMESTAMPTZ | Optional    |
| `completed_at`         | TIMESTAMPTZ | Optional    |
| `due_at`               | TIMESTAMPTZ | Optional    |
| `completion_note`      | TEXT        | Optional    |
| `version`              | BIGINT      | Required    |

Supported statuses:

* `open`
* `in_progress`
* `blocked`
* `completed`
* `rejected`
* `cancelled`
* `expired`

Supported assignee types:

* `membership`
* `team`

Task completion does not automatically prove that a protected Action succeeded.

---

# Workflow Approvals

## `workflow_approvals`

Stores approval requests and immutable decisions.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `workflow_instance_id` | UUID        | Required    |
| `workflow_step_id`     | UUID        | Required    |
| `approval_type`        | VARCHAR     | Required    |
| `status`               | VARCHAR     | Required    |
| `requested_by_type`    | VARCHAR     | Required    |
| `requested_by_id`      | UUID        | Optional    |
| `approver_type`        | VARCHAR     | Required    |
| `approver_id`          | UUID        | Required    |
| `request_reason`       | TEXT        | Required    |
| `decision`             | VARCHAR     | Optional    |
| `decision_reason`      | TEXT        | Optional    |
| `requested_at`         | TIMESTAMPTZ | Required    |
| `decided_at`           | TIMESTAMPTZ | Optional    |
| `expires_at`           | TIMESTAMPTZ | Optional    |

Supported statuses:

* `requested`
* `approved`
* `rejected`
* `expired`
* `cancelled`
* `revoked`

Supported decisions:

* `approved`
* `rejected`

An approval decision must not be modified after completion.

A new decision requires a new approval record.

---

# Workflow Actions

## `workflow_actions`

Stores a requested domain or external Action.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `workflow_instance_id`     | UUID        | Required    |
| `workflow_step_id`         | UUID        | Required    |
| `action_type`              | VARCHAR     | Required    |
| `owning_capability`        | VARCHAR     | Required    |
| `target_resource_type`     | VARCHAR     | Required    |
| `target_resource_id`       | UUID        | Required    |
| `status`                   | VARCHAR     | Required    |
| `required_permission`      | VARCHAR     | Required    |
| `approval_id`              | UUID        | Optional    |
| `idempotency_key`          | VARCHAR     | Required    |
| `execution_reference_type` | VARCHAR     | Optional    |
| `execution_reference_id`   | UUID        | Optional    |
| `requested_at`             | TIMESTAMPTZ | Required    |
| `authorized_at`            | TIMESTAMPTZ | Optional    |
| `executed_at`              | TIMESTAMPTZ | Optional    |
| `verified_at`              | TIMESTAMPTZ | Optional    |
| `failure_code`             | VARCHAR     | Optional    |
| `failure_message`          | TEXT        | Optional    |
| `version`                  | BIGINT      | Required    |

Supported statuses:

* `proposed`
* `awaiting_approval`
* `authorized`
* `executing`
* `executed`
* `verified`
* `rejected`
* `failed`
* `cancelled`
* `expired`

The owning capability performs the Action.

The Workflow capability records coordination state.

---

# Step Attempts

## `workflow_step_attempts`

Stores each execution attempt for a Workflow Step.

| Column             | Type        | Rules       |
| ------------------ | ----------- | ----------- |
| `id`               | UUID        | Primary key |
| `organization_id`  | UUID        | Required    |
| `workflow_step_id` | UUID        | Required    |
| `attempt_number`   | INTEGER     | Required    |
| `status`           | VARCHAR     | Required    |
| `started_at`       | TIMESTAMPTZ | Required    |
| `completed_at`     | TIMESTAMPTZ | Optional    |
| `failure_code`     | VARCHAR     | Optional    |
| `failure_message`  | TEXT        | Optional    |
| `retryable`        | BOOLEAN     | Optional    |
| `next_retry_at`    | TIMESTAMPTZ | Optional    |

Constraints:

```text id="is3u1w"
UNIQUE (
    organization_id,
    workflow_step_id,
    attempt_number
)
```

Attempts are append-only.

---

# Step Evidence

## `workflow_step_evidence`

Connects a Step to its input or output evidence.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `workflow_step_id`  | UUID        | Required    |
| `evidence_type`     | VARCHAR     | Required    |
| `evidence_id`       | UUID        | Required    |
| `relationship_type` | VARCHAR     | Required    |
| `created_at`        | TIMESTAMPTZ | Required    |

Possible relationships:

* `input`
* `supports`
* `result`
* `verification`
* `failure`
* `rollback`

Evidence remains owned by its source capability.

---

# Workflow Timers

## `workflow_timers`

Stores waiting, timeout, and scheduled-resume state.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `workflow_instance_id` | UUID        | Required    |
| `workflow_step_id`     | UUID        | Optional    |
| `timer_type`           | VARCHAR     | Required    |
| `status`               | VARCHAR     | Required    |
| `scheduled_for`        | TIMESTAMPTZ | Required    |
| `triggered_at`         | TIMESTAMPTZ | Optional    |
| `cancelled_at`         | TIMESTAMPTZ | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |

Supported Timer Types:

* `delay`
* `timeout`
* `retry`
* `approval_expiration`
* `scheduled_resume`
* `deadline`

Supported statuses:

* `scheduled`
* `triggered`
* `cancelled`
* `expired`

---

# Workflow Relationships

## `workflow_relationships`

Connects a Workflow Instance to relevant Resources or other Workflows.

| Column                  | Type        | Rules       |
| ----------------------- | ----------- | ----------- |
| `id`                    | UUID        | Primary key |
| `organization_id`       | UUID        | Required    |
| `workflow_instance_id`  | UUID        | Required    |
| `related_resource_type` | VARCHAR     | Required    |
| `related_resource_id`   | UUID        | Required    |
| `relationship_type`     | VARCHAR     | Required    |
| `created_at`            | TIMESTAMPTZ | Required    |
| `removed_at`            | TIMESTAMPTZ | Optional    |

Possible relationship types:

* `triggered_by`
* `coordinates`
* `child_workflow`
* `parent_workflow`
* `supports`
* `remediates`
* `reviews`
* `follows_up`

Relationships do not transfer ownership of the related Resource.

---

# Workflow History

## `workflow_history`

Stores immutable Workflow and Step state changes.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `workflow_instance_id` | UUID        | Required    |
| `workflow_step_id`     | UUID        | Optional    |
| `change_type`          | VARCHAR     | Required    |
| `previous_status`      | VARCHAR     | Optional    |
| `new_status`           | VARCHAR     | Required    |
| `reason`               | TEXT        | Optional    |
| `changed_by_type`      | VARCHAR     | Required    |
| `changed_by_id`        | UUID        | Optional    |
| `changed_at`           | TIMESTAMPTZ | Required    |
| `correlation_id`       | UUID        | Optional    |

History is append-only.

---

# Workflow Activation Transaction

Activating a Workflow Version should update within one transaction:

1. Workflow Definition.
2. `active_version_id`.
3. lifecycle history.
4. Outbox message.

Only a validated Version may become Active.

---

# Workflow Instance Creation

Creating an Instance should normally persist:

1. Workflow Instance.
2. initial context.
3. initial Step state.
4. initial Workflow history.
5. idempotency record where required.
6. Outbox message.

External Actions must not execute inside this transaction.

---

# Step Completion

Completing a Step should normally persist:

1. Updated Step state.
2. output data.
3. evidence references.
4. Step attempt result.
5. next Step state.
6. updated Workflow Instance state.
7. Workflow history.
8. Outbox message.

The transition must be valid under the exact Workflow Version used by the Instance.

---

# Protected Actions

A protected Workflow Action requires:

1. Valid Organization context.
2. permitted Workflow configuration.
3. current approval where required.
4. current acting identity.
5. execution-time authorization.
6. target Resource Version where required.
7. idempotency protection.
8. execution through the owning capability.
9. recorded outcome.
10. verification when required.

Approval alone does not guarantee execution permission.

---

# Agent and Plugin Steps

Agent and Plugin Steps should create references to:

```text id="bllekf"
agent_execution_id
plugin_execution_id
```

The Agent and Plugin capabilities own those execution records.

The Workflow Step records:

* Coordination status.
* input references.
* output references.
* success or failure.
* resulting transition.

---

# Notification Steps

Notification Steps reference a Notification created by the Notification Platform.

The Workflow must not treat successful Notification creation as confirmed delivery.

Where delivery is required, the Step waits for the required delivery or acknowledgement event.

---

# Retry Rules

Retries must consider:

* Maximum attempt count.
* Step Type.
* idempotency.
* failure classification.
* Workflow expiration.
* target Resource state.
* approval validity.
* Policy restrictions.

Retries must not repeat a completed non-idempotent Action.

---

# Cancellation

Cancelling a Workflow Instance should:

* Stop pending Steps.
* cancel eligible Timers.
* cancel unexecuted Actions.
* preserve completed Step history.
* preserve approval decisions.
* preserve evidence.
* publish a cancellation event.

Cancellation does not automatically undo completed domain Actions.

Compensation or rollback requires an explicit governed Action.

---

# Optimistic Concurrency

Workflow Instances, Steps, Tasks, and Actions use `version`.

Example:

```sql id="d3epcf"
UPDATE workflow_steps
SET
    status = :new_status,
    version = version + 1
WHERE organization_id = :organization_id
  AND id = :workflow_step_id
  AND version = :expected_version;
```

A failed Version match produces a concurrency conflict.

History, approvals, and attempts remain immutable.

---

# Tenant Isolation

Every Workflow execution table includes `organization_id`.

The schema must prevent:

* Cross-Organization Workflow context.
* task assignment to another Organization’s Member or Team.
* approvals by another Organization’s identity.
* Actions against another Organization’s Resource.
* Agent or Plugin executions across Organizations.
* Notification references across Organizations.
* parent-child Workflows across Organizations.

Organization ownership must be validated before every write.

---

# Required Indexes

Recommended indexes include:

```text id="j6lc3r"
workflow_definitions (
    organization_id,
    status,
    workflow_type
)

workflow_versions (
    organization_id,
    workflow_definition_id,
    version_number
)

workflow_instances (
    organization_id,
    status,
    created_at
)

workflow_instances (
    organization_id,
    workflow_definition_id,
    status
)

workflow_instances (
    organization_id,
    trigger_resource_type,
    trigger_resource_id
)

workflow_steps (
    organization_id,
    workflow_instance_id,
    status
)

workflow_tasks (
    organization_id,
    assignee_type,
    assignee_id,
    status
)

workflow_approvals (
    organization_id,
    approver_type,
    approver_id,
    status
)

workflow_actions (
    organization_id,
    status,
    requested_at
)

workflow_actions (
    organization_id,
    target_resource_type,
    target_resource_id
)

workflow_step_attempts (
    organization_id,
    workflow_step_id,
    attempt_number
)

workflow_timers (
    organization_id,
    status,
    scheduled_for
)

workflow_history (
    organization_id,
    workflow_instance_id,
    changed_at
)
```

---

# Retention

Workflow retention applies to:

* Definitions.
* Versions.
* Instances.
* Steps.
* Tasks.
* approvals.
* Actions.
* attempts.
* Timers.
* evidence.
* relationships.
* history.

Workflow records referenced by Incidents, security investigations, Audit Records, or Legal Holds must be retained accordingly.

Temporary Step payloads may use shorter retention when they do not contain authoritative evidence.

---

# Security Requirements

* Workflow input must be validated.
* Protected Actions require execution-time authorization.
* approval and execution identities must remain distinct where separation of duties applies.
* Agent and Plugin outputs must be treated as untrusted input.
* sensitive Step data must be minimized.
* credentials must not appear in Workflow payloads.
* external Actions must use secret references.
* cross-Organization execution must be rejected.
* unsafe Workflows must be suspendable immediately.

---

# Audit Requirements

Auditable operations include:

* Workflow creation.
* Version activation.
* suspension.
* Instance start.
* privileged task assignment.
* approval decisions.
* protected Actions.
* cancellation.
* manual retry.
* forced Step completion.
* timeout override.
* compensation or rollback.
* sensitive context access.

Workflow history remains separate from general Audit Records.

---

# Business Rules

* Every Organization-specific Workflow belongs to exactly one Organization.
* Every Instance records the exact Workflow Version used.
* Activated Workflow Versions are immutable.
* Workflow state does not replace domain Resource state.
* Steps may transition only through declared transitions.
* Every protected Action is executed by its owning capability.
* Approval does not replace execution-time authorization.
* Approvals are immutable.
* Completed non-idempotent Actions must not be repeated.
* Workflow history is append-only.
* Cancellation does not automatically reverse completed Actions.
* Agent and Plugin Steps preserve their execution references.
* Notification creation does not prove delivery.
* Evidence must belong to the same Organization.
* Cross-Organization execution is prohibited.
* Mutable Workflow records require optimistic concurrency.
* Missing or conflicting Organization ownership results in rejection.

---

# Success Criteria

The Workflow schema is successful when:

* Workflow Definitions and Instances remain clearly separated.
* every Instance is reproducible from its exact Version.
* Step progress can be queried reliably.
* manual Tasks remain accountable.
* approvals remain traceable.
* protected Actions cannot bypass authorization.
* Agent, Plugin, and Notification Steps remain governed.
* retries remain safe.
* failures remain recoverable.
* cancellation preserves historical truth.
* domain ownership remains intact.
* tenant isolation is preserved.

---

# Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/06-alert-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/08-knowledge-schema.md`
* `docs/database/09-agent-schema.md`
* `docs/database/10-plugin-schema.md`
* `docs/database/11-notification-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/15-retention-archival.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/07-agent-architecture.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-006-agent-platform.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-008-security-policy-layer.md`
