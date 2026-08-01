# Workflow Module

## Purpose

The Workflow module coordinates multi-step operational processes across Sentinel modules.

It manages orchestration without taking ownership of the Resources modified by Workflow Steps.

---

## Owns

* Workflow Definition.
* Workflow Version.
* Workflow Instance.
* Workflow Step.
* Step execution.
* approval request.
* approval decision.
* Timer.
* Workflow execution history.
* Workflow variables.

---

## Does Not Own

* Alerts or Incidents.
* Notifications.
* Agent or Plugin state.
* Knowledge publication.
* identity and permissions.
* domain Actions executed by other modules.

A Workflow invokes public module contracts and must not write another module’s tables directly.

---

## Core Resources

### Workflow Definition

Describes a reusable operational process.

```text
id
organization_id
name
status
trigger_definition
created_at
```

### Workflow Version

An immutable executable definition.

```text
id
workflow_id
version
steps
transitions
input_schema
activated_at
```

### Workflow Instance

Represents one execution.

```text
id
organization_id
workflow_version_id
status
trigger_type
trigger_reference
started_at
completed_at
correlation_id
version
```

Primary identifiers use UUIDv7.

---

## Definition Lifecycle

Suggested states:

```text
draft
active
suspended
archived
```

Activated Workflow Versions are immutable.

Editing an active Workflow creates a new Version.

---

## Instance Status

Suggested states:

```text
pending
running
waiting
waiting_for_approval
completed
failed
cancelled
timed_out
```

Terminal states are:

```text
completed
failed
cancelled
timed_out
```

---

## Commands

```text
CreateWorkflow
CreateWorkflowVersion
ActivateWorkflowVersion
SuspendWorkflow
ArchiveWorkflow
StartWorkflow
CancelWorkflow
RetryWorkflowStep
CompleteWorkflowStep
CreateApprovalRequest
ApproveWorkflowAction
RejectWorkflowAction
ResumeWorkflow
ProcessWorkflowTimer
```

Commands require Tenant Context and authorization.

---

## Queries

```text
GetWorkflow
ListWorkflows
GetWorkflowVersion
GetWorkflowInstance
ListWorkflowInstances
GetWorkflowTimeline
GetPendingApprovals
GetWorkflowStep
```

Queries must preserve Organization and Resource scope.

---

## Published Events

```text
workflow.created
workflow.version_activated
workflow.suspended
workflow.started
workflow.step_started
workflow.step_completed
workflow.step_failed
workflow.approval_requested
workflow.approval_granted
workflow.approval_rejected
workflow.completed
workflow.failed
workflow.cancelled
workflow.timed_out
```

Events must include Organization, Workflow Instance, correlation, and causation identifiers.

---

## Consumed Events

The module may consume:

```text
alert.created
alert.resolved
incident.created
incident.resolved
knowledge.review_requested
plugin.synchronization_failed
agent.action_proposed
notification.failed
organization.suspended
organization.archived
```

Consumed events may trigger eligible active Workflow Versions.

Event handling must remain idempotent.

---

## Workflow Steps

Supported Step categories may include:

```text
domain_command
agent_execution
plugin_execution
notification_request
approval
delay
condition
parallel_group
```

Every Step must declare:

* Input contract.
* output contract.
* timeout.
* retry Policy.
* required capability.
* failure behavior.

---

## Domain Commands

A Workflow may invoke commands such as:

```text
AcknowledgeAlert
AssignIncidentCommander
CreateKnowledgeArticle
RequestNotification
StartAgentExecution
```

The owning module performs:

* Authorization.
* tenant validation.
* lifecycle validation.
* concurrency control.
* Audit recording.

Workflow execution never bypasses domain rules.

---

## Conditions

Conditions determine execution paths using approved Workflow data.

Conditions must be:

* Deterministic where practical.
* bounded.
* side-effect free.
* validated before activation.

Arbitrary application code execution is prohibited.

---

## Approvals

High-risk Steps may require approval.

An approval request should contain:

```text
workflow_instance_id
step_id
requested_action
target_resource
required_permission
expires_at
```

Approval must be:

* Given by an authorized identity.
* scoped to one Action.
* time-limited.
* recorded immutably.

Approval does not grant permanent permission.

---

## Timers and Delays

Workflow Timers support:

* Delayed Steps.
* approval expiration.
* escalation deadlines.
* scheduled follow-up.
* timeout handling.

Timers should dispatch Queue jobs rather than perform long-running work in the scheduler.

