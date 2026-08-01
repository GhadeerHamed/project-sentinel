# Agent Schema

## Purpose

This document defines the database schema for the Agent Platform.

It covers:

* Agent Definitions.
* immutable Agent Versions.
* Agent Executions.
* execution context.
* Tool calls.
* Findings.
* Recommendations.
* provider usage.
* feedback.
* execution history.

The Agent Platform owns Agent configuration and execution records.

Other capabilities remain authoritative for their own Resources.

---

## Design Principles

* Every Organization-specific Agent belongs to one Organization.
* Every active Agent executes through a distinct Agent Identity.
* Agent Versions are immutable.
* Historical Executions retain the exact Agent Version, provider, and model used.
* Agent context contains references to authorized data.
* Agent-generated content is not source evidence.
* Findings and Recommendations remain separate from domain state.
* Protected Actions require approval and execution through the owning capability.
* Sensitive provider payloads are minimized.
* Cross-Organization context is prohibited.
* Mutable records use optimistic concurrency.

---

## Schema Overview

```text
agent_definitions
├── agent_versions
├── agent_tools
├── agent_execution_policies
└── agent_executions
    ├── agent_execution_context
    ├── agent_tool_calls
    ├── agent_findings
    │   └── agent_finding_evidence
    ├── agent_recommendations
    │   └── agent_recommendation_evidence
    ├── agent_execution_usage
    └── agent_feedback
```

Agent Identity records are defined in:

```text
docs/database/02-identity-access-schema.md
```

---

## Agent Definitions

### `agent_definitions`

Represents the logical identity and lifecycle of an Agent.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `slug`                   | VARCHAR     | Required    |
| `agent_type`             | VARCHAR     | Required    |
| `description`            | TEXT        | Optional    |
| `status`                 | VARCHAR     | Required    |
| `owner_type`             | VARCHAR     | Required    |
| `owner_id`               | UUID        | Required    |
| `active_version_id`      | UUID        | Optional    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `suspended_at`           | TIMESTAMPTZ | Optional    |
| `deprecated_at`          | TIMESTAMPTZ | Optional    |
| `archived_at`            | TIMESTAMPTZ | Optional    |
| `version`                | BIGINT      | Required    |

Supported Agent Types:

* `triage`
* `investigation`
* `correlation`
* `knowledge`
* `incident_assistant`
* `remediation_advisor`
* `review`
* `administrative`
* `workflow`

Supported statuses:

* `draft`
* `validated`
* `active`
* `suspended`
* `deprecated`
* `archived`
* `rejected`

Constraints:

```text
UNIQUE (organization_id, id)
UNIQUE (organization_id, slug)
```

Only Active Agents may begin normal Executions.

---

## Agent Versions

### `agent_versions`

Stores immutable Agent configuration Versions.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `agent_definition_id`    | UUID        | Required    |
| `version_number`         | BIGINT      | Required    |
| `instructions`           | TEXT        | Required    |
| `input_schema`           | JSONB       | Required    |
| `output_schema`          | JSONB       | Required    |
| `context_definition`     | JSONB       | Required    |
| `provider_selection`     | JSONB       | Required    |
| `execution_limits`       | JSONB       | Required    |
| `safety_configuration`   | JSONB       | Required    |
| `approval_configuration` | JSONB       | Required    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `validated_at`           | TIMESTAMPTZ | Optional    |
| `activated_at`           | TIMESTAMPTZ | Optional    |

Constraints:

```text
UNIQUE (
    organization_id,
    agent_definition_id,
    version_number
)

UNIQUE (organization_id, id)
```

Agent Versions must not be modified after creation.

---

## Agent Tools

### `agent_tools`

Defines the Tools available to an Agent Version.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `agent_version_id`  | UUID        | Required    |
| `tool_type`         | VARCHAR     | Required    |
| `tool_reference_id` | UUID        | Optional    |
| `capability`        | VARCHAR     | Required    |
| `permission_code`   | VARCHAR     | Required    |
| `configuration`     | JSONB       | Required    |
| `risk_level`        | VARCHAR     | Required    |
| `requires_approval` | BOOLEAN     | Required    |
| `created_at`        | TIMESTAMPTZ | Required    |

Possible Tool Types:

* `search`
* `knowledge`
* `event_query`
* `alert_query`
* `incident_query`
* `observability`
* `plugin`
* `workflow`
* `calculation`
* `internal_capability`

Only declared Tools may be invoked.

---

