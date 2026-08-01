# Workflows API

## Purpose

This document defines the API for starting, reading, and controlling Operational Workflows.

Workflow Definitions are configured separately. This API focuses on runtime execution.

---

## Workflow Instance

```json
{
  "id": "0198f3d2-973d-7365-ad09-baf69f18258c",
  "type": "workflow_instance",
  "attributes": {
    "workflow_definition_id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
    "status": "running",
    "current_step_key": "investigate",
    "trigger_resource_type": "incident",
    "trigger_resource_id": "0198f3d1-8caf-7fea-a18b-32acd02b4fa0",
    "started_at": "2026-08-01T16:30:00Z",
    "version": 4
  }
}
```

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

---

## Endpoints

| Method | Endpoint                                                                    | Purpose                     |
| ------ | --------------------------------------------------------------------------- | --------------------------- |
| `GET`  | `/organizations/{organization_id}/workflow-definitions`                     | List available Workflows    |
| `GET`  | `/organizations/{organization_id}/workflow-definitions/{workflow_id}`       | Read a Workflow Definition  |
| `POST` | `/organizations/{organization_id}/workflow-definitions/{workflow_id}/start` | Start a Workflow            |
| `GET`  | `/organizations/{organization_id}/workflow-instances`                       | List Workflow Instances     |
| `GET`  | `/organizations/{organization_id}/workflow-instances/{instance_id}`         | Read an Instance            |
| `POST` | `/organizations/{organization_id}/workflow-instances/{instance_id}/cancel`  | Cancel an Instance          |
| `POST` | `/organizations/{organization_id}/workflow-instances/{instance_id}/retry`   | Retry eligible failed work  |
| `POST` | `/organizations/{organization_id}/workflow-tasks/{task_id}/complete`        | Complete a manual Task      |
| `POST` | `/organizations/{organization_id}/workflow-approvals/{approval_id}/decide`  | Approve or reject a request |

---

## Start Workflow

```http
POST /api/v1/organizations/{organization_id}/workflow-definitions/{workflow_id}/start
Idempotency-Key: {key}
```

Request:

```json
{
  "trigger": {
    "resource_type": "incident",
    "resource_id": "0198f3d1-8caf-7fea-a18b-32acd02b4fa0"
  },
  "input": {
    "objective": "Investigate and coordinate recovery."
  }
}
```

Response:

```http
202 Accepted
```

The response references the created Workflow Instance.

The Workflow Definition, trigger Resource, and input references must belong to the same Organization.

---

## List Instances

```http
GET /api/v1/organizations/{organization_id}/workflow-instances
```

Supported filters:

* `workflow_definition_id`
* `status`
* `trigger_resource_type`
* `trigger_resource_id`
* `started_from`
* `started_to`

Default ordering:

```text
created_at DESC, id DESC
```

---

## Read Instance

```http
GET /api/v1/organizations/{organization_id}/workflow-instances/{instance_id}
```

The response may include:

* Current Step.
* completed Steps.
* open Tasks.
* pending approvals.
* related Actions.
* Agent or Plugin execution references.
* Notification references.
* lifecycle history.

Referenced Resources remain independently authorized.

---

## Complete Task

```http
POST /api/v1/organizations/{organization_id}/workflow-tasks/{task_id}/complete
If-Match: "2"
Idempotency-Key: {key}
```

Request:

```json
{
  "completion_note": "Rollback completed and health checks passed.",
  "output": {
    "deployment_id": "deploy-1842"
  }
}
```

Completing a Task records human work.

It does not prove that an external or protected Action succeeded unless the owning capability confirms it.

---

## Decide Approval

```http
POST /api/v1/organizations/{organization_id}/workflow-approvals/{approval_id}/decide
Idempotency-Key: {key}
```

Request:

```json
{
  "decision": "approved",
  "reason": "Rollback is the safest available action."
}
```

Supported decisions:

* `approved`
* `rejected`

Approval decisions are immutable.

A protected Action must still pass execution-time Authorization.

---

## Retry Workflow

```http
POST /api/v1/organizations/{organization_id}/workflow-instances/{instance_id}/retry
If-Match: "4"
Idempotency-Key: {key}
```

Retry is allowed only when:

* The failed Step is retryable.
* attempt limits remain.
* the Workflow has not expired.
* required approval remains valid.
* the Action is idempotent or safely compensatable.

Completed non-idempotent Actions must not be repeated.

---

## Cancel Workflow

```http
POST /api/v1/organizations/{organization_id}/workflow-instances/{instance_id}/cancel
If-Match: "4"
```

Request:

```json
{
  "reason": "The Incident was cancelled."
}
```

Cancellation stops eligible pending work.

It does not automatically reverse completed Actions.

Rollback requires a separate governed Action or compensation Workflow.

---

## Authorization

Typical permissions:

```text
workflow.read
workflow.start
workflow.cancel
workflow.retry
workflow.task.complete
workflow.approval.decide
```

Authorization may also depend on:

* Workflow Definition.
* trigger Resource.
* Task assignment.
* approval assignment.
* Environment.
* Action risk.
* current Workflow state.

---

## Errors

Common errors:

```text
workflow.not_found
workflow.instance_not_found
workflow.not_active
workflow.invalid_input
workflow.invalid_state
workflow.task_not_found
workflow.task_not_assigned
workflow.approval_not_found
workflow.approval_expired
workflow.retry_not_allowed
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Workflow Instance belongs to one Organization.
* Every Instance records the exact Workflow Version used.
* Starting a Workflow uses idempotency.
* Runtime state changes use optimistic concurrency.
* Steps follow transitions defined by the Workflow Version.
* Approvals are immutable.
* Approval does not replace execution-time Authorization.
* Completed non-idempotent Actions cannot be repeated.
* Cancellation does not automatically undo completed Actions.
* Agent, Plugin, Notification, and domain Actions remain owned by their respective capabilities.
* Cross-Organization context and execution are prohibited.

---

## Related Documents

* `docs/api/14-agents-api.md`
* `docs/api/15-plugins-api.md`
* `docs/api/16-notifications-api.md`
* `docs/api/19-webhooks.md`
* `docs/database/12-workflow-schema.md`
* `docs/functional/17-operational-workflows.md`
