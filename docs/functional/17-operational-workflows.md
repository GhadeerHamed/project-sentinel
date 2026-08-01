# Operational Workflows

## Purpose

Operational Workflows coordinate multi-step operational processes across Project Sentinel capabilities.

A Workflow may coordinate:

* Incident response.
* Alert escalation.
* approval processes.
* remediation actions.
* Knowledge review.
* Agent recommendations.
* Plugin operations.
* administrative changes.
* follow-up tasks.

A Workflow coordinates work.

It does not replace the business capabilities that own Alerts, Incidents, Knowledge, Agents, Plugins, or other Resources.

---

## Objectives

Operational Workflows enable Sentinel to:

* Define repeatable operational processes.
* Coordinate human and automated steps.
* assign tasks and responsibilities.
* manage approvals.
* apply conditions and branching.
* invoke authorized capability operations.
* track execution progress.
* manage timeouts and failures.
* preserve evidence and decisions.
* support manual intervention.
* provide complete execution history.
* prevent duplicate execution.
* preserve Organization isolation.

---

## Responsibilities

Operational Workflows are responsible for:

* Managing Workflow Definitions.
* managing Workflow Versions.
* starting Workflow Instances.
* evaluating triggers.
* executing Workflow Steps.
* evaluating conditions.
* assigning Tasks.
* collecting approvals.
* invoking authorized capability operations.
* tracking Workflow state.
* managing retries and timeouts.
* managing cancellation.
* managing compensation actions.
* publishing Workflow events.
* preserving execution history.

---

## Functional Boundaries

Operational Workflows do not:

* Own source domain Resources.
* replace Alert or Incident lifecycle.
* bypass Authentication and Authorization.
* bypass Organization Policies.
* access another Organization.
* modify domain data directly.
* access Core persistence directly.
* treat Workflow state as domain state.
* execute protected actions without authorization.
* guarantee external-provider availability.
* use database rollback as business compensation.
* erase failed or cancelled execution history.

Each capability remains authoritative for its own Resources and operations.

---

## Position in the Platform

```text
Operational Trigger

↓

Workflow Instance Created

↓

Steps and Conditions Evaluated

↓

Human, System, Agent, or Plugin Actions

↓

Owning Capabilities Invoked

↓

Results Verified

↓

Workflow Completed
```

Workflow completion means the defined process finished.

It does not automatically mean every external or domain effect succeeded unless those effects were verified.

---

## Core Concepts

| Concept             | Description                                                       |
| ------------------- | ----------------------------------------------------------------- |
| Workflow Definition | The logical description of an operational process                 |
| Workflow Version    | An immutable version of a Workflow Definition                     |
| Workflow Instance   | One execution of a Workflow Version                               |
| Trigger             | The condition that starts a Workflow                              |
| Step                | One unit of work in a Workflow                                    |
| Transition          | Movement between Workflow Steps                                   |
| Condition           | A rule controlling execution or branching                         |
| Task                | Work assigned to a human or Team                                  |
| Action              | An operation requested from a capability                          |
| Approval            | A governed decision required before continuing                    |
| Execution Context   | The authorized information available to the Workflow              |
| Evidence            | Records supporting Workflow decisions and outcomes                |
| Timeout             | A limit on how long a Step may wait                               |
| Retry               | A repeated execution attempt after a temporary failure            |
| Compensation        | A business action intended to reduce or reverse an earlier effect |

---

## Workflow Categories

Workflows may support:

| Category             | Example                                                 |
| -------------------- | ------------------------------------------------------- |
| Incident Response    | Coordinate investigation, mitigation, and communication |
| Alert Escalation     | Assign, notify, and escalate unattended Alerts          |
| Approval             | Review and approve protected operations                 |
| Automation           | Execute approved operational actions                    |
| Knowledge Governance | Review and publish Knowledge                            |
| Agent Action         | Review and execute Agent Recommendations                |
| Plugin Operation     | Coordinate approved external actions                    |
| Administration       | Apply governed configuration changes                    |
| Follow-Up            | Track work created after an Incident or review          |

---

## Workflow Ownership

Every Workflow Definition and Workflow Instance belongs to exactly one Organization.