## Agent Execution Policies

### `agent_execution_policies`

Stores Agent-specific execution restrictions.

| Column                | Type        | Rules       |
| --------------------- | ----------- | ----------- |
| `id`                  | UUID        | Primary key |
| `organization_id`     | UUID        | Required    |
| `agent_definition_id` | UUID        | Required    |
| `environment_id`      | UUID        | Optional    |
| `policy_type`         | VARCHAR     | Required    |
| `configuration`       | JSONB       | Required    |
| `status`              | VARCHAR     | Required    |
| `created_at`          | TIMESTAMPTZ | Required    |
| `updated_at`          | TIMESTAMPTZ | Required    |
| `version`             | BIGINT      | Required    |

Possible Policy Types:

* `context_access`
* `provider_restriction`
* `model_restriction`
* `cost_limit`
* `execution_limit`
* `tool_restriction`
* `approval_requirement`
* `sensitive_data`

Platform and Organization Policies may impose additional restrictions.

---

## Agent Executions

### `agent_executions`

Stores one governed Agent invocation.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `agent_definition_id`  | UUID        | Required    |
| `agent_version_id`     | UUID        | Required    |
| `agent_identity_id`    | UUID        | Required    |
| `initiated_by_type`    | VARCHAR     | Required    |
| `initiated_by_id`      | UUID        | Optional    |
| `purpose`              | TEXT        | Required    |
| `target_resource_type` | VARCHAR     | Optional    |
| `target_resource_id`   | UUID        | Optional    |
| `status`               | VARCHAR     | Required    |
| `provider`             | VARCHAR     | Optional    |
| `model`                | VARCHAR     | Optional    |
| `requested_at`         | TIMESTAMPTZ | Required    |
| `authorized_at`        | TIMESTAMPTZ | Optional    |
| `started_at`           | TIMESTAMPTZ | Optional    |
| `completed_at`         | TIMESTAMPTZ | Optional    |
| `expires_at`           | TIMESTAMPTZ | Optional    |
| `failure_code`         | VARCHAR     | Optional    |
| `failure_message`      | TEXT        | Optional    |
| `correlation_id`       | UUID        | Optional    |
| `idempotency_key`      | VARCHAR     | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |
| `updated_at`           | TIMESTAMPTZ | Required    |
| `version`              | BIGINT      | Required    |

Supported statuses:

* `requested`
* `authorized`
* `queued`
* `running`
* `completed`
* `rejected`
* `failed`
* `cancelled`
* `timed_out`
* `blocked`
* `expired`

Constraints:

```text
UNIQUE (organization_id, id)
```

Optional idempotency constraint:

```text
UNIQUE (
    organization_id,
    agent_definition_id,
    idempotency_key
)
WHERE idempotency_key IS NOT NULL
```

---

## Execution Context

### `agent_execution_context`

Stores references to information supplied to an Agent Execution.

| Column               | Type        | Rules       |
| -------------------- | ----------- | ----------- |
| `id`                 | UUID        | Primary key |
| `organization_id`    | UUID        | Required    |
| `agent_execution_id` | UUID        | Required    |
| `context_type`       | VARCHAR     | Required    |
| `resource_type`      | VARCHAR     | Required    |
| `resource_id`        | UUID        | Required    |
| `relationship_type`  | VARCHAR     | Required    |
| `source_version`     | VARCHAR     | Optional    |
| `sensitivity`        | VARCHAR     | Required    |
| `included_at`        | TIMESTAMPTZ | Required    |

Possible Context Types:

* `event`
* `signal`
* `alert`
* `incident`
* `knowledge_version`
* `plugin_execution`
* `observability_record`
* `audit_record`
* `configuration`

Possible relationships:

* `target`
* `evidence`
* `related`
* `historical_example`
* `knowledge`
* `constraint`

Context should contain references rather than unrestricted copied payloads.

---

## Context Snapshots

A limited snapshot may be required when provider input must be preserved for reproducibility.

### `agent_context_snapshots`

| Column               | Type        | Rules       |
| -------------------- | ----------- | ----------- |
| `id`                 | UUID        | Primary key |
| `organization_id`    | UUID        | Required    |
| `agent_execution_id` | UUID        | Required    |
| `content_hash`       | VARCHAR     | Required    |
| `encrypted_content`  | BYTEA       | Required    |
| `sensitivity`        | VARCHAR     | Required    |
| `created_at`         | TIMESTAMPTZ | Required    |
| `expires_at`         | TIMESTAMPTZ | Optional    |

