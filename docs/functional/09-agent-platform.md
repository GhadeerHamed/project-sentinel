# Agent Platform

## Purpose

The Agent Platform enables governed AI-assisted operational analysis and action within Project Sentinel.

Agents may help users and platform capabilities:

* Analyze Events, Alerts, and Incidents.
* Retrieve relevant Knowledge.
* Identify patterns and relationships.
* Produce Findings.
* Propose Recommendations.
* Summarize operational context.
* Assist with investigation and response.
* Participate in approved operational workflows.

Agents are governed platform actors.

They must operate through explicit identities, permissions, scopes, Policies, and evidence boundaries.

Agent output is not automatically authoritative.

Findings and Recommendations must remain explainable, traceable, and subject to appropriate review.

---

## Objectives

The Agent Platform enables Sentinel to:

* Define reusable operational Agents.
* Execute Agents within Organization boundaries.
* Provide authorized operational context.
* Support multiple AI providers and models.
* Preserve evidence used during reasoning.
* Produce structured Findings and Recommendations.
* Communicate confidence and uncertainty.
* Require approval for high-impact actions.
* Prevent unrestricted data access.
* Control cost, latency, and provider usage.
* Preserve Agent execution history.
* Collect feedback and outcome evidence.
* Support future automation safely.
* Remain independent from one AI provider or model.

---

## Responsibilities

The Agent Platform is responsible for:

* Managing Agent Definitions.
* Managing Agent Versions.
* Managing Agent Identities.
* Validating Agent Configuration.
* Resolving Execution Context.
* Authorizing Context Access.
* Selecting Approved Providers and Models.
* Managing Agent Executions.
* Recording Inputs and Evidence References.
* Validating Structured Outputs.
* Managing Findings and Recommendations.
* Applying Agent Policies.
* Managing Execution Limits.
* Managing Approval Requirements.
* Recording Cost and Usage.
* Collecting Feedback.
* Publishing Agent Lifecycle Events.
* Preserving Execution History.

---

## Functional Boundaries

The Agent Platform does not:

* Own Events, Alerts, Incidents, or Knowledge.
* Bypass Authentication and Authorization.
* Bypass Organization Policies.
* Treat generated statements as source evidence.
* Execute protected actions without authorization.
* Replace human accountability.
* Publish Knowledge automatically without review.
* Modify historical evidence.
* Guarantee correct diagnosis.
* Guarantee deterministic output.
* Permit cross-Organization context access.
* Expose provider credentials.
* Depend permanently on one provider or model.

The owning capability remains authoritative for every Resource affected by an Agent.

---

## Position in the Platform

```text
Authorized Operational Context

↓

Agent Execution

↓

Analysis

↓

Findings and Recommendations

↓

Human or Workflow Review

↓

Approved Operational Action
```

Agents assist operational decision-making.

They do not independently redefine authoritative platform state.

---

## Core Concepts

| Concept            | Description                                            |
| ------------------ | ------------------------------------------------------ |
| Agent              | A governed AI-assisted operational capability          |
| Agent Definition   | The logical purpose and behavior of an Agent           |
| Agent Version      | An immutable version of an Agent Definition            |
| Agent Identity     | The non-human identity used by an Agent                |
| Agent Execution    | One governed invocation of an Agent Version            |
| Execution Context  | The authorized information supplied to an Agent        |
| Evidence Reference | A reference to authoritative supporting information    |
| Finding            | A supported conclusion produced by an Agent            |
| Recommendation     | A proposed operational action or decision              |
| Confidence         | The Agent’s assessed certainty                         |
| Provider           | An external or internal AI execution provider          |
| Model              | The configured model used for an execution             |
| Tool               | An approved capability available to an Agent           |
| Agent Policy       | Rules governing execution, context, output, and action |
| Human Approval     | A required decision before protected action            |
| Feedback           | Evaluation of Agent usefulness or correctness          |

---

## Agent Ownership

Every Agent Definition belongs to exactly one Organization or is an explicitly governed platform-provided Agent.

Organization ownership establishes:

* Tenant isolation.
* Configuration ownership.
* Permission scope.
* Policy scope.
* Cost ownership.
* Audit ownership.
* Retention responsibility.
* Provider restrictions.

Organization-specific Agent data must not be shared with another Organization.

