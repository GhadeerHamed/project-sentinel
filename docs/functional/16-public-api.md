# Public API

## Purpose

The Public API provides a stable and governed interface through which authorized external systems interact with Project Sentinel.

It allows customer applications, command-line tools, automation platforms, deployment pipelines, and partner integrations to use selected Sentinel capabilities without depending on internal frameworks, services, or database structures.

The Public API is an external product boundary.

It must preserve:

* Organization isolation.
* Authentication.
* Authorization.
* Domain business rules.
* Policy enforcement.
* Contract stability.
* Auditability.

---

## Objectives

The Public API enables Sentinel to:

* Expose supported platform capabilities externally.
* Support human-operated and machine-operated clients.
* Provide consistent Resource representations.
* Authenticate and authorize protected operations.
* Support safe request retries.
* Support asynchronous operations.
* Enforce Rate Limits and Quotas.
* Support API version evolution.
* Deliver selected platform events through Webhooks.
* Preserve request traceability.
* Prevent external consumers from bypassing domain rules.

---

## Responsibilities

The Public API is responsible for:

* Receiving external requests.
* Resolving the API Client and Organization.
* Authenticating requests.
* Authorizing operations.
* Validating request structure.
* Mapping requests to business capabilities.
* Returning consistent responses.
* Supporting pagination and filtering.
* Supporting idempotent operations.
* Coordinating asynchronous API Jobs.
* Enforcing limits and Quotas.
* Managing API Versions.
* Managing Webhook Subscriptions.
* Preserving API access history.

---

## Functional Boundaries

The Public API does not:

* Own domain Resources.
* Own domain lifecycle decisions.
* bypass Authentication and Authorization.
* bypass Organization Policies.
* bypass business rules.
* expose internal persistence structures.
* expose internal service topology.
* expose credentials or secrets.
* permit cross-Organization access.
* guarantee synchronous completion of long-running operations.
* replace Plugin contracts or Notification delivery.

Each business capability remains authoritative for its own Resources and operations.

---

## Position in the Platform

```text
External API Client

↓

Public API

↓

Authentication and Authorization

↓

Validation and Policy Evaluation

↓

Business Capability

↓

Business Result

↓

Public API Response
```

---

## Core Concepts

| Concept                | Description                                    |
| ---------------------- | ---------------------------------------------- |
| API Client             | A registered programmatic consumer             |
| API Credential         | Authentication material used by an API Client  |
| API Request            | One requested operation                        |
| API Response           | The public result of an API Request            |
| API Resource           | A public representation of a Sentinel Resource |
| API Scope              | A broad category of permitted access           |
| API Version            | A defined public contract version              |
| Idempotency Key        | A key preventing duplicate effects             |
| Correlation Identifier | An identifier connecting related processing    |
| API Job                | An asynchronous operation                      |
| Webhook Subscription   | A request to receive selected platform events  |

---

## API Clients

Every Organization-specific API Client belongs to exactly one Organization.

An API Client defines:

* Name.
* owner.
* purpose.
* authentication method.
* API Scopes.
* Resource Scope.
* lifecycle status.

API Clients may represent:

* Customer applications.
* command-line tools.
* automation systems.
* deployment pipelines.
* reporting tools.
* partner integrations.
* internal enterprise services.

API Clients must use non-human credentials and remain independently attributable.

---

## API Client Lifecycle

API Clients follow this lifecycle:

```text
Registered

↓

Active

↓

Suspended

↓

Disabled

↓

Archived
```

A Suspended, Disabled, or Archived API Client cannot perform protected operations.

Historical requests remain attributed to the API Client that submitted them.

---

## Authentication

Every protected API Request must be authenticated.

Supported methods may include:

* API Keys.
* OAuth Access Tokens.
* service credentials.
* signed requests.
* client certificates.
* workload identity.

Authentication is owned by Authentication and Authorization.

Authentication success does not grant access by itself.

---

## Authorization

Every protected operation must be authorized.

Authorization evaluates:

* API Client identity.
* Organization.
* credential status.
* API Scope.
* required permission.
* target Resource.
* Resource ownership.
* Resource Scope.
* Environment.
* sensitivity.
* Organization Policy.
* entitlement.

When authorization cannot be evaluated safely, the operation is denied.

---

## API Scopes

API Scopes may include:

* Event ingestion.
* Event read.
* Alert read.
* Alert management.
* Incident read.
* Incident management.
* Knowledge read.
* Knowledge management.
* Agent execution.
* Plugin management.
* Notification management.
* Search.
* Workflow participation.
* Administration.
* Audit read.

API Scopes provide broad access categories.

They do not replace fine-grained permissions or Resource Scope.

---

## Resource Scope