Snapshots should be created only when:

* Required for investigation.
* permitted by Organization Policy.
* supported by provider data-handling rules.

Snapshots require stricter retention and access controls.

---

## Tool Calls

### `agent_tool_calls`

Stores each Tool invocation made during an Execution.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `agent_execution_id`   | UUID        | Required    |
| `agent_tool_id`        | UUID        | Required    |
| `status`               | VARCHAR     | Required    |
| `target_resource_type` | VARCHAR     | Optional    |
| `target_resource_id`   | UUID        | Optional    |
| `request_reference`    | JSONB       | Required    |
| `result_reference`     | JSONB       | Optional    |
| `attempt_number`       | INTEGER     | Required    |
| `started_at`           | TIMESTAMPTZ | Required    |
| `completed_at`         | TIMESTAMPTZ | Optional    |
| `failure_code`         | VARCHAR     | Optional    |
| `failure_message`      | TEXT        | Optional    |

Supported statuses:

* `requested`
* `authorized`
* `running`
* `completed`
* `rejected`
* `failed`
* `cancelled`
* `timed_out`

Tool calls must not store credentials or unrestricted sensitive responses.

---

## Findings

### `agent_findings`

Stores conclusions produced by Agent Executions.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `agent_execution_id`      | UUID        | Required    |
| `finding_type`            | VARCHAR     | Required    |
| `statement`               | TEXT        | Required    |
| `confidence`              | VARCHAR     | Required    |
| `status`                  | VARCHAR     | Required    |
| `affected_scope`          | JSONB       | Required    |
| `limitations`             | JSONB       | Required    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `reviewed_by_identity_id` | UUID        | Optional    |
| `reviewed_at`             | TIMESTAMPTZ | Optional    |
| `superseded_at`           | TIMESTAMPTZ | Optional    |

Supported confidence values:

* `high`
* `medium`
* `low`
* `unknown`

Supported statuses:

* `proposed`
* `accepted`
* `rejected`
* `superseded`
* `inconclusive`

Agent Findings do not automatically change Alert, Incident, or Knowledge state.

---

## Finding Evidence

### `agent_finding_evidence`

Connects Agent Findings to supporting evidence.

| Column              | Type        | Rules    |
| ------------------- | ----------- | -------- |
| `organization_id`   | UUID        | Required |
| `agent_finding_id`  | UUID        | Required |
| `evidence_type`     | VARCHAR     | Required |
| `evidence_id`       | UUID        | Required |
| `relationship_type` | VARCHAR     | Required |
| `created_at`        | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (
    organization_id,
    agent_finding_id,
    evidence_type,
    evidence_id
)
```

Possible relationships:

* `supports`
* `contradicts`
* `derived_from`
* `related`

A Finding without evidence must be identified as inference or hypothesis.

---

## Recommendations

### `agent_recommendations`

Stores proposed operational Actions or Decisions.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `agent_execution_id`   | UUID        | Required    |
| `recommendation_type`  | VARCHAR     | Required    |
| `title`                | VARCHAR     | Required    |
| `description`          | TEXT        | Required    |
| `rationale`            | TEXT        | Required    |
| `confidence`           | VARCHAR     | Required    |
| `risk_level`           | VARCHAR     | Required    |
| `status`               | VARCHAR     | Required    |
| `target_resource_type` | VARCHAR     | Optional    |
| `target_resource_id`   | UUID        | Optional    |
| `required_permission`  | VARCHAR     | Optional    |
| `requires_approval`    | BOOLEAN     | Required    |
| `workflow_instance_id` | UUID        | Optional    |
| `expires_at`           | TIMESTAMPTZ | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |
| `updated_at`           | TIMESTAMPTZ | Required    |
| `version`              | BIGINT      | Required    |

Supported statuses:

* `proposed`
* `under_review`
* `accepted`
* `authorized`
* `executed`
* `verified`
* `rejected`
* `expired`
* `cancelled`
* `failed`
* `superseded`

A Recommendation remains separate from the resulting domain Action.

---

## Recommendation Evidence

### `agent_recommendation_evidence`

Connects Recommendations to supporting evidence.

| Column                    | Type        | Rules    |
| ------------------------- | ----------- | -------- |
| `organization_id`         | UUID        | Required |
| `agent_recommendation_id` | UUID        | Required |
| `evidence_type`           | VARCHAR     | Required |
| `evidence_id`             | UUID        | Required |
| `relationship_type`       | VARCHAR     | Required |
| `created_at`              | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (
    organization_id,
    agent_recommendation_id,
    evidence_type,
    evidence_id
)
```