Organization ownership establishes:

* Tenant isolation.
* authorization boundaries.
* Policy scope.
* audit ownership.
* retention responsibility.
* execution limits.

Organization ownership must not change after creation.

Platform-provided Workflow Definitions may be made available to Organizations, but each resulting Workflow Instance must belong to one Organization.

---

## Workflow Definition

A Workflow Definition should include:

* Name.
* purpose.
* owner.
* Organization.
* supported trigger.
* Steps.
* Transitions.
* Conditions.
* required permissions.
* approval requirements.
* timeout behavior.
* failure behavior.
* lifecycle status.

A Workflow Definition describes the process.

It does not represent a running execution.

---

## Workflow Definition Lifecycle

Workflow Definitions follow a controlled lifecycle.

```text
Draft

↓

Validated

↓

Active

↓

Superseded

↓

Archived
```

A Workflow Definition may also become:

* Rejected.
* Suspended.

Only Active Workflow Versions may start normal Workflow Instances.

---

## Draft Workflow

A Draft Workflow may be edited and tested.

Draft Workflows must not execute protected production actions unless an approved testing Policy permits it.

---

## Validation

Validation confirms that a Workflow Definition is structurally and operationally safe.

Validation may include:

* Step references.
* Transition validity.
* condition validity.
* required permissions.
* approval requirements.
* timeout settings.
* retry settings.
* compensation definitions.
* Organization Scope.
* capability compatibility.
* loop detection.
* unreachable Step detection.

Invalid Workflow Definitions must not become Active.

---

## Workflow Versioning

Material Workflow changes require a new immutable Workflow Version.

Material changes include:

* Adding or removing Steps.
* changing Conditions.
* changing Transitions.
* changing approvals.
* changing permissions.
* changing timeout behavior.
* changing retry behavior.
* changing compensation.
* changing invoked capability operations.

Active Workflow Versions must not be modified.

Existing Workflow Instances continue using the Version with which they started.

---

## Workflow Triggers

A Workflow may begin from:

* A domain event.
* Alert creation or escalation.
* Incident declaration.
* Agent Recommendation.
* Plugin event.
* administrative request.
* scheduled condition.
* Public API request.
* authorized manual action.
* another Workflow.

Every trigger must identify:

* Organization.
* trigger type.
* source.
* initiating identity or process.
* related Resource.
* occurrence time.
* idempotency reference where applicable.

---

## Trigger Rules

* Triggers must be authenticated or originate from trusted platform processes.
* The related Resource must belong to the Workflow Organization.
* Trigger conditions must be evaluated before starting.
* Duplicate triggers must not create duplicate Workflow effects.
* A Workflow must not trigger itself indefinitely.
* Trigger history must remain traceable.

---

## Workflow Instance

A Workflow Instance represents one execution of one Workflow Version.

A Workflow Instance should record:

* Organization.
* Workflow Definition.
* Workflow Version.
* trigger.
* initiating identity or process.
* related Resources.
* current state.
* current Steps.
* start time.
* completion time.
* execution result.
* Correlation Identifier.

---

## Workflow Instance Lifecycle

```text
Pending

↓

Running

↓

Waiting

↓

Completed
```

A Workflow Instance may also become:

* Failed.
* Cancelled.
* Expired.
* Compensating.
* Compensated.

---

## Pending Workflows

A Workflow is Pending while waiting for:

* Authorization.
* required context.
* execution capacity.
* required approval.
* scheduled start time.

Pending Workflows must not execute Actions before their prerequisites are satisfied.

---

## Running Workflows

A Workflow is Running while one or more Steps are actively executing.

A Running Workflow may:

* Evaluate Conditions.
* create Tasks.
* request approvals.
* invoke capability operations.
* call approved Agent or Plugin capabilities.
* wait for results.
* record evidence.
* create new eligible Steps.

---

## Waiting Workflows

A Workflow becomes Waiting when progress depends on:

* Human Task completion.
* approval.
* external provider result.
* scheduled delay.
* domain event.
* asynchronous Job.
* retry delay.

The reason for Waiting and the expected next condition must remain visible.

---

## Completed Workflows