---

## Agent Types

Agents may support operational purposes such as:

| Agent Type           | Purpose                                          |
| -------------------- | ------------------------------------------------ |
| Triage Agent         | Summarizes and classifies operational concerns   |
| Investigation Agent  | Analyzes evidence and proposes hypotheses        |
| Correlation Agent    | Identifies related Events, Alerts, and Incidents |
| Knowledge Agent      | Retrieves and summarizes relevant Knowledge      |
| Incident Assistant   | Supports Incident coordination and updates       |
| Remediation Advisor  | Proposes governed remediation options            |
| Review Agent         | Assists with Post-Incident or Knowledge review   |
| Administrative Agent | Assists with controlled platform administration  |
| Workflow Agent       | Participates in approved operational workflows   |

An Agent Definition should have one primary operational purpose.

---

## Agent Definition

An Agent Definition describes the intended behavior of an Agent.

It should define:

* Name.
* Purpose.
* Organization.
* Agent Type.
* Owner.
* Allowed Input Types.
* Allowed Context Sources.
* Allowed Tools.
* Expected Output Structure.
* Provider Restrictions.
* Model Requirements.
* Permission Requirements.
* Sensitivity Limits.
* Execution Limits.
* Approval Requirements.
* Lifecycle Status.

An Agent Definition is configuration, not an execution record.

---

## Agent Versioning

Material changes create a new immutable Agent Version.

Material changes may include:

* Instructions.
* Expected Outputs.
* Provider Selection Rules.
* Model Requirements.
* Tool Availability.
* Context Permissions.
* Safety Constraints.
* Confidence Behavior.
* Approval Requirements.
* Data-Handling Restrictions.

Historical Executions must retain the exact Agent Version used.

---

## Agent Lifecycle

Agent Definitions follow a controlled lifecycle.

```text
Draft

↓

Validated

↓

Active

↓

Suspended

↓

Deprecated

↓

Archived
```

An Agent may also become Rejected during validation or approval.

Only Active Agents may begin normal Executions.

---

## Draft Agents

A Draft Agent may be configured and tested without general operational availability.

Draft configuration may include:

* Instructions.
* Context Requirements.
* Output Schema.
* Provider Options.
* Tool Permissions.
* Test Scenarios.
* Safety Conditions.
* Cost Limits.
* Approval Requirements.

Draft Agents must not execute against protected production context unless an approved test Policy permits it.

---

## Agent Validation

Validation confirms that an Agent is safe and structurally usable.

Validation may evaluate:

* Required Configuration.
* Valid Output Schema.
* Permission Scope.
* Context Boundaries.
* Tool Restrictions.
* Provider Eligibility.
* Model Eligibility.
* Sensitive-Data Handling.
* Cost Limits.
* Timeout Limits.
* Approval Requirements.
* Test Results.

Validation does not guarantee that every future output will be correct.

---

## Active Agents

An Active Agent may execute when:

* Its Version is valid.
* Its Agent Identity is active.
* The initiating identity is authorized.
* Required context is accessible.
* Provider and model are permitted.
* Applicable Policies allow execution.
* Usage limits permit execution.

Active status does not grant unrestricted data or action access.

---

## Suspended Agents

An Agent may be Suspended because of:

* Unsafe output.
* Policy violation.
* Provider failure.
* Excessive cost.
* Repeated execution failure.
* Security concern.
* Data-handling concern.
* Administrative review.
* Model deprecation.

A Suspended Agent cannot begin new normal Executions.

Existing Executions may be cancelled or allowed to complete according to risk.

---

## Deprecated Agents

A Deprecated Agent remains historically available but should not be selected for new use.

Deprecation should identify:

* Reason.
* replacement Agent where available.
* migration guidance.
* deprecation time.
* planned archival time.

---

## Agent Identity

Every Agent executes through a distinct non-human Agent Identity.

Agent Identity establishes:

* Organization.
* Agent Definition.
* Agent Version.
* permissions.
* Resource Scope.
* Tool access.
* sensitivity access.
* provider restrictions.
* audit attribution.

Agents must not execute using a human Member’s identity.

The initiating User and acting Agent must both remain attributable.

---

## Agent Permissions

Agent permissions should be narrowly scoped.

They may permit:

* Reading selected Events.
* reading selected Alerts.
* reading selected Incidents.
* retrieving Published Knowledge.
* using Search.
* creating Findings.
* proposing Recommendations.
* drafting status updates.
* requesting approved Workflow actions.

Permission to analyze a Resource does not imply permission to modify it.

---

## Agent Tools

Tools are approved capabilities an Agent may invoke during execution.

Examples include:

* Search.
* Knowledge retrieval.
* Event retrieval.
* Alert retrieval.
* Incident retrieval.
* Observability queries.
* Plugin actions.
* workflow requests.
* controlled calculation.
* approved external lookup.

Every Tool invocation must be:

* Authorized.
* scoped.
* attributable.
* bounded.
* auditable where required.

Tool availability is defined by the Agent Version and current Policy.

---

## Execution Request

An Agent Execution Request should define:

* Agent.
* initiating identity or process.
* Organization.
* purpose.
* target Resource.
* requested scope.
* input.
* required output.
* urgency.
* applicable Policy.
* Idempotency reference where appropriate.

The request must not grant access beyond the Agent’s existing permissions.

---

## Agent Execution Lifecycle

Agent Executions follow a controlled lifecycle.

```text
Requested

↓

Authorized

↓

Queued

↓

Running

↓

Completed
```

An Execution may also become:

* Rejected.
* Failed.
* Cancelled.
* Timed Out.
* Blocked.
* Expired.

Every terminal state requires an explainable outcome.

---

## Requested Executions

An Execution is Requested when an authorized identity or capability asks an Agent to perform work.

A Request does not guarantee execution.

The platform must still evaluate:

* Agent status.
* initiator authorization.
* context access.
* Policy.
* provider availability.
* limits.
* required approval.

---

## Authorized Executions

An Execution becomes Authorized when the platform confirms that:

* The Agent may execute.
* the initiating identity may request it.
* the target Resource belongs to the Organization.
* context access is permitted.
* provider use is permitted.
* expected cost is acceptable.
* required pre-execution approval exists.

Authorization must be re-evaluated when material context changes before execution begins.

---

## Running Executions

While Running, an Agent may:

* Retrieve authorized context.
* invoke approved Tools.
* produce intermediate analysis.
* create structured output.
* record provider usage.
* record evidence references.
* report progress where meaningful.

Running Executions must remain bounded by timeout, context, Tool, and cost limits.

---

## Completed Executions

An Execution becomes Completed when:

* Required processing finished.
* output validation succeeded.
* Findings and Recommendations were recorded.
* evidence references were preserved.
* usage and cost were recorded.
* final status was produced.

Completed does not mean the output was accepted or correct.

---

## Failed Executions

An Execution may fail because of:

* Provider failure.
* model failure.
* timeout.
* invalid output.
* Tool failure.
* missing context.
* authorization change.
* Policy rejection.
* cost limit.
* unsafe output.
* internal processing failure.

Failure must not produce an authoritative Finding or execute a protected action silently.

---

## Execution Context

Execution Context is the authorized information supplied to an Agent.

It may include:

* Target Resource.
* Events.
* Signals.
* Alerts.
* Incidents.
* Findings.
* timelines.
* Published Knowledge.
* Observability evidence.
* Plugin information.
* configuration metadata.
* recent changes.
* permitted user-provided instructions.

Context must be limited to the execution purpose.

---

## Context Rules

* Context belongs to one Organization.
* Every context item requires authorization.
* Sensitive fields may be redacted.
* Context should be minimized.
* Context freshness should be visible.
* Source references must be preserved.
* Derived content must remain distinguishable from source evidence.
* Provider restrictions must be applied before transmission.
* Context unavailable to the Agent must not be inferred as known fact.

---

## Evidence References

Agent output should reference the evidence supporting it.

Evidence references may point to:

* Events.
* Signals.
* Alerts.
* Incidents.
* Knowledge Versions.
* Observability records.
* Plugin records.
* audit records.
* approved external sources.

An Agent-generated statement without supporting evidence must be identified as inference, hypothesis, or Recommendation.

---

## Findings

A Finding is a structured conclusion produced by an Agent.

A Finding should include:

* Statement.
* supporting evidence.
* confidence.
* affected scope.
* Agent Version.
* Execution.
* creation time.
* limitations.
* review status.

A Finding may be:

* Proposed.
* accepted.
* rejected.
* superseded.
* inconclusive.

Agent Findings do not automatically alter authoritative domain state.

---

## Recommendations

A Recommendation proposes an operational action or decision.

A Recommendation should include:

* Proposed action.
* rationale.
* supporting evidence.
* expected outcome.
* confidence.
* affected scope.
* risk.
* required permission.
* required approval.
* rollback considerations.
* expiration where appropriate.

Recommendations must not be presented as completed actions.

---

## Recommendation Lifecycle

Recommendations may follow:

```text
Proposed

↓

Under Review

↓

Accepted

↓

Authorized

↓

Executed

↓

Verified
```

A Recommendation may also become:

* Rejected.
* Expired.
* Cancelled.
* Failed.
* Superseded.

The owning business capability or Workflow remains authoritative for action execution.

---

## Confidence

Confidence communicates the Agent’s assessed certainty.

Confidence may be represented as:

| Confidence | Meaning                                        |
| ---------- | ---------------------------------------------- |
| High       | Strong support from relevant evidence          |
| Medium     | Reasonable support with meaningful uncertainty |
| Low        | Limited support or substantial uncertainty     |
| Unknown    | Confidence cannot be assessed reliably         |

Confidence must not be treated as probability unless explicitly defined.

---

## Uncertainty

Agent output should identify uncertainty caused by:

* Missing evidence.
* stale context.
* conflicting evidence.
* limited historical examples.
* provider limitations.
* ambiguous symptoms.
* incomplete Resource access.
* unsupported assumptions.

Uncertainty must not be hidden to make output appear more authoritative.

---

## Human Review

Human review may be required for:

* High-impact Findings.
* security-sensitive analysis.
* customer-impact conclusions.
* Knowledge publication.
* Incident severity changes.
* production remediation.
* destructive actions.
* external communication.
* privileged administrative changes.

Review requirements are governed by Agent Policy and the owning capability.

---

## Human Approval

Approval is required before an Agent Recommendation may trigger a protected action when Policy demands it.

Approval should identify:

* Recommendation.
* approving identity.
* approval time.
* approved scope.
* conditions.
* expiration.
* applicable Policy.

Approval does not bypass the action’s normal authorization checks.

---

## Automated Actions

Agents may participate in automation only through approved Operational Workflows.

Automation must define:

* Eligible Agent.
* eligible Recommendation type.
* required confidence.
* Resource Scope.
* allowed action.
* approval rules.
* rollback behavior.
* verification requirements.
* failure handling.
* audit requirements.

Direct unrestricted Agent automation is prohibited.

---

## Provider Independence

The Agent Platform must support multiple providers and models through stable platform contracts.

Provider selection may consider:

* Organization Policy.
* data sensitivity.
* model capability.
* availability.
* latency.
* cost.
* region.
* contractual restrictions.
* context limits.
* fallback configuration.

Agent Definitions should not depend unnecessarily on one provider-specific format.

---

## Provider Configuration

Provider configuration may define:

* Provider identity.
* allowed Organizations.
* supported models.
* data-handling restrictions.
* regional availability.
* cost rules.
* timeout limits.
* context limits.
* retention behavior.
* fallback eligibility.
* lifecycle status.

Provider credentials must remain protected.

---

## Model Selection

Model selection may be:

* Fixed by Agent Version.
* selected from an approved set.
* selected according to sensitivity.
* selected according to cost or latency.
* selected according to capability.
* changed through an approved fallback.

The exact model used must be recorded for every Execution.

---

## Provider Failure

When a provider is unavailable, the platform may:

* Retry safely.
* select an approved fallback.
* delay execution.
* fail the Execution.
* require manual review.
* disable the Agent temporarily.

Fallback must not violate data-handling or Organization restrictions.

---

## Output Validation

Agent output must be validated before it enters Sentinel domain workflows.

Validation may include:

* Required structure.
* allowed output type.
* evidence-reference validity.
* Resource ownership.
* field size.
* sensitivity.
* prohibited content.
* action eligibility.
* confidence presence.
* schema compatibility.

Invalid output must not become a valid Finding or Recommendation.

---

## Safety Controls

Agent safety controls may include:

* Context minimization.
* Tool allowlists.
* output validation.
* provider restrictions.
* cost limits.
* timeout limits.
* human approval.
* execution quotas.
* sensitive-field redaction.
* prompt-injection defenses.
* action verification.
* Agent suspension.
* emergency disablement.

Safety controls must remain independent from model self-reporting.

---

## Untrusted Content

Operational context may contain untrusted instructions, including:

* Log messages.
* external Event payloads.
* Plugin content.
* customer-provided text.
* imported tickets.
* external documentation.

Agents must treat such content as data, not authority.

Untrusted content must not redefine Agent permissions, Policies, Tools, or system instructions.

---

## Cost Management

Agent Executions may consume metered provider resources.

Cost controls may include:

* Per-Execution limit.
* per-Agent limit.
* Organization budget.
* monthly quota.
* context-size limit.
* output-size limit.
* model restrictions.
* approval above threshold.
* fallback to lower-cost models.
* execution cancellation.

Cost limits must not silently truncate output into a misleading result.

---

## Execution Limits

Limits may apply to:

* Context size.
* execution duration.
* Tool invocations.
* retry count.
* concurrent Executions.
* output size.
* cost.
* provider usage.
* Agent requests per period.

Limit exhaustion should produce a clear Execution outcome.

---

## Feedback

Users and platform capabilities may provide feedback on Agent output.

Feedback may include:

* Helpful.
* not helpful.
* correct.
* incorrect.
* accepted.
* rejected.
* unsafe.
* incomplete.
* unsupported.
* successful outcome.
* failed outcome.

Feedback does not alter historical output.

It may inform future Agent Versions and governance decisions.

---

## Outcome Evidence

Where a Recommendation is executed, the platform should record its outcome.

Outcome evidence may include:

* Action completed.
* expected result observed.
* mitigation succeeded.
* no improvement.
* failure.
* rollback.
* unintended effect.
* later recurrence.

Outcome evidence supports evaluation of Agent usefulness and confidence calibration.

---

## Agent Use in Alert Management

Agents may assist Alert Management by:

* Summarizing Alert evidence.
* suggesting severity.
* correlating related Alerts.
* retrieving Knowledge.
* identifying likely ownership.
* proposing Incident evaluation.
* detecting missing evidence.

Alert Management remains authoritative for Alert state.

---

## Agent Use in Incident Management

Agents may assist Incident Management by:

* Summarizing Incident timelines.
* proposing hypotheses.
* identifying related deployments.
* retrieving similar historical Incidents.
* suggesting Response Actions.
* drafting status updates.
* identifying contradictory evidence.
* assisting Post-Incident Review.

Incident Management remains authoritative for Incident lifecycle, Findings, Decisions, and Actions.

---

## Agent Use in Knowledge Management

Agents may assist Knowledge Management by:

* Drafting Knowledge from validated Incident evidence.
* summarizing source material.
* suggesting applicability.
* identifying conflicts.
* identifying stale Knowledge.
* proposing revisions.

Agent-generated Knowledge remains Draft until governed review and approval.

---

## Search and Retrieval

Agents may use Search only within their authorized scope.

Agent Search must preserve:

* Organization isolation.
* Resource authorization.
* sensitivity restrictions.
* purpose limitation.
* result traceability.
* Search freshness.
* provider restrictions.

Agents must not receive broader Search access than required by the execution purpose.

---

## Observability

The platform should measure:

* Execution count.
* success rate.
* failure rate.
* timeout rate.
* provider latency.
* Tool failure.
* output-validation failure.
* cost.
* context volume.
* Recommendation acceptance.
* Recommendation outcome.
* Finding review.
* Policy rejection.
* fallback usage.
* Agent suspension.

Observability must not expose confidential prompts, context, or output unnecessarily.

---

## Auditability

The following Agent activities are auditable:

* Agent creation.
* Version creation.
* validation.
* activation.
* suspension.
* provider change.
* permission change.
* Tool change.
* Execution request.
* authorization.
* context access.
* Tool invocation.
* Finding creation.
* Recommendation creation.
* approval.
* action request.
* cancellation.
* failure.
* feedback.
* outcome recording.

Audit records should identify:

* Organization.
* Agent.
* Agent Version.
* Execution.
* initiating identity.
* Agent Identity.
* provider and model.
* action.
* result.
* time.
* applicable Policy.

---

