# Alerts API

## Purpose

This document defines the API for reading and managing Alerts.

An Alert represents an actionable operational condition detected from one or more Signals.

---

## Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "alert",
  "attributes": {
    "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
    "environment_id": "0198f3b5-f5dc-7f37-884d-83223d29b991",
    "title": "Checkout failure rate increased",
    "status": "open",
    "severity": "high",
    "priority": "high",
    "occurrence_count": 12,
    "first_observed_at": "2026-08-01T16:30:00Z",
    "last_observed_at": "2026-08-01T16:35:00Z",
    "version": 4
  }
}
```

Supported statuses:

* `open`
* `acknowledged`
* `investigating`
* `suppressed`
* `resolved`
* `closed`

---

## Endpoints

| Method | Endpoint                                                              | Purpose                 |
| ------ | --------------------------------------------------------------------- | ----------------------- |
| `GET`  | `/organizations/{organization_id}/alerts`                             | List Alerts             |
| `GET`  | `/organizations/{organization_id}/alerts/{alert_id}`                  | Read an Alert           |
| `POST` | `/organizations/{organization_id}/alerts/{alert_id}/acknowledge`      | Acknowledge an Alert    |
| `POST` | `/organizations/{organization_id}/alerts/{alert_id}/assign`           | Assign ownership        |
| `POST` | `/organizations/{organization_id}/alerts/{alert_id}/suppress`         | Suppress an Alert       |
| `POST` | `/organizations/{organization_id}/alerts/{alert_id}/resolve`          | Resolve an Alert        |
| `POST` | `/organizations/{organization_id}/alerts/{alert_id}/reopen`           | Reopen a resolved Alert |
| `POST` | `/organizations/{organization_id}/alerts/{alert_id}/declare-incident` | Create an Incident      |

Alerts are normally created by Sentinel processing rather than directly through the public API.

---

## List Alerts

```http
GET /api/v1/organizations/{organization_id}/alerts
```

Supported filters:

* `project_id`
* `application_id`
* `environment_id`
* `status`
* `severity`
* `priority`
* `assignee_id`
* `observed_from`
* `observed_to`

Default ordering:

```text
last_observed_at DESC, id DESC
```

Cursor pagination is required.

---

## Read Alert

```http
GET /api/v1/organizations/{organization_id}/alerts/{alert_id}
```

Authorized responses may include references to:

* Source Signals.
* evidence.
* assignments.
* related Incidents.
* lifecycle history.

Sensitive evidence must remain permission-filtered.

---

## Acknowledge Alert

```http
POST /api/v1/organizations/{organization_id}/alerts/{alert_id}/acknowledge
If-Match: "4"
Idempotency-Key: {key}
```

Request:

```json
{
  "note": "Investigating the checkout service."
}
```

Acknowledgement records that a responder has seen and accepted responsibility for the Alert.

---

## Assign Alert

```http
POST /api/v1/organizations/{organization_id}/alerts/{alert_id}/assign
If-Match: "5"
```

Request:

```json
{
  "assignee_type": "membership",
  "assignee_id": "0198f3c1-d98d-7bd2-af76-9d8d224ea829"
}
```

Supported assignee types:

* `membership`
* `team`

The assignee must belong to the same Organization.

---

## Suppress Alert

```http
POST /api/v1/organizations/{organization_id}/alerts/{alert_id}/suppress
If-Match: "5"
```

Request:

```json
{
  "reason": "Expected during scheduled maintenance.",
  "until": "2026-08-01T20:00:00Z"
}
```

Suppression reduces normal escalation or notification activity.

It does not delete the Alert or its evidence.

---

## Resolve Alert

```http
POST /api/v1/organizations/{organization_id}/alerts/{alert_id}/resolve
If-Match: "6"
Idempotency-Key: {key}
```

Request:

```json
{
  "resolution": "Failure rate returned to normal after rollback."
}
```

Resolution requires the current lifecycle state to permit it.

---

## Reopen Alert

```http
POST /api/v1/organizations/{organization_id}/alerts/{alert_id}/reopen
If-Match: "7"
```

Request:

```json
{
  "reason": "The failure condition returned."
}
```

Reopening preserves previous resolution history.

---

## Declare Incident

```http
POST /api/v1/organizations/{organization_id}/alerts/{alert_id}/declare-incident
If-Match: "6"
Idempotency-Key: {key}
```

Request:

```json
{
  "title": "Checkout service disruption",
  "severity": "high"
}
```

Response:

```http
201 Created
```

The created Incident must reference the source Alert.

The Alert remains an authoritative Alert Resource.

---

## Authorization

Typical permissions:

```text
alert.read
alert.acknowledge
alert.assign
alert.suppress
alert.resolve
alert.reopen
incident.declare
```

Authorization may also depend on:

* Project.
* Application.
* Environment.
* Team scope.
* severity.
* current Alert state.

---

## Errors

Common errors:

```text
alert.not_found
alert.invalid_transition
alert.already_acknowledged
alert.assignee_invalid
alert.suppression_invalid
alert.incident_already_declared
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Alert belongs to one Organization.
* Alerts are normally created from Signals.
* Alert lookup always includes Organization scope.
* Lifecycle commands require optimistic concurrency.
* Retryable commands use idempotency.
* Assignments cannot cross Organization boundaries.
* Suppression preserves history and evidence.
* Resolution does not delete the Alert.
* Declaring an Incident preserves the Alert as a separate Resource.
* All lifecycle changes are recorded in Alert history.
* Cross-Organization access is prohibited.

---

## Related Documents

* `docs/api/10-events-api.md`
* `docs/api/12-incidents-api.md`
* `docs/database/06-alert-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/functional/07-alert-management.md`