A Workflow becomes Completed when:

* Required Steps reached successful terminal states.
* required approvals were received.
* required capability results were verified.
* no mandatory Step remains active.
* the final Workflow outcome was recorded.

Completion must not be inferred from a request being accepted when the required effect is still pending.

---

## Failed Workflows

A Workflow becomes Failed when it cannot satisfy its required outcome.

Failure may result from:

* Permanent Step failure.
* authorization denial.
* approval rejection.
* timeout.
* invalid context.
* unavailable required capability.
* failed compensation.
* unsupported state.
* retry exhaustion.

Failure must identify the failing Step and reason.

---

## Cancelled Workflows

A Workflow may be Cancelled by:

* An authorized identity.
* Organization Policy.
* related Resource cancellation.
* superseding Workflow.
* emergency control.
* platform safety process.

Cancellation must stop future eligible Steps.

Already completed external or domain effects remain unless compensation is explicitly executed.

---

## Workflow Steps

A Step represents one unit of Workflow work.

Step Types may include:

| Step Type         | Purpose                                      |
| ----------------- | -------------------------------------------- |
| Human Task        | Work assigned to a Member or Team            |
| System Action     | Operation performed by a Sentinel capability |
| Agent Step        | Governed Agent Execution                     |
| Plugin Step       | Governed Plugin operation                    |
| Approval Step     | Waits for an authorization decision          |
| Condition Step    | Evaluates branching logic                    |
| Wait Step         | Waits for time or an event                   |
| Notification Step | Requests operational communication           |
| Compensation Step | Performs a business recovery action          |

---

## Step Execution

Each Step should record:

* Step identifier.
* Step Type.
* status.
* assigned identity or process.
* start time.
* completion time.
* input references.
* output references.
* attempt count.
* error.
* evidence.
* resulting Transition.

Step execution history remains immutable.

---

## Step Status

A Step may be:

* Pending.
* Ready.
* Running.
* Waiting.
* Completed.
* Failed.
* Rejected.
* Cancelled.
* Skipped.
* Timed Out.

Skipped Steps require an explainable Condition or decision.

---

## Conditions and Branching

Conditions determine whether a Step or Transition is eligible.

Conditions may evaluate:

* Resource state.
* severity.
* priority.
* approval result.
* Task result.
* capability result.
* Agent Finding.
* Plugin result.
* elapsed time.
* Organization Policy.
* Workflow context.

Conditions must be deterministic for the same recorded input where practical.

---

## Condition Rules

* Conditions must use authorized context.
* Missing required context must not be treated as approval.
* Condition results must remain traceable.
* High-impact branching must be explainable.
* Agent output alone must not authorize protected branching unless Policy explicitly permits it.
* Conditions must not create uncontrolled execution loops.

---

## Human Tasks

A Human Task represents work assigned to a Member or Team.

A Task should define:

* Description.
* assignee.
* required permission.
* priority.
* due time.
* completion requirements.
* related Resources.
* required evidence.
* escalation behavior.

---

## Task Assignment

Tasks may be assigned to:

* A specific Member.
* A Team.
* an Incident role.
* an Application owner.
* an Environment owner.
* an on-call group.

Assignment must remain within the Organization.

Inactive or unauthorized Members must not retain active Tasks.

---

## Task Completion

Task completion should record:

* Completing identity.
* completion time.
* result.
* evidence.
* comments where required.
* resulting Workflow Transition.

Marking a Task complete does not prove an external action succeeded unless evidence verifies it.

---

## Approvals

Approval Steps protect sensitive or high-impact operations.

Approval may be required for:

* Production changes.
* destructive actions.
* Incident severity changes.
* external communication.
* Agent-recommended remediation.
* Plugin actions.
* Knowledge publication.
* privileged administration.
* emergency access.

---

## Approval Requirements

An Approval should define:

* Requested operation.
* approver or eligible approvers.
* required permission.
* approval scope.
* reason.
* expiration.
* separation-of-duties rules.
* resulting action.

Approval does not replace final authorization at execution time.

---

## Approval Decisions

An Approval Decision may be:

* Approved.
* Rejected.
* Expired.
* Cancelled.