---

## Recommendation Decisions

### `agent_recommendation_decisions`

Stores review decisions for a Recommendation.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `agent_recommendation_id` | UUID        | Required    |
| `decision`                | VARCHAR     | Required    |
| `reason`                  | TEXT        | Optional    |
| `decided_by_identity_id`  | UUID        | Required    |
| `decided_at`              | TIMESTAMPTZ | Required    |
| `approval_id`             | UUID        | Optional    |

Supported decisions:

* `accepted`
* `rejected`
* `cancelled`
* `superseded`

Decision records are immutable.

---

## Execution Usage

### `agent_execution_usage`

Stores provider usage and cost metadata.

| Column               | Type        | Rules       |
| -------------------- | ----------- | ----------- |
| `id`                 | UUID        | Primary key |
| `organization_id`    | UUID        | Required    |
| `agent_execution_id` | UUID        | Required    |
| `provider`           | VARCHAR     | Required    |
| `model`              | VARCHAR     | Required    |
| `input_units`        | BIGINT      | Optional    |
| `output_units`       | BIGINT      | Optional    |
| `tool_call_count`    | INTEGER     | Required    |
| `estimated_cost`     | DECIMAL     | Optional    |
| `currency`           | VARCHAR     | Optional    |
| `recorded_at`        | TIMESTAMPTZ | Required    |

Usage values are operational measurements.

Billing remains owned by the future Subscription capability.

---

## Feedback

### `agent_feedback`

Stores evaluation of an Execution, Finding, or Recommendation.

| Column               | Type        | Rules       |
| -------------------- | ----------- | ----------- |
| `id`                 | UUID        | Primary key |
| `organization_id`    | UUID        | Required    |
| `agent_execution_id` | UUID        | Required    |
| `target_type`        | VARCHAR     | Required    |
| `target_id`          | UUID        | Optional    |
| `feedback_type`      | VARCHAR     | Required    |
| `comment`            | TEXT        | Optional    |
| `submitted_by_type`  | VARCHAR     | Required    |
| `submitted_by_id`    | UUID        | Optional    |
| `created_at`         | TIMESTAMPTZ | Required    |

Possible Target Types:

* `execution`
* `finding`
* `recommendation`

Possible Feedback Types:

* `helpful`
* `not_helpful`
* `correct`
* `incorrect`
* `unsafe`
* `incomplete`
* `unsupported`
* `successful`
* `failed`

Feedback does not modify historical Agent output.

---

## Execution Lifecycle History

### `agent_execution_history`

Stores immutable execution status changes.

| Column               | Type        | Rules       |
| -------------------- | ----------- | ----------- |
| `id`                 | UUID        | Primary key |
| `organization_id`    | UUID        | Required    |
| `agent_execution_id` | UUID        | Required    |
| `previous_status`    | VARCHAR     | Optional    |
| `new_status`         | VARCHAR     | Required    |
| `reason`             | TEXT        | Optional    |
| `changed_by_type`    | VARCHAR     | Required    |
| `changed_by_id`      | UUID        | Optional    |
| `changed_at`         | TIMESTAMPTZ | Required    |

Execution history is append-only.

---

## Agent Activation Transaction

Activating an Agent Version should normally update within one transaction:

1. Agent Definition.
2. `active_version_id`.
3. Agent lifecycle history.
4. Agent Identity status where required.
5. Outbox message.

Only a validated Agent Version may become Active.

---

## Execution Creation Transaction

Starting an Agent Execution should normally create:

1. Agent Execution.
2. initial execution history.
3. approved context references.
4. idempotency record where required.
5. Outbox message.

Provider calls occur only after the transaction commits.

---

## Execution Completion Transaction

Completing an Agent Execution should normally create or update:

1. Execution status.
2. Findings.
3. Finding evidence.
4. Recommendations.
5. Recommendation evidence.
6. usage record.
7. execution history.
8. Outbox message.

Invalid output must not create accepted Findings or Recommendations.

---

## Optimistic Concurrency

Mutable Agent Definitions, Executions, and Recommendations use `version`.

Example:

```sql
UPDATE agent_executions
SET
    status = :new_status,
    version = version + 1,
    updated_at = :updated_at
WHERE organization_id = :organization_id
  AND id = :agent_execution_id
  AND version = :expected_version;
```

