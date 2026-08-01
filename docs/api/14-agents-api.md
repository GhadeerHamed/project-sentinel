# Agents API

## Purpose

This document defines the API for managing and executing AI Agents.

Agents analyze authorized operational context and may produce:

* Findings.
* recommendations.
* summaries.
* proposed Actions.

Agents do not bypass domain ownership, Authorization, approvals, or Workflow controls.

---

## Agent Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "agent",
  "attributes": {
    "name": "Incident Investigator",
    "agent_type": "incident_analysis",
    "status": "active",
    "active_version": 2,
    "version": 4,
    "created_at": "2026-08-01T16:30:00Z"
  }
}
```

Supported statuses:

* `draft`
* `active`
* `suspended`
* `deprecated`
* `archived`

---

## Endpoints

| Method  | Endpoint                                                           | Purpose               |
| ------- | ------------------------------------------------------------------ | --------------------- |
| `GET`   | `/organizations/{organization_id}/agents`                          | List Agents           |
| `POST`  | `/organizations/{organization_id}/agents`                          | Create an Agent       |
| `GET`   | `/organizations/{organization_id}/agents/{agent_id}`               | Read an Agent         |
| `PATCH` | `/organizations/{organization_id}/agents/{agent_id}`               | Update Agent metadata |
| `POST`  | `/organizations/{organization_id}/agents/{agent_id}/versions`      | Create a Version      |
| `POST`  | `/organizations/{organization_id}/agents/{agent_id}/activate`      | Activate a Version    |
| `POST`  | `/organizations/{organization_id}/agents/{agent_id}/suspend`       | Suspend an Agent      |
| `POST`  | `/organizations/{organization_id}/agents/{agent_id}/execute`       | Request execution     |
| `GET`   | `/organizations/{organization_id}/agent-executions`                | List executions       |
| `GET`   | `/organizations/{organization_id}/agent-executions/{execution_id}` | Read execution result |

---

## List Agents

```http
GET /api/v1/organizations/{organization_id}/agents
```

Supported filters:

* `agent_type`
* `status`
* `application_id`
* `environment_id`

---

## Create Agent

```http
POST /api/v1/organizations/{organization_id}/agents
Idempotency-Key: {key}
```

Request:

```json
{
  "name": "Incident Investigator",
  "agent_type": "incident_analysis",
  "description": "Analyzes Incident evidence and proposes likely causes."
}
```

New Agents begin in `draft`.

---

## Create Agent Version

```http
POST /api/v1/organizations/{organization_id}/agents/{agent_id}/versions
If-Match: "4"
Idempotency-Key: {key}
```

Request:

```json
{
  "model_configuration": {
    "provider": "configured-provider",
    "model": "configured-model"
  },
  "instructions": "Analyze the supplied Incident evidence.",
  "input_schema": {
    "type": "object"
  },
  "capabilities": [
    "incident.read",
    "event.read",
    "knowledge.read"
  ]
}
```

Agent Versions are immutable after creation.

Secret values must not appear in Agent configuration.

---

## Activate Version

```http
POST /api/v1/organizations/{organization_id}/agents/{agent_id}/activate
If-Match: "5"
```

Request:

```json
{
  "version_id": "0198f3ca-9e18-71ab-9d98-17f6d76d38a1"
}
```

Activation requires:

* Valid configuration.
* approved capabilities.
* valid provider configuration.
* completed security checks.

Only the Active Version is used for new executions.

---

## Suspend Agent

```http
POST /api/v1/organizations/{organization_id}/agents/{agent_id}/suspend
If-Match: "6"
```

Request:

```json
{
  "reason": "Execution quality review"
}
```

Suspension prevents new executions but preserves execution history.

---

## Execute Agent

```http
POST /api/v1/organizations/{organization_id}/agents/{agent_id}/execute
Idempotency-Key: {key}
```

Request:

```json
{
  "target": {
    "resource_type": "incident",
    "resource_id": "0198f3d1-8caf-7fea-a18b-32acd02b4fa0"
  },
  "objective": "Identify likely causes and recommend investigation steps."
}
```

Response:

```http
202 Accepted
```

```json
{
  "data": {
    "id": "0198f3d2-973d-7365-ad09-baf69f18258c",
    "type": "agent_execution",
    "attributes": {
      "status": "queued"
    }
  }
}
```

---

## Execution Resource

```json
{
  "id": "0198f3d2-973d-7365-ad09-baf69f18258c",
  "type": "agent_execution",
  "attributes": {
    "agent_id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
    "agent_version_id": "0198f3ca-9e18-71ab-9d98-17f6d76d38a1",
    "status": "completed",
    "requested_at": "2026-08-01T16:30:00Z",
    "completed_at": "2026-08-01T16:30:08Z"
  },
  "relationships": {
    "findings": [],
    "recommendations": []
  }
}
```

Supported execution statuses:

* `queued`
* `running`
* `completed`
* `failed`
* `cancelled`
* `timed_out`
* `rejected`

---

## Execution Results

Agent results may include:

* Findings.
* recommendations.
* confidence indicators.
* evidence references.
* proposed Workflow Actions.
* safe execution summary.

Results must preserve:

* Agent Version.
* source Resources.
* evidence used.
* execution identity.
* timestamps.
* provider metadata required for traceability.

Raw provider responses should not be exposed by default.

---

## Proposed Actions

An Agent may propose an Action but cannot directly execute protected changes unless explicitly authorized.

A proposed Action should normally be sent to:

* A User for review.
* an approval Step.
* a governed Workflow.
* the owning domain capability.

Approval does not replace execution-time Authorization.

---

## Authorization

Typical permissions:

```text
agent.read
agent.create
agent.update
agent.activate
agent.suspend
agent.execute
agent.execution.read
agent.recommendation.approve
```

Execution Authorization must evaluate:

* Agent capability grants.
* target Resource access.
* Organization scope.
* Environment restrictions.
* current Agent status.
* execution Policy.

---

## Errors

Common errors:

```text
agent.not_found
agent.version_not_found
agent.not_active
agent.configuration_invalid
agent.capability_not_granted
agent.execution_not_allowed
agent.provider_unavailable
agent.execution_failed
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Agent belongs to one Organization.
* Every execution records the exact Agent Version used.
* Agent Versions are immutable.
* Agents receive only explicitly granted capabilities.
* Agent context must remain Organization-scoped.
* Agent output is treated as untrusted input.
* Agents cannot bypass Authorization or domain ownership.
* Protected Actions require normal approval and execution controls.
* Suspended Agents cannot start new executions.
* Execution requests use idempotency.
* Agent lifecycle changes use optimistic concurrency.
* Cross-Organization context and Actions are prohibited.

---

## Related Documents

* `docs/api/13-knowledge-api.md`
* `docs/api/15-plugins-api.md`
* `docs/api/18-workflows-api.md`
* `docs/database/09-agent-schema.md`
* `docs/architecture/07-agent-architecture.md`
* `docs/adr/ADR-006-agent-platform.md`