Every decision must record:

* Decision maker.
* time.
* reason where required.
* approved scope.
* conditions.
* related evidence.

---

## Separation of Duties

A Workflow may require that:

* The requester cannot approve the request.
* the Agent cannot approve its own Recommendation.
* the Plugin cannot approve its own action.
* the author cannot publish high-risk Knowledge alone.
* one identity cannot perform all stages of a protected operation.

Separation-of-duties violations must block progression.

---

## Capability Actions

A Workflow may request operations from Sentinel capabilities.

Examples include:

* Acknowledge an Alert.
* create an Incident.
* assign a responder.
* publish Knowledge.
* execute an Agent.
* invoke a Plugin.
* send a Notification.
* activate configuration.
* create a follow-up Task.

The owning capability:

* Authorizes the operation.
* validates domain rules.
* performs the operation.
* returns the result.
* remains authoritative for resulting state.

---

## Execution Context

Execution Context contains the information required by the Workflow.

It may include:

* Trigger information.
* Resource references.
* Organization context.
* identity references.
* previous Step results.
* approval results.
* evidence references.
* deadlines.
* Policy decisions.

Context must not contain unauthorized data.

---

## Evidence

Workflow evidence may include:

* Events.
* Signals.
* Alerts.
* Incidents.
* Knowledge Versions.
* approval records.
* Task results.
* Agent Findings.
* Plugin results.
* capability responses.
* Notification outcomes.
* audit records.

Evidence remains owned by its source capability.

The Workflow preserves references and usage context.

---

## Timeouts

Timeouts limit how long a Step or Workflow may remain active or waiting.

Timeout behavior may:

* Fail the Step.
* escalate.
* reassign a Task.
* retry an Action.
* cancel the Workflow.
* begin compensation.
* require manual intervention.

Timeouts must be explicit and auditable.

---

## Retries

Retries may be used for temporary failures.

Retry configuration should define:

* Eligible failure types.
* maximum attempts.
* delay.
* expiration.
* idempotency requirements.
* escalation after exhaustion.

Non-idempotent Actions must not be retried automatically unless the owning capability supports safe retry.

---

## Compensation

Compensation is a business action intended to reduce or reverse a previous effect.

Examples include:

* Re-enable a disabled feature.
* restore previous configuration.
* cancel an external request.
* revoke temporary access.
* issue a corrective Notification.

Compensation is not a database rollback.

Compensation may fail and must have its own recorded outcome.

---

## Manual Intervention

Authorized users may intervene when a Workflow becomes blocked or unsafe.

Manual intervention may:

* Retry a Step.
* skip an optional Step.
* reassign a Task.
* cancel the Workflow.
* provide missing context.
* approve an exception.
* begin compensation.
* mark the Workflow for investigation.

Manual intervention must be authorized, justified, and auditable.

---

## Idempotency

Workflow execution must prevent duplicate business effects.

Idempotency may use:

* Trigger identifier.
* Workflow Instance identifier.
* Step identifier.
* capability operation identifier.
* Idempotency Key.
* external provider reference.

Repeated execution of an idempotent Step must return the existing result or safely reproduce the same effect.

---

## Concurrency

Multiple Workflow Instances may target the same Resource.

Concurrency controls should prevent:

* Conflicting lifecycle changes.
* duplicate approvals.
* duplicate external actions.
* inconsistent assignments.
* repeated compensation.
* stale-state updates.

The owning capability must perform final concurrency validation before changing its Resource.

---

## Authorization and Policy

Every protected Workflow operation must evaluate:

* Acting identity.
* Organization.
* required permission.
* Resource Scope.
* authentication strength.
* approval status.
* separation of duties.
* applicable Policy.
* current Resource state.

Workflow authorization must be re-evaluated when the protected Action executes.

Earlier authorization must not be treated as permanent.

---

## Observability

The platform should measure:

* Workflow Instances started.
* completion rate.
* failure rate.
* cancellation rate.
* average execution duration.
* active Workflow count.
* waiting Workflow count.
* blocked Workflow count.
* overdue Tasks.
* approval delay.
* Step failure rate.
* retry rate.
* compensation rate.