## Retention

Agent records are retained according to Organization, platform, security, and provider Policies.

Retention applies to:

* Agent Definitions.
* Agent Versions.
* Executions.
* context references.
* Tool activity.
* Findings.
* Recommendations.
* approvals.
* feedback.
* usage and cost.
* outcome evidence.
* audit history.

Raw provider prompts and responses should be retained only when operationally required and permitted.

---

## Capability Interactions

| Capability                       | Relationship                                       |
| -------------------------------- | -------------------------------------------------- |
| Organization Management          | Provides Organization ownership and governance     |
| Application Management           | Provides Application scope                         |
| Environment Management           | Provides Environment restrictions                  |
| Event Processing                 | Supplies Events and Signals                        |
| Alert Management                 | Receives Agent-assisted triage and correlation     |
| Incident Management              | Receives investigation and response assistance     |
| Knowledge Management             | Supplies governed Knowledge and receives Drafts    |
| Plugin Platform                  | Provides approved external Tools and context       |
| Notification Platform            | Delivers Agent-related communication               |
| Search                           | Provides authorized operational retrieval          |
| Authentication and Authorization | Establishes Agent identities and permissions       |
| Administration                   | Configures providers, limits, and Agent governance |
| Observability                    | Measures Agent health, quality, and cost           |
| Public API                       | Exposes approved Agent operations                  |
| Operational Workflows            | Governs approvals and Agent-assisted actions       |
| Policy Layer                     | Governs context, providers, Tools, and automation  |
| Audit                            | Preserves Agent activity and decisions             |

The Agent Platform remains authoritative for Agent Definitions, Versions, Executions, Findings, Recommendations, and Agent usage records.

Each business capability remains authoritative for its own Resources and lifecycle.

---

## Business Rules

* Every Organization-specific Agent belongs to exactly one Organization.
* Every Agent executes through a distinct Agent Identity.
* Agents must not impersonate human Members.
* Only Active Agent Versions may begin normal Executions.
* Historical Executions retain the exact Agent Version, provider, and model used.
* Every Execution has an explicit purpose and scope.
* Every context item requires authorization.
* Agent context must remain Organization-isolated.
* Generated content is not source evidence.
* Findings must reference supporting evidence or identify themselves as inference.
* Recommendations must remain distinct from completed actions.
* Confidence and uncertainty must remain visible.
* Agent output must pass validation.
* Untrusted context cannot redefine Agent permissions or instructions.
* Protected actions require normal authorization.
* Human approval is required where Policy demands it.
* Agents may automate actions only through governed Workflows.
* Provider fallback must preserve data-handling restrictions.
* Cost and execution limits must be enforced.
* Agent failure must not modify authoritative domain state silently.
* Agent-generated Knowledge remains Draft until reviewed.
* Suspended Agents cannot begin normal Executions.
* Cross-Organization Agent access is prohibited.
* Unauthorized Agent operations are rejected and auditable.
* When execution safety cannot be determined, the Execution is rejected or stopped.

---

## Success Criteria

The Agent Platform is successful when:

* Agents provide useful operational assistance.
* Every Agent action remains attributable.
* Agent context remains authorized and tenant-isolated.
* Findings and Recommendations remain traceable to evidence.
* Users can distinguish evidence, inference, and proposed action.
* High-impact actions require appropriate approval.
* Agent output cannot bypass domain rules.
* Multiple providers and models can be supported.
* Provider changes do not require redesigning Sentinel domains.
* Cost and usage remain controlled.
* Unsafe or unreliable Agents can be suspended rapidly.
* Feedback and outcome evidence support continuous improvement.
* Agent failures do not corrupt authoritative business state.
* Human accountability remains preserved.

---

## Related Documents

* `docs/functional/00-platform-overview.md`
* `docs/functional/05-event-processing.md`
* `docs/functional/06-alert-management.md`
* `docs/functional/07-incident-management.md`
* `docs/functional/08-knowledge-management.md`
* `docs/functional/10-plugin-platform.md`
* `docs/functional/11-notification-platform.md`
* `docs/functional/12-search.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/functional/14-administration.md`
* `docs/functional/15-observability.md`
* `docs/functional/16-public-api.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/07-agent-architecture.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-006-agent-platform.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-009-core-is-framework-agnostic.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
