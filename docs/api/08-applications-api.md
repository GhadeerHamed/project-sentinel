# Applications API

## Purpose

This document defines the API for managing Applications within a Project.

An Application represents a deployable or operational software system monitored by Sentinel.

---

## Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "application",
  "attributes": {
    "project_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
    "name": "Checkout API",
    "slug": "checkout-api",
    "description": "Processes checkout requests.",
    "status": "active",
    "version": 3,
    "created_at": "2026-08-01T16:30:00Z",
    "updated_at": "2026-08-01T17:00:00Z"
  }
}
```

Supported statuses:

* `active`
* `suspended`
* `archived`

---

## Endpoints

| Method  | Endpoint                                                                    | Purpose                   |
| ------- | --------------------------------------------------------------------------- | ------------------------- |
| `GET`   | `/organizations/{organization_id}/applications`                             | List Applications         |
| `POST`  | `/organizations/{organization_id}/projects/{project_id}/applications`       | Create an Application     |
| `GET`   | `/organizations/{organization_id}/applications/{application_id}`            | Read an Application       |
| `PATCH` | `/organizations/{organization_id}/applications/{application_id}`            | Update an Application     |
| `POST`  | `/organizations/{organization_id}/applications/{application_id}/suspend`    | Suspend an Application    |
| `POST`  | `/organizations/{organization_id}/applications/{application_id}/reactivate` | Reactivate an Application |
| `POST`  | `/organizations/{organization_id}/applications/{application_id}/archive`    | Archive an Application    |

---

## List Applications

```http
GET /api/v1/organizations/{organization_id}/applications
```

Supported filters:

* `project_id`
* `status`
* `name`
* `slug`

Default ordering:

```text
created_at DESC, id DESC
```

---

## Create Application

```http
POST /api/v1/organizations/{organization_id}/projects/{project_id}/applications
Idempotency-Key: {key}
```

Request:

```json
{
  "name": "Checkout API",
  "slug": "checkout-api",
  "description": "Processes checkout requests."
}
```

Response:

```http
201 Created
```

The Project and Organization must match.

---

## Read Application

```http
GET /api/v1/organizations/{organization_id}/applications/{application_id}
```

The lookup must include both:

```text
organization_id
application_id
```

---

## Update Application

```http
PATCH /api/v1/organizations/{organization_id}/applications/{application_id}
If-Match: "3"
```

Request:

```json
{
  "name": "Checkout Service",
  "description": "Handles checkout orchestration."
}
```

Mutable fields:

* `name`
* `description`
* approved metadata

Changing Project ownership should require a dedicated operation, if supported.

---

## Suspend Application

```http
POST /api/v1/organizations/{organization_id}/applications/{application_id}/suspend
If-Match: "3"
```

Request:

```json
{
  "reason": "Service retired temporarily"
}
```

Suspension may prevent:

* New Event ingestion.
* new Agent or Plugin executions.
* new Workflow execution.
* configuration changes.

Historical data remains retained.

---

## Reactivate Application

```http
POST /api/v1/organizations/{organization_id}/applications/{application_id}/reactivate
If-Match: "4"
```

The parent Organization and Project must both be Active.

---

## Archive Application

```http
POST /api/v1/organizations/{organization_id}/applications/{application_id}/archive
If-Match: "5"
```

Archival:

* Stops normal new activity.
* preserves Environments and operational history.
* removes the Application from default active listings.
* does not delete data.

---

## Authorization

Typical permissions:

```text
application.read
application.create
application.update
application.suspend
application.reactivate
application.archive
```

Authorization may also depend on Project or Team scope.

---

## Errors

Common errors:

```text
application.not_found
application.slug_conflict
application.suspended
application.archived
project.not_found
project.suspended
organization.suspended
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Application belongs to one Project and Organization.
* Application lookup always includes Organization scope.
* Application slugs are unique within their Project.
* Parent Organization and Project state may restrict Application operations.
* Suspended or Archived Applications cannot accept normal new activity.
* Archival preserves operational history.
* Creation uses idempotency.
* Lifecycle changes use optimistic concurrency.
* Cross-Organization relationships are prohibited.

---

## Related Documents

* `docs/api/06-organizations-api.md`
* `docs/api/07-projects-api.md`
* `docs/api/09-environments-api.md`
* `docs/database/04-application-environment-schema.md`