A failed Version match produces a concurrency conflict.

Agent Versions and decision records remain immutable.

---

## Tenant Isolation

Every Organization-specific Agent table must include `organization_id`.

The schema must prevent:

* Cross-Organization Agent context.
* use of another Organization’s Agent Identity.
* Tool calls against another Organization’s Resource.
* evidence references across Organizations.
* Recommendation targets across Organizations.
* Workflow references across Organizations.

Provider responses must be mapped back to the correct Organization and Execution.

---

## Required Indexes

Recommended indexes include:

```text
agent_definitions (
    organization_id,
    status,
    agent_type
)

agent_versions (
    organization_id,
    agent_definition_id,
    version_number
)

agent_tools (
    organization_id,
    agent_version_id
)

agent_executions (
    organization_id,
    status,
    requested_at
)

agent_executions (
    organization_id,
    agent_definition_id,
    requested_at
)

agent_executions (
    organization_id,
    target_resource_type,
    target_resource_id
)

agent_execution_context (
    organization_id,
    agent_execution_id
)

agent_execution_context (
    organization_id,
    resource_type,
    resource_id
)

agent_tool_calls (
    organization_id,
    agent_execution_id,
    status
)

agent_findings (
    organization_id,
    agent_execution_id,
    status
)

agent_recommendations (
    organization_id,
    status,
    risk_level
)

agent_recommendations (
    organization_id,
    target_resource_type,
    target_resource_id
)

agent_execution_usage (
    organization_id,
    recorded_at
)

agent_feedback (
    organization_id,
    agent_execution_id,
    feedback_type
)
```

---

## Retention

Agent retention applies to:

* Agent Definitions.
* Agent Versions.
* Executions.
* context references.
* context snapshots.
* Tool calls.
* Findings.
* Recommendations.
* decisions.
* usage.
* feedback.
* execution history.

Context snapshots and raw provider data should use shorter retention than structured execution records unless investigation or compliance requires otherwise.

Historical Executions must retain:

* Agent Version.
* provider.
* model.
* evidence references.
* outcome.
* usage metadata.

---

## Security Requirements

* Provider credentials must not be stored in Agent tables.
* Context must be authorized before inclusion.
* Sensitive context should be minimized or redacted.
* Raw prompts and responses should not be retained by default.
* Untrusted operational content must be treated as data.
* Tool calls require permission and Policy evaluation.
* Agents cannot approve their own Recommendations.
* Protected Actions require execution-time authorization.
* Cross-Organization context is prohibited.
* Agent output must be validated before persistence.

---

## Audit Requirements

Auditable operations include:

* Agent creation.
* Version creation.
* validation.
* activation.
* suspension.
* Tool changes.
* permission changes.
* provider restrictions.
* Execution requests.
* sensitive context access.
* protected Tool calls.
* Recommendation approval.
* automated Action requests.
* context export.
* emergency Agent disablement.

Execution history remains separate from general Audit Records.

---

## Business Rules

* Every Organization-specific Agent belongs to exactly one Organization.
* Every active Agent uses one distinct Agent Identity.
* Agent Versions are immutable.
* Historical Executions retain the exact Version, provider, and model used.
* Every Execution has an explicit purpose.
* Every context item must be authorized.
* Agent-generated content is not source evidence.
* Findings must reference evidence or identify themselves as inference.
* Recommendations remain distinct from completed Actions.
* Agent output must pass validation.
* Protected Actions require normal authorization.
* Agents cannot approve their own Recommendations.
* Automated Actions execute through governed Workflows.
* Feedback does not modify historical output.
* Suspended Agents cannot begin normal Executions.
* Cross-Organization Agent access is prohibited.
* Missing or conflicting Organization ownership results in rejection.

---

## Success Criteria

The Agent schema is successful when:

* Agent Definitions and Versions remain clearly separated.
* every Execution is attributable.
* historical provider and model usage can be reconstructed.
* Agent context remains authorized and tenant-isolated.
* Tool activity remains traceable.
* Findings remain connected to evidence.
* Recommendations remain governed.
* protected Actions cannot bypass approval.
* cost and usage remain measurable.
* feedback can improve future Agent Versions.
* unsafe Agents can be suspended.
* tenant isolation is preserved.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/08-knowledge-schema.md`
* `docs/database/10-plugin-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/15-retention-archival.md`
* `docs/functional/09-agent-platform.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/07-agent-architecture.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-006-agent-platform.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
