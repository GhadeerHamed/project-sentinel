# Agent Module

## Purpose

The Agent module owns AI Agent definitions, Versions, executions, Tool access, findings, recommendations, and Action proposals.

Agents assist operational work but do not bypass domain ownership, authorization, or approval.

---

## Owns

* Agent Definition.
* Agent Version.
* Agent status.
* Agent capability requirements.
* Agent execution.
* execution context references.
* Tool invocation records.
* findings.
* recommendations.
* Action proposals.
* usage and cost metadata.

---

## Does Not Own

* Events, Alerts, Incidents, or Knowledge.
* Workflow lifecycle.
* Plugin execution.
* authorization Policy.
* external provider credentials.
* authoritative remediation Actions.

Agents interact with owning modules through approved contracts.

---

## Core Resources

### Agent Definition

Describes an operational Agent.

```text
id
organization_id
name
purpose
status
resource_scope
created_at
```

### Agent Version

Represents an immutable Agent configuration.

```text
id
agent_id
version
instructions
model_configuration
declared_capabilities
tool_policy
released_at
```

### Agent Execution

Represents one execution attempt.

```text
id
organization_id
agent_version_id
status
objective
target_resource_type
target_resource_id
started_at
completed_at
correlation_id
```

Primary identifiers use UUIDv7.

---

## Lifecycle

Suggested Agent states:

```text
draft
active
suspended
archived
```

Agent Versions are immutable after activation.

Suspended or Archived Agents cannot start normal executions.

---

## Execution Status

Suggested execution states:

```text
pending
running
waiting_for_approval
completed
failed
cancelled
timed_out
```

Execution status must not be inferred only from provider responses.

---

## Commands

```text
CreateAgent
CreateAgentVersion
ActivateAgentVersion
SuspendAgent
ReactivateAgent
ArchiveAgent
StartAgentExecution
CancelAgentExecution
RecordAgentFinding
CreateActionProposal
ApproveActionProposal
RejectActionProposal
RetryAgentExecution
```

Commands require Tenant Context and authorization.

---

## Queries

```text
GetAgent
ListAgents
GetAgentVersion
ListAgentVersions
GetAgentExecution
ListAgentExecutions
GetAgentFindings
GetActionProposal
```

Queries must preserve Organization, Resource, and sensitivity scope.

---

## Published Events

```text
agent.created
agent.version_activated
agent.suspended
agent.reactivated
agent.archived
agent.execution_started
agent.execution_completed
agent.execution_failed
agent.execution_cancelled
agent.finding_recorded
agent.action_proposed
agent.action_approved
agent.action_rejected
```

Events must not expose credentials, unrestricted prompts, or sensitive provider payloads.

---

## Consumed Events

The module may consume:

```text
alert.created
incident.created
incident.resolved
knowledge.published
workflow.agent_execution_requested
organization.suspended
organization.archived
credential.revoked
```

Consumed events may trigger or restrict execution according to Policy.

Consumers must remain idempotent.

---

## Agent Identity

Every Active Agent uses a dedicated non-human identity.

The identity is bound to:

```text
organization_id
agent_id
agent_version_id
granted_permissions
resource_scope
status
```

Agents must not inherit the full permissions of their creator.

---

## Capability Model

Effective Agent access is:

```text
Declared capability
∩ Organization grant
∩ Resource scope
∩ runtime Policy
```

Example capabilities:

```text
event.read
alert.read
incident.read
knowledge.read
incident.action.propose
workflow.trigger
```

Agents cannot modify their own capabilities.

---

## Execution Context

Execution context may include authorized references to:

* Events.
* Signals.
* Alerts.
* Incidents.
* Knowledge.
* Application and Environment metadata.
* Plugin-provided evidence.
* Workflow context.

Context must be minimized and belong to one Organization.

---

## Prompt Security

Agent instructions and context must treat external content as untrusted.

Prompt injection must not change:

* Tenant scope.
* granted capabilities.
* Tool availability.
* approval requirements.
* system instructions.
* authorization rules.

Secrets must never be placed in prompts.

---

## Tool Access

Agents may use only explicitly approved Tools.

Examples:

```text
ReadIncident
SearchKnowledge
ListRelatedAlerts
ProposeIncidentAction
RequestWorkflowExecution
```

Each Tool invocation must:

* Validate Organization scope.
* evaluate current authorization.
* validate arguments.
* enforce rate and execution limits.
* record execution metadata.

