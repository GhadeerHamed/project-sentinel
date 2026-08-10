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
* `archived`

---

## Endpoints

| Method  | Endpoint                                                            | Purpose              |
| ------- | ------------------------------------------------------------------- | -------------------- |
| `GET`   | `/organizations/{organization_id}/projects`                         | List Projects        |
| `POST`  | `/organizations/{organization_id}/projects`                         | Create a Project     |
| `GET`   | `/organizations/{organization_id}/projects/{project_id}`            | Read a Project       |
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

Results are cursor-paginated. `limit` accepts values from `1` through `100`; an opaque `cursor` continues the same filter set. Responses include `pagination.next_cursor` when another page exists.

Active Projects are returned by default. Authorized callers may request `status=archived` for historical list access.

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

The caller must have an active Organization Membership, a resolved Tenant Context, and `project.create` permission.

Response:

```http
201 Created
```

The Project belongs to the resolved Organization context. The server assigns its UUIDv7 identifier, active status, creator identity, timestamps, version `1`, and `archived_at = null`; creation does not use `If-Match`.

Idempotency is scoped by Organization, acting identity, operation, key, and normalized request hash. A matching retry returns the original result; reuse with a different request returns `409 Conflict`.

Successful creation publishes `project.created`, records its Outbox message transactionally, and records Audit action `project.create`.

---

## Read Project

```http
GET /api/v1/organizations/{organization_id}/projects/{project_id}
```

Both `organization_id` and `project_id` must be used for lookup and authorization. Archived Projects remain available to authorized historical reads.

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

The request requires a quoted `If-Match` version. A successful archive returns `204 No Content` with the next quoted `ETag`; a stale version returns `412 Precondition Failed`.

---

## Deferred Project Capabilities

Project profile and metadata updates, slug changes, settings and defaults, ownership records, suspension, reactivation, deletion, and restoration are future capabilities. They have no current MVP endpoint or permission contract.

---

## Authorization

Typical permissions:

```text
project.read
project.create
project.archive
```

Project membership and Team-scoped authorization are deferred.

---

## Errors

Common errors:

```text
project.not_found
project.slug_conflict
project.archived
organization.suspended
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Project belongs to exactly one Organization.
* Project lookup always includes Organization scope.
* Project slugs are unique within the Organization.
* Projects are created active; only active Projects may be archived in the MVP.
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