An API Client may be limited to:

* One Organization.
* selected Projects.
* selected Applications.
* selected Environments.
* selected Services.
* selected Plugin Installations.
* selected Agent configurations.
* selected Workflows.

Resource Scope is evaluated for every protected request.

---

## Public Resources

The Public API may expose selected representations of:

* Organizations.
* Projects.
* Applications.
* Environments.
* Events.
* Signals.
* Alerts.
* Incidents.
* Knowledge Entries.
* Agents and Agent Executions.
* Plugin Installations.
* Notifications.
* Search Results.
* API Jobs.
* Operational Workflows.
* selected administrative or Observability records.

Not every internal entity must have a public API representation.

---

## Resource Representation

A public Resource representation should expose only fields required by the external contract.

It may include:

* Public identifier.
* Resource Type.
* status.
* relevant business attributes.
* Resource relationships.
* creation time.
* update time.
* version information where required.

Internal storage identifiers and implementation details must remain hidden.

Possession of a Resource identifier does not grant access.

---

## Supported Operation Categories

The Public API may support:

| Operation   | Purpose                                       |
| ----------- | --------------------------------------------- |
| Create      | Create a governed Resource                    |
| Retrieve    | Retrieve one authorized Resource              |
| List        | Retrieve an authorized collection             |
| Update      | Modify permitted fields                       |
| Transition  | Request a lifecycle transition                |
| Archive     | Remove a Resource from active use             |
| Search      | Discover authorized Resources                 |
| Execute     | Start an Agent, Plugin, or Workflow operation |
| Export      | Request a governed data export                |
| Acknowledge | Confirm awareness or responsibility           |

The owning capability determines whether an operation is valid.

---

## Request Processing

Every protected request follows this sequence:

```text
Request Received

↓

API Version Resolved

↓

Authentication Evaluated

↓

Authorization Evaluated

↓

Request Validated

↓

Business Capability Invoked

↓

Response Returned
```

Requests failing authentication, authorization, or structural validation must not invoke protected business operations.

---

## Request Validation

Validation may include:

* API Version.
* supported operation.
* request size.
* payload structure.
* required fields.
* field types.
* identifier format.
* pagination parameters.
* filters.
* time ranges.
* Idempotency Key requirements.

The owning capability performs separate business validation.

---

## Responses

API Responses must be:

* Consistent.
* machine-readable.
* versioned.
* bounded.
* traceable.
* clear about success or failure.
* clear about asynchronous processing.
* safe for unauthorized consumers.

Responses must not expose internal stack traces, service names, database details, or sensitive Policy information.

---

## API Errors

API Errors should distinguish:

* Invalid Request.
* Unauthenticated Request.
* Unauthorized Operation.
* Resource Not Found or Not Disclosable.
* Resource Conflict.
* Business Rule Rejection.
* Rate Limit Exceeded.
* Quota Exceeded.
* Unsupported API Version.
* Temporary Unavailability.
* Internal Failure.

Detailed error codes and transport mappings belong in `docs/api/`.

---

## Idempotency

Eligible write operations should support Idempotency Keys.

This may include:

* Event ingestion.
* Incident creation.
* Notification requests.
* Agent execution requests.
* Plugin actions.
* Workflow initiation.
* Export requests.

Repeating the same valid request with the same active key must not create duplicate business effects.

The same key must not be reused for materially different requests.

---

## Concurrency Control

Mutable Resources may require a Resource version or equivalent concurrency reference.

When the Resource changed after the Client retrieved it, the API should reject the outdated update rather than overwrite the newer state silently.

---

## Collections

Collection operations must support bounded retrieval.

They may provide:

* Pagination.
* filtering.
* sorting.

Unbounded collection requests are prohibited.

Large historical retrieval should use Search or Export operations.

---

## Bulk Operations

Selected capabilities may support Bulk Operations.

Bulk behavior must define:

* Maximum batch size.
* authorization behavior.
* validation behavior.
* partial-success behavior.
* idempotency.
* asynchronous processing threshold.

Individual failures must remain visible.

---

## Asynchronous Operations

Long-running operations should use API Jobs.

Examples include:

* Large exports.
* bulk imports.
* Search Jobs.
* Agent analysis.
* Plugin synchronization.
* migration validation.

API Jobs may have these states:

```text
Requested

↓

Queued

↓

Running

↓

Completed
```

A Job may also become:

* Rejected.
* Failed.
* Cancelled.
* Expired.

---

## Webhooks

Organizations may create Webhook Subscriptions to receive selected platform events.

Supported events may include:

* Alert changes.
* Incident changes.
* Knowledge publication.
* Agent Execution completion.
* Plugin Health changes.
* Workflow task changes.
* API Job completion.