---

## Findings

Findings are evidence-based observations produced by an Agent.

A finding should include:

```text
execution_id
finding_type
summary
confidence
evidence_references
created_at
```

Findings remain non-authoritative until acted upon through an owning module.

---

## Action Proposals

Agents may propose Actions such as:

* Escalate an Incident.
* update severity.
* start a Workflow.
* notify responders.
* disable an unsafe integration.
* create a Knowledge draft.

A proposal does not execute the Action automatically.

---

## Action Execution

Protected Actions must pass through:

1. The owning module.
2. current authorization.
3. current Resource state validation.
4. approval where required.
5. idempotency protection.
6. Audit recording.

Agent confidence does not replace Policy evaluation.

---

## Approval

High-risk proposals may require approval.

Approval should record:

```text
proposal_id
approved_by
approved_at
scope
expiration
decision_note
```

Approval is specific to one proposed Action and does not grant permanent permission.

---

## Provider Boundary

AI providers must be accessed through an adapter contract.

Example:

```text
AgentProvider
```

The Agent module must not depend directly on one provider SDK or model family.

Provider credentials remain in secret management.

---

## Execution Limits

Every Agent Version should define limits for:

```text
maximum execution time
maximum Tool calls
maximum context size
maximum retries
usage or cost budget
allowed providers or models
```

Limits may also apply per Organization.

---

## Failure Handling

Transient failures may retry with bounded backoff.

Permanent failures include:

* Capability denial.
* invalid Tool request.
* tenant mismatch.
* unsupported output.
* revoked credential.
* invalid Resource state.
* exceeded budget.

Repeated unsafe failures may suspend the Agent.

---

## Incident Integration

Agents may:

* Summarize Incident evidence.
* identify related Alerts.
* propose severity.
* suggest remediation.
* draft status updates.

They must invoke Incident commands for any authoritative state change.

---

## Knowledge Integration

Agents may read authorized published Knowledge and create Draft proposals.

Agents cannot:

* Publish Knowledge.
* access restricted Knowledge without permission.
* expose restricted evidence through generated summaries.

---

## Workflow Integration

Workflows may request Agent execution and wait for its outcome.

Agents may propose Workflow execution but must not bypass Workflow authorization or approval.

---

## Repository Interfaces

```text
AgentRepository
AgentVersionRepository
AgentExecutionRepository
AgentFindingRepository
AgentActionProposalRepository
AgentToolInvocationRepository
```

Other modules must not write Agent persistence directly.

---

## Tenant Isolation

Every Agent, Version, execution, finding, and proposal belongs to one Organization.

Agent execution must never:

* combine context from multiple Organizations.
* reuse another tenant’s provider context.
* access cross-tenant embeddings.
* switch Organization through Tool arguments.
* use another Agent’s credentials.

---

## Audit Requirements

Audit Records are required for:

* Agent activation and suspension.
* capability changes.
* provider configuration changes.
* protected Action proposals.
* approval decisions.
* protected Action execution.
* manual retry or cancellation.
* privileged execution access.

Normal execution history remains in Agent execution records.

---

## Prohibited Practices

The Agent module must not:

* Treat model output as authoritative.
* place secrets in prompts.
* inherit unrestricted User permissions.
* provide unrestricted Tool access.
* mix Organization context.
* execute protected Actions without runtime Policy evaluation.
* publish Agent-generated Knowledge automatically.
* hide Tool calls or Action proposals.
* allow unlimited execution cost or duration.

---

## Business Rules

* Every Agent belongs to one Organization.
* Agent Versions are immutable after activation.
* every Agent uses a dedicated non-human identity.
* capabilities and Resource scope are explicit.
* execution context is authorized and minimized.
* Agent output is untrusted until validated.
* findings and proposals are non-authoritative.
* protected Actions execute only through owning modules.
* approval is Action-specific and time-bounded.
* Agent executions are bounded, attributable, observable, and auditable.

---

## Related Documents

* `docs/modules/08-incident-module.md`
* `docs/modules/09-knowledge-module.md`
* `docs/modules/11-plugin-module.md`
* `docs/modules/14-identity-access-module.md`
* `docs/modules/17-workflow-module.md`
* `docs/database/09-agent-schema.md`
* `docs/api/14-agents-api.md`
* `docs/security/07-agent-plugin-security.md`
* `docs/architecture/07-agent-architecture.md`
* `docs/adr/ADR-006-agent-platform.md`