Timer processing must be idempotent.

---

## Parallel Execution

Independent Steps may execute in parallel.

The Workflow Version must define:

* Required successful branches.
* failure behavior.
* join condition.
* cancellation behavior.

Parallel execution must not allow conflicting domain updates to bypass optimistic concurrency.

---

## Variables

Workflow variables store bounded execution data.

They may contain:

* Resource identifiers.
* approved scalar values.
* Step outputs.
* correlation metadata.

They must not contain:

* Plaintext credentials.
* unrestricted tenant payloads.
* mutable domain objects.
* excessive binary data.

Authoritative state must be reloaded from the owning module.

---

## Agent Integration

A Workflow may start Agent execution and consume its result.

Agent findings remain non-authoritative.

Any proposed Action must pass through:

```text
Approval when required
→ owning module command
→ runtime authorization
```

---

## Plugin Integration

A Workflow may request approved Plugin operations.

Plugin execution must preserve:

* Installation identity.
* granted capabilities.
* Organization scope.
* execution limits.
* provider rate limits.

The Workflow module does not modify Plugin execution state directly.

---

## Notification Integration

A Workflow may request Notifications and wait for delivery outcomes.

Notification failures affect Workflow status only according to the Workflow Version’s explicit failure Policy.

---

## Idempotency

Workflow starts and Step execution must be idempotent.

Possible keys include:

```text
workflow_version_id
trigger_event_id
step_id
execution_attempt
```

Duplicate events must not create duplicate Workflow Instances unless explicitly configured.

---

## Retries

Step retries must distinguish transient and permanent failures.

Transient examples:

* Provider timeout.
* temporary dependency failure.
* Queue interruption.

Permanent examples:

* Authorization denial.
* tenant mismatch.
* invalid Resource state.
* rejected approval.
* invalid Step configuration.

Retries must use bounded backoff.

---

## Transactions

Workflow state transitions should atomically commit:

* Instance or Step state.
* execution history.
* approval state where applicable.
* Timer state.
* Outbox Records.

External calls and domain commands should not run inside long Workflow database transactions.

---

## Repository Interfaces

```text
WorkflowRepository
WorkflowVersionRepository
WorkflowInstanceRepository
WorkflowStepRepository
WorkflowApprovalRepository
WorkflowTimerRepository
```

Other modules must not modify Workflow persistence directly.

---

## Authorization

Typical permissions:

```text
workflow.create
workflow.update
workflow.activate
workflow.execute
workflow.cancel
workflow.retry
workflow.approval.decide
workflow.read
```

Starting a Workflow does not automatically authorize every Step it contains.

Each protected Step requires execution-time authorization.

---

## Tenant Isolation

Every Definition, Version, Instance, Step, approval, and Timer belongs to one Organization.

Referenced Resources, identities, Agents, Plugins, and Notifications must belong to the same Organization.

Cross-Organization Workflow execution is prohibited.

---

## Audit Requirements

Audit Records are required for:

* Workflow activation and suspension.
* manual execution.
* cancellation.
* manual retry.
* approval decisions.
* protected domain Actions.
* configuration changes affecting sensitive operations.

Normal Step history remains in Workflow execution records.

---

## Prohibited Practices

The Workflow module must not:

* Write another module’s tables.
* bypass execution-time authorization.
* treat approval as permanent access.
* store plaintext secrets in variables.
* execute arbitrary code.
* retry permanent failures indefinitely.
* mix Resources from different Organizations.
* mutate activated Workflow Versions.
* assume Agent output is authoritative.
* hide failed or skipped Steps.

---

## Business Rules

* Workflow Versions are immutable after activation.
* every Workflow Instance uses one exact Version.
* Workflows coordinate but do not own domain Resources.
* protected Steps invoke owning module commands.
* every Step is authorized at execution time.
* approvals are Action-specific and time-limited.
* Workflow and Step execution are idempotent.
* variables contain references and bounded data, not authoritative objects.
* failures, retries, and skipped Steps remain observable.
* all Workflow data remains Organization-scoped.

---

## Related Documents

* `docs/modules/07-alert-module.md`
* `docs/modules/08-incident-module.md`
* `docs/modules/09-knowledge-module.md`
* `docs/modules/10-agent-module.md`
* `docs/modules/11-plugin-module.md`
* `docs/modules/12-notification-module.md`
* `docs/database/12-workflow-schema.md`
* `docs/api/18-workflows-api.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/deployment/06-workers-queues.md`
