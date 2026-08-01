# Projects API

## Purpose

This document defines the API for managing Projects within an Organization.

A Project groups related Applications, Environments, operational data, and access scope.

---

## Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "project",
  "attributes": {
    "name": "Commerce Platform",
    "slug": "commerce-platform",
    "description": "Customer-facing commerce services.",
    "status": "active",
    "version": 2,
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

| Method  | Endpoint                                                            | Purpose              |
| ------- | ------------------------------------------------------------------- | -------------------- |
| `GET`   | `/organizations/{organization_id}/projects`                         | List Projects        |
| `POST`  | `/organizations/{organization_id}/projects`                         | Create a Project     |
| `GET`   | `/organizations/{organization_id}/projects/{project_id}`            | Read a Project       |
| `PATCH` | `/organizations/{organization_id}/projects/{project_id}`            | Update a Project     |
| `POST`  | `/organizations/{organization_id}/projects/{project_id}/suspend`    | Suspend a Project    |
| `POST`  | `/organizations/{organization_id}/projects/{project_id}/reactivate` | Reactivate a Project |
| `POST`  | `/organizations/{organization_id}/projects/{project_id}/archive`    | Archive a Project    |

---

## List Projects

```http
GET /api/v1/organizations/{organization_id}/projects
```

Supported filters:

* `status`
* `name`
* `slug`

Default ordering:

```text
created_at DESC, id DESC
```

---

## Create Project

```http
POST /api/v1/organizations/{organization_id}/projects
Idempotency-Key: {key}
```

Request:

```json
{
  "name": "Commerce Platform",
  "slug": "commerce-platform",
  "description": "Customer-facing commerce services."
}
```

Response:

```http
201 Created
```

The Project belongs to the Organization in the route.

---

## Read Project

```http
GET /api/v1/organizations/{organization_id}/projects/{project_id}
```

Both `organization_id` and `project_id` must be used for lookup and authorization.

---

## Update Project

```http
PATCH /api/v1/organizations/{organization_id}/projects/{project_id}
If-Match: "2"
```

Request:

```json
{
  "name": "Commerce Operations",
  "description": "Operational scope for commerce services."
}
```

Mutable fields:

* `name`
* `description`
* approved metadata

Slug changes should be restricted or handled through a dedicated command.

---

## Suspend Project

```http
POST /api/v1/organizations/{organization_id}/projects/{project_id}/suspend
If-Match: "2"
```

Request:

```json
{
  "reason": "Project temporarily inactive"
}
```

Suspension may prevent:

* New ingestion.
* new Agent or Plugin executions.
* new Workflow execution.
* configuration changes.

Historical data remains available according to authorization and retention rules.

---

## Reactivate Project

```http
POST /api/v1/organizations/{organization_id}/projects/{project_id}/reactivate
If-Match: "3"
```

Request:

```json
{
  "reason": "Project restored"
}
```

Reactivation requires the Organization to be Active.

---

## Archive Project

```http
POST /api/v1/organizations/{organization_id}/projects/{project_id}/archive
If-Match: "4"
```

Archival should:

* Prevent normal new activity.
* preserve Applications and historical data.
* exclude the Project from default active listings.
* create an Audit Record.

Archival is not deletion.

---

## Authorization

Typical permissions:

```text
project.read
project.create
project.update
project.suspend
project.reactivate
project.archive
```

Access may also be restricted by Project membership or Team scope.

---

## Errors

Common errors:

```text
project.not_found
project.slug_conflict
project.suspended
project.archived
project.has_active_dependencies
organization.suspended
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Project belongs to exactly one Organization.
* Project lookup always includes Organization scope.
* Project slugs are unique within the Organization.
* Suspended or Archived Projects cannot accept normal new activity.
* Archival preserves historical data.
* Lifecycle changes use optimistic concurrency.
* Creation uses idempotency.
* Sensitive lifecycle changes are audited.
* Cross-Organization relationships are prohibited.

---

## Related Documents

* `docs/api/00-api-overview.md`
* `docs/api/06-organizations-api.md`
* `docs/api/08-applications-api.md`
* `docs/database/03-organization-project-schema.md`
* `docs/database/04-application-environment-schema.md`
