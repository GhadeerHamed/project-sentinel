# Incidents API

## Purpose

This document defines the API for managing operational Incidents.

An Incident coordinates investigation, ownership, communication, mitigation, and resolution for a significant operational problem.

---

## Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "incident",
  "attributes": {
    "title": "Checkout service disruption",
    "status": "investigating",
    "severity": "high",
    "summary": "Checkout requests are failing.",
    "declared_at": "2026-08-01T16:30:00Z",
    "version": 6
  }
}
```

Supported statuses:

* `declared`
* `investigating`
* `mitigating`
* `monitoring`
* `resolved`
* `closed`
* `cancelled`

---

## Endpoints

| Method  | Endpoint                                                            | Purpose                 |
| ------- | ------------------------------------------------------------------- | ----------------------- |
| `GET`   | `/organizations/{organization_id}/incidents`                        | List Incidents          |
| `POST`  | `/organizations/{organization_id}/incidents`                        | Declare an Incident     |
| `GET`   | `/organizations/{organization_id}/incidents/{incident_id}`          | Read an Incident        |
| `PATCH` | `/organizations/{organization_id}/incidents/{incident_id}`          | Update permitted fields |
| `POST`  | `/organizations/{organization_id}/incidents/{incident_id}/assign`   | Assign an Incident Role |
| `POST`  | `/organizations/{organization_id}/incidents/{incident_id}/mitigate` | Record mitigation       |
| `POST`  | `/organizations/{organization_id}/incidents/{incident_id}/monitor`  | Move to monitoring      |
| `POST`  | `/organizations/{organization_id}/incidents/{incident_id}/resolve`  | Resolve the Incident    |
| `POST`  | `/organizations/{organization_id}/incidents/{incident_id}/close`    | Close the Incident      |
| `POST`  | `/organizations/{organization_id}/incidents/{incident_id}/reopen`   | Reopen the Incident     |
| `POST`  | `/organizations/{organization_id}/incidents/{incident_id}/timeline` | Add a timeline entry    |

---

## List Incidents

```http
GET /api/v1/organizations/{organization_id}/incidents
```

Supported filters:

* `project_id`
* `application_id`
* `environment_id`
* `status`
* `severity`
* `owner_id`
* `declared_from`
* `declared_to`

Default ordering:

```text
declared_at DESC, id DESC
```

Cursor pagination is required.

---

## Declare Incident

```http
POST /api/v1/organizations/{organization_id}/incidents
Idempotency-Key: {key}
```

Request:

```json
{
  "title": "Checkout service disruption",
  "severity": "high",
  "summary": "Checkout requests are failing.",
  "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
  "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
  "source_alert_ids": [
    "0198f3c1-d98d-7bd2-af76-9d8d224ea829"
  ]
}
```

Response:

```http
201 Created
```

Source Alerts and Resources must belong to the same Organization.

---

## Read Incident

```http
GET /api/v1/organizations/{organization_id}/incidents/{incident_id}
```

Authorized responses may include:

* Roles and assignees.
* related Alerts.
* affected Applications and Environments.
* timeline entries.
* evidence references.
* active Workflows.
* Agent findings.
* resolution details.

Sensitive evidence remains independently authorized.

---

## Update Incident

```http
PATCH /api/v1/organizations/{organization_id}/incidents/{incident_id}
If-Match: "6"
```

Request:

```json
{
  "title": "Checkout and payment disruption",
  "severity": "critical"
}
```

Direct updates should be limited to approved descriptive fields.

Lifecycle changes must use explicit command endpoints.

---

## Assign Incident Role

```http
POST /api/v1/organizations/{organization_id}/incidents/{incident_id}/assign
If-Match: "6"
```

Request:

```json
{
  "role": "incident_commander",
  "assignee_type": "membership",
  "assignee_id": "0198f3c1-d98d-7bd2-af76-9d8d224ea829"
}
```

Possible roles:

* `incident_commander`
* `technical_lead`
* `communications_lead`
* `responder`
* `observer`

Assignments cannot cross Organization boundaries.

---

## Record Mitigation

```http
POST /api/v1/organizations/{organization_id}/incidents/{incident_id}/mitigate
If-Match: "7"
Idempotency-Key: {key}
```

Request:

```json
{
  "summary": "Rolled back the latest deployment.",
  "evidence_ids": [
    "0198f3c4-572c-72bd-91e5-bcc0b36f92ab"
  ]
}
```

Mitigation records the action taken and may move the Incident to `mitigating`.

---

## Move to Monitoring

```http
POST /api/v1/organizations/{organization_id}/incidents/{incident_id}/monitor
If-Match: "8"
```

Request:

```json
{
  "reason": "Error rate returned to normal."
}
```

Monitoring indicates that mitigation is complete but recovery is still being verified.

---

## Resolve Incident

```http
POST /api/v1/organizations/{organization_id}/incidents/{incident_id}/resolve
If-Match: "9"
Idempotency-Key: {key}
```

Request:

```json
{
  "resolution_summary": "Rollback restored checkout availability.",
  "resolved_at": "2026-08-01T18:10:00Z"
}
```

Resolution must preserve:

* Resolver identity.
* resolution summary.
* relevant evidence.
* lifecycle history.

---

## Close Incident

```http
POST /api/v1/organizations/{organization_id}/incidents/{incident_id}/close
If-Match: "10"
```

Closure may require:

* Completed resolution.
* required timeline entries.
* assigned owner.
* Post-Incident Review status.
* no blocking Workflow Tasks.

Closing does not delete the Incident.

---

## Reopen Incident

```http
POST /api/v1/organizations/{organization_id}/incidents/{incident_id}/reopen
If-Match: "11"
```

Request:

```json
{
  "reason": "The failure condition returned."
}
```

Reopening preserves previous resolution and closure history.

---

## Add Timeline Entry

```http
POST /api/v1/organizations/{organization_id}/incidents/{incident_id}/timeline
Idempotency-Key: {key}
```

Request:

```json
{
  "entry_type": "observation",
  "summary": "Checkout error rate dropped below 1%.",
  "occurred_at": "2026-08-01T18:00:00Z"
}
```

Timeline entries are append-only.

Possible entry types:

* `observation`
* `action`
* `decision`
* `status_change`
* `communication`
* `system_event`

---

## Authorization

Typical permissions:

```text
incident.read
incident.declare
incident.update
incident.assign
incident.mitigate
incident.resolve
incident.close
incident.reopen
incident.timeline.create
```

Authorization may depend on:

* Project or Application scope.
* Incident Role.
* severity.
* Environment.
* current lifecycle state.

---

## Errors

Common errors:

```text
incident.not_found
incident.invalid_transition
incident.role_invalid
incident.assignee_invalid
incident.required_evidence_missing
incident.close_requirements_not_met
alert.not_found
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Incident belongs to one Organization.
* Source Alerts and evidence must belong to that Organization.
* Lifecycle changes use explicit commands.
* Mutable operations use optimistic concurrency.
* Retryable commands use idempotency.
* Timeline entries are append-only.
* Role assignments remain attributable.
* Resolution does not delete operational history.
* Closure requirements are evaluated by domain Policy.
* Cross-Organization relationships are prohibited.

---

## Related Documents

* `docs/api/11-alerts-api.md`
* `docs/api/13-knowledge-api.md`
* `docs/database/07-incident-schema.md`
* `docs/database/08-knowledge-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/functional/08-incident-management.md`
