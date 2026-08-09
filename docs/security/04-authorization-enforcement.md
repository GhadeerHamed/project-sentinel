# Authorization Enforcement

## Purpose

This document defines how Project Sentinel authorizes protected operations.

Authentication establishes identity.

Authorization decides whether that identity may perform a specific operation on a specific Resource.

---

## Core Rule

Sentinel uses:

```text
Deny by default
```

An operation is allowed only when an applicable Policy explicitly permits it.

---

## Authorization Context

Every authorization decision should receive:

```text
identity_id
identity_type
organization_id
permission
resource_type
resource_id
resource_state
resource_scope
authentication_strength
request_id
correlation_id
```

Additional context may include:

* Project.
* Application.
* Environment.
* Agent or Plugin capability grants.
* approval reference.
* Workflow Instance.
* privileged-access session.

---

## Enforcement Order

Protected operations should follow this order:

1. Authenticate the caller.
2. resolve and validate Organization context.
3. load the Resource within that Organization.
4. evaluate required permission and Policy.
5. validate lifecycle and business rules.
6. execute the operation.
7. record required Audit data.

Authorization must occur before sensitive Resource details are returned.

---

## Permission Model

Permissions use stable capability-oriented names.

Examples:

```text
incident.read
incident.resolve
knowledge.publish
plugin.install
agent.execute
workflow.approval.decide
organization.suspend
```

Permissions describe allowed business operations, not controller or database actions.

---

## Scope

Permission alone may be insufficient.

Authorization may also require access to:

* A specific Project.
* Application.
* Environment.
* Team.
* Incident.
* sensitivity level.
* production scope.

Example:

```text
A User may have incident.read,
but only for Applications assigned to their Team.
```

---

## Resource Ownership

Every tenant Resource must belong to the active Organization.

Authorization must never rely on:

* Resource ID alone.
* route ownership assumptions.
* UI visibility.
* hidden fields.
* cache presence.

Cross-Organization Resources must be treated as unavailable.

---

## Roles

Roles group permissions for administration and assignment.

Examples:

* Organization Owner.
* Organization Administrator.
* Project Operator.
* Incident Commander.
* Responder.
* Viewer.

Domain logic should evaluate permissions and scope rather than depend directly on Role names.

---

## Administrative Recovery

An active Organization must retain an active human Membership with effective `organization.member.manage`. Administrative-access-reducing mutations are serialized by Organization inside their transaction and rejected with a conflict when their committed result would remove that final recovery capability. This invariant is capability-based and separate from ownership semantics.

---

## Non-Human Identities

API Clients, Services, Agents, and Plugins receive explicit permissions.

They must not automatically inherit the permissions of:

* Their creator.
* the initiating User.
* the Organization Owner.
* another Installation or Agent.

Each non-human identity must remain independently revocable.

---

## Agent and Plugin Capabilities

Agent and Plugin permissions are limited by their approved capability grants.

Example:

```text
Declared capability
∩ Organization grant
∩ Resource scope
∩ runtime Policy
= effective permission
```

Agents and Plugins cannot grant themselves additional access.

---

## Workflow Authorization

A Workflow coordinates operations but does not bypass normal authorization.

Protected Workflow Actions require:

* Valid Workflow configuration.
* required approval where applicable.
* authorized execution identity.
* current Resource state.
* execution-time Policy evaluation.

Approval alone does not grant permanent permission.

---

## Background Processing

Authorization requirements apply equally to:

* HTTP requests.
* queue jobs.
* scheduled tasks.
* event consumers.
* retries.
* Workflow Steps.
* Agent Tool calls.
* Plugin callbacks.

Background execution must restore the original or approved execution identity and Tenant Context.

---

## Read Authorization

Authorization applies to:

* Resource responses.
* collection results.
* counts.
* facets.
* Search suggestions.
* relationship previews.
* file downloads.
* exports.

Metadata must not reveal unauthorized Resource existence.

---

## Field-Level Authorization

Some Resources may be visible while specific fields remain restricted.

Examples:

* Sensitive evidence.
* credential metadata.
* private Incident notes.
* Agent provider data.
* security findings.

Response mapping must remove unauthorized fields before serialization.

---

## Authorization Caching

Authorization decisions may be cached only when the cache key includes relevant context, such as:

```text
organization_id
identity_id
permission
resource_scope
authorization_version
```

Caches must be invalidated promptly after:

* Role changes.
* Membership suspension.
* capability revocation.
* Policy changes.
* privileged-access expiration.

High-risk Actions should use fresh evaluation.

---

## Failure Behavior

Authorization failures return:

```http
403 Forbidden
```

Sentinel may return:

```http
404 Not Found
```

when revealing Resource existence would expose protected information.

Errors must not disclose internal Policy rules unnecessarily.

---

## Audit Requirements

Audit Records should be created for:

* Privileged authorization decisions.
* denied high-risk Actions.
* capability grants and revocations.
* Role and permission changes.
* approval-controlled execution.
* privileged-access use.
* tenant-isolation violations.

Routine low-risk reads may use operational access telemetry unless Policy requires full Audit.

---

## Testing Requirements

Authorization tests must cover:

* Allowed operations.
* denied operations.
* cross-Organization access.
* inactive Memberships.
* revoked permissions.
* Resource scope restrictions.
* production Environment restrictions.
* Agent and Plugin capability limits.
* expired approvals.
* background-job enforcement.
* field-level filtering.

Every new protected operation requires authorization tests.

---

## Prohibited Practices

The following are prohibited:

* Authorization only in controllers or UI.
* loading Resources before validating Organization scope.
* trusting Resource identifiers.
* Role-name checks inside domain logic.
* inheriting creator permissions for non-human identities.
* skipping authorization in background jobs.
* treating approval as permanent authorization.
* returning unauthorized counts or fields.
* relying on network location as permission.

---

## Business Rules

* Authorization is deny-by-default.
* Every protected operation declares a required permission.
* Organization ownership is validated before Resource access.
* Permissions may be restricted by Resource scope and state.
* Human and non-human identities are authorized independently.
* Agent, Plugin, and Workflow operations use runtime Policy evaluation.
* Approval does not replace execution-time authorization.
* Authorization applies to synchronous and asynchronous execution.
* Sensitive fields require field-level authorization.
* High-risk authorization decisions are auditable.

---

## Related Documents

* `docs/security/00-security-overview.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/03-authentication-security.md`
* `docs/security/05-secrets-credentials.md`
* `docs/api/02-authentication.md`
* `docs/database/02-identity-access-schema.md`
* `docs/adr/ADR-008-security-policy-layer.md`