Webhook delivery must support:

* Endpoint verification.
* signed payloads.
* replay protection.
* secret rotation.
* secure transport.
* controlled retries.
* versioned payloads.

Webhook delivery failure must not reverse the source business event.

---

## API Versioning

The Public API is versioned independently from internal deployments.

An API Version defines:

* Public Resource representations.
* operation semantics.
* request and response fields.
* error behavior.
* pagination behavior.
* Webhook payloads.

Version statuses may include:

* Preview.
* Current.
* Supported.
* Deprecated.
* Retired.

Breaking changes require a new Version or a governed migration path.

---

## Deprecation

API deprecation must define:

* Affected Version or operation.
* replacement.
* migration guidance.
* deprecation date.
* planned retirement date.

Security or platform-safety issues may require accelerated restriction or retirement.

---

## Rate Limits and Quotas

Rate Limits protect short-term platform capacity and tenant fairness.

Quotas enforce longer-term entitlement or usage allowances.

They may apply by:

* Organization.
* API Client.
* operation category.
* time window.
* subscription plan.

Rate Limiting and Quota exhaustion must remain distinguishable.

---

## Correlation and Audit

Every API Request should receive a Correlation Identifier.

Selected operations must be auditable, including:

* Authentication.
* authorization denial.
* Resource creation.
* lifecycle transitions.
* Agent execution.
* Plugin actions.
* exports.
* administrative operations.
* API Client and credential changes.
* Webhook configuration.

Audit records should identify:

* Organization.
* API Client.
* API Version.
* operation.
* Resource.
* result.
* Correlation Identifier.
* time.

---

## Security Requirements

The Public API must:

* Preserve Organization isolation.
* follow deny-by-default authorization.
* minimize returned data.
* protect sensitive fields.
* prevent Resource enumeration.
* never return reusable secrets after protected one-time disclosure.
* reject unsafe or indeterminate operations.
* preserve Client attribution.
* prevent cross-Organization access through identifiers, filters, Jobs, Webhooks, or pagination.

---

## Capability Interactions

| Capability                       | Relationship                             |
| -------------------------------- | ---------------------------------------- |
| Organization Management          | Provides Organization ownership          |
| Project Management               | Provides Project scope                   |
| Application Management           | Provides Application Resources           |
| Environment Management           | Provides Environment restrictions        |
| Event Processing                 | Accepts and exposes Event operations     |
| Alert Management                 | Exposes Alert operations                 |
| Incident Management              | Exposes Incident operations              |
| Knowledge Management             | Exposes governed Knowledge               |
| Agent Platform                   | Exposes Agent execution                  |
| Plugin Platform                  | Exposes Plugin management and actions    |
| Notification Platform            | Delivers Webhooks and Notifications      |
| Search                           | Executes Search Requests                 |
| Authentication and Authorization | Authenticates and authorizes API Clients |
| Administration                   | Governs privileged API operations        |
| Observability                    | Measures API health and usage            |
| Operational Workflows            | Exposes Workflow operations              |
| Subscription Management          | Supplies Quotas and Entitlements         |
| Policy Layer                     | Governs external access                  |
| Audit                            | Preserves API activity                   |

The Public API owns external contracts and request-processing conventions.

Each business capability remains authoritative for its domain Resources and lifecycle.

---

## Business Rules

* Every protected request must be authenticated.
* Every protected operation must be authorized.
* Every API Client belongs to one Organization.
* API Scopes do not override fine-grained authorization.
* Resource identifiers do not grant access.
* Public contracts must not expose internal implementation details.
* Client input must be validated.
* Domain rules cannot be bypassed through the API.
* Eligible write operations should support idempotency.
* Long-running operations should use API Jobs.
* Pagination must be bounded.
* Partial success must remain explicit.
* API Versions must have explicit support status.
* Breaking changes require versioning or governed migration.
* Webhook payloads must be signed and versioned.
* Secrets must remain protected.
* Cross-Organization access is prohibited.
* API failures must not corrupt authoritative business state.
* Unauthorized operations are rejected and auditable.

---

## Success Criteria

The Public API is successful when:

* External systems integrate without depending on Sentinel internals.
* Organization isolation is preserved.
* Authentication and Authorization protect every operation.
* Domain business rules cannot be bypassed.
* API Clients can retry eligible requests safely.
* Long-running operations remain traceable.
* API Versions evolve predictably.
* Webhooks provide secure business-event delivery.
* Rate Limits and Quotas protect platform stability.
* API activity remains attributable and auditable.
* Public contracts remain independent from frameworks and persistence technologies.

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
* `docs/functional/17-operational-workflows.md`
* `docs/api/`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-009-core-is-framework-agnostic.md`
