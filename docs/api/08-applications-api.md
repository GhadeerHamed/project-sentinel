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
    "criticality": "high",
    "status": "active",
    "version": 3,
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

| Method  | Endpoint                                                                    | Purpose                   |
| ------- | --------------------------------------------------------------------------- | ------------------------- |
| `POST`  | `/organizations/{organization}/projects/{project}/applications`             | Create an Application     |
| `GET`   | `/organizations/{organization}/projects/{project}/applications`             | List Applications         |
| `GET`   | `/organizations/{organization}/projects/{project}/applications/{application}` | Read an Application     |
| `POST`  | `/organizations/{organization}/projects/{project}/applications/{application}/archive` | Archive an Application |

---

## List Applications

```http
GET /api/v1/organizations/{organization}/projects/{project}/applications
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

Active Applications are returned by default. Authorized callers may request `status=archived` for historical list access.

---

## Create Application

```http
POST /api/v1/organizations/{organization}/projects/{project}/applications
Idempotency-Key: {key}
```

Request:

```json
{
  "name": "Checkout API",
  "slug": "checkout-api",
  "description": "Processes checkout requests.",
  "criticality": "high"
}
```

Response:

```http
201 Created
```

The caller must have an active Organization Membership, resolved Tenant Context, and `application.create` permission. The Project must be active and belong to the resolved Organization. `criticality` is required and must be one of `critical`, `high`, `medium`, or `low`; no default is applied.

The server assigns the Application UUIDv7 identifier, active status, creator identity, timestamps, version `1`, and `archived_at = null`. The request does not accept Organization or Project ownership, status, archival metadata, creator identity, or version fields, and creation does not use `If-Match`.

Idempotency is scoped by Organization, acting identity, operation, key, and normalized request hash. A matching retry returns the original result; reuse with a different request returns `409 Conflict`.

Successful creation publishes `application.created`, records its Outbox message transactionally, and records Audit action `application.create`.

The `201 Created` response returns the canonical Application resource, including `project_id`, `name`, `slug`, `description`, `criticality`, `status`, `version`, `created_at`, and `updated_at`. It does not expose creator or archival metadata.

---

## Read Application

```http
GET /api/v1/organizations/{organization}/projects/{project}/applications/{application}
```

The lookup must include:

```text
organization_id
project_id
application_id
```

The persisted Application Project relationship must also belong to that Organization. Archived Applications and their archived Project parents remain available to authorized historical reads.

---

## Archive Application

```http
POST /api/v1/organizations/{organization}/projects/{project}/applications/{application}/archive
If-Match: "5"
```

Archival:

* Stops normal new activity.
* preserves Environments and operational history.
* removes the Application from default active listings.
* does not delete data.

The request requires a quoted `If-Match` version. A successful archive returns `204 No Content` with the next quoted `ETag`; a stale version returns `412 Precondition Failed`.

---

## Deferred Application Capabilities

Application updates, including criticality mutation, settings, runtime/framework metadata, repository/source metadata, suspension, reactivation, deletion, and restoration are future capabilities. They have no current MVP endpoint or permission contract. Any future Project ownership change requires a governed migration process.

---

## Authorization

Typical permissions:

```text
application.read
application.create
application.archive
```

Project and Team-scoped authorization extensions are deferred.

---

## Errors

Common errors:

```text
application.not_found
application.slug_conflict
application.archived
project.not_found
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
* Archived Applications cannot accept normal new activity.
* Archival preserves operational history.
* Creation uses idempotency.
* Lifecycle changes use optimistic concurrency.
* Cross-Organization relationships are prohibited.
* Suspension, reactivation, provisioning, restricted, deletion, and restoration are deferred beyond the MVP.

---

## Related Documents

* `docs/api/06-organizations-api.md`
* `docs/api/07-projects-api.md`
* `docs/api/09-environments-api.md`
* `docs/database/04-application-environment-schema.md`