Workflow monitoring must preserve Organization isolation.

---

## Auditability

The following Workflow activities are auditable:

* Definition creation.
* Version activation.
* Workflow start.
* Step start and completion.
* Task assignment.
* Task completion.
* approval request.
* approval decision.
* Action request.
* Action result.
* retry.
* timeout.
* cancellation.
* manual intervention.
* compensation.
* completion.
* failure.

Audit records should identify:

* Organization.
* Workflow Definition.
* Workflow Version.
* Workflow Instance.
* Step.
* actor or process.
* action.
* time.
* reason.
* result.
* applicable Policy.

---

## Capability Interactions

| Capability                       | Relationship                                        |
| -------------------------------- | --------------------------------------------------- |
| Organization Management          | Provides Organization ownership                     |
| Application Management           | Provides Application ownership and scope            |
| Environment Management           | Provides Environment restrictions                   |
| Event Processing                 | Supplies Workflow triggers and evidence             |
| Alert Management                 | Participates in Alert response and escalation       |
| Incident Management              | Participates in Incident coordination               |
| Knowledge Management             | Participates in review and publication              |
| Agent Platform                   | Executes governed Agent Steps                       |
| Plugin Platform                  | Executes governed external actions                  |
| Notification Platform            | Delivers Workflow communication                     |
| Search                           | Resolves authorized operational context             |
| Authentication and Authorization | Protects Workflow operations                        |
| Administration                   | Configures Workflow governance and limits           |
| Observability                    | Measures Workflow health                            |
| Public API                       | Starts and queries authorized Workflows             |
| Policy Layer                     | Governs approvals, branching, and protected Actions |
| Audit                            | Preserves Workflow execution history                |

Operational Workflows remain authoritative for Workflow Definitions, Versions, Instances, Steps, Tasks, and Approval coordination.

Each participating capability remains authoritative for its own Resources and business operations.

---

## Business Rules

* Every Workflow Definition belongs to one Organization or is explicitly platform-provided.
* Every Workflow Instance belongs to exactly one Organization.
* Active Workflow Versions are immutable.
* Existing Workflow Instances retain their original Version.
* Workflow state is not domain state.
* Workflows must not access Core persistence directly.
* Capability operations must use stable contracts.
* The owning capability performs final authorization and validation.
* Protected Actions require applicable approval.
* Approval does not replace execution-time authorization.
* Human, System, Agent, and Plugin identities must remain distinct.
* Evidence remains owned by its source capability.
* Duplicate triggers must not create duplicate effects.
* Non-idempotent operations must not be retried unsafely.
* Workflow loops must be controlled.
* Failed and cancelled history must remain available.
* Compensation is a business Action, not a database rollback.
* Compensation failure must remain visible.
* Manual intervention must be authorized and auditable.
* Workflow completion requires verification of required outcomes.
* Cross-Organization Workflow access is prohibited.
* When execution safety cannot be determined, the Step or Workflow is blocked or failed.

---

## Success Criteria

Operational Workflows are successful when:

* Repeated operational processes are consistent.
* Human and automated work can be coordinated.
* ownership and Tasks remain clear.
* approvals protect sensitive operations.
* Workflow decisions remain explainable.
* duplicate execution is prevented.
* failures and timeouts remain visible.
* manual intervention is possible.
* compensation can be coordinated safely.
* every Step remains attributable.
* domain capabilities remain authoritative.
* Organization isolation is preserved.
* Workflow history supports investigation and audit.

---

## Related Documents

* `docs/functional/00-platform-overview.md`
* `docs/functional/05-event-processing.md`
* `docs/functional/06-alert-management.md`
* `docs/functional/07-incident-management.md`
* `docs/functional/08-knowledge-management.md`
* `docs/functional/09-agent-platform.md`
* `docs/functional/10-plugin-platform.md`
* `docs/functional/11-notification-platform.md`
* `docs/functional/12-search.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/functional/14-administration.md`
* `docs/functional/15-observability.md`
* `docs/functional/16-public-api.md`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/07-agent-architecture.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-004-operational-processing-model.md`
* `docs/adr/ADR-006-agent-platform.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-010-component-based-architecture.md`
