# Environments API

## Purpose

This document defines the MVP API for managing Environments within an Application.

---

## Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "environment",
  "attributes": {
    "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01",
    "name": "Production",
    "slug": "production",
    "environment_type": "production",
    "status": "active",
    "version": 1,
    "created_at": "2026-08-01T16:30:00Z",
    "updated_at": "2026-08-01T16:30:00Z"
  }
}
```

Supported `environment_type` values are `production`, `staging`, `testing`, `development`, `sandbox`, and `other`. The only MVP statuses are `active` and `archived`.

---

## Endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/organizations/{organization}/projects/{project}/applications/{application}/environments` | List Environments |
| `POST` | `/organizations/{organization}/projects/{project}/applications/{application}/environments` | Create an Environment |
| `GET` | `/organizations/{organization}/projects/{project}/applications/{application}/environments/{environment}` | Read an Environment |
| `POST` | `/organizations/{organization}/projects/{project}/applications/{application}/environments/{environment}/archive` | Archive an Environment |

Every route validates the Organization → Project → Application → Environment relationship. Cross-Organization and cross-Project Resources are tenant-safe not-found results.

---

## List Environments

```http
GET /api/v1/organizations/{organization}/projects/{project}/applications/{application}/environments
```

Supported filters are `status`, `environment_type`, and `slug`. The canonical ordering is `created_at DESC, id DESC`.

Active Environments are returned by default. Authorized callers may request `status=archived` for historical list access.

---

## Create Environment

```http
POST /api/v1/organizations/{organization}/projects/{project}/applications/{application}/environments
Idempotency-Key: {key}
```

Request:

```json
{
  "name": "Production",
  "slug": "production",
  "environment_type": "production"
}
```

The caller requires an active Organization Membership, resolved Tenant Context, and `environment.create`. The Project and Application must be active, belong to the resolved Organization, and the Application must belong to the route Project.

The server assigns the UUIDv7 identifier, Organization and Application ownership, active status, creator identity, timestamps, `archived_at = null`, and version `1`. The request does not accept ownership, status, archival metadata, creator identity, or version fields. Creation does not use `If-Match`.

Idempotency is scoped by Organization, acting identity, operation, key, and normalized request hash. A matching retry returns the original result; reuse with a different request returns `409 Conflict`.

Successful creation atomically persists the Environment, publishes `environment.created` through the Outbox, records Audit action `environment.create`, and returns `201 Created` with the canonical Environment resource. Creator and archival metadata are not exposed by the response.

---

## Read Environment

```http
GET /api/v1/organizations/{organization}/projects/{project}/applications/{application}/environments/{environment}
```

The lookup is scoped by Organization, Application, and Environment, with the Application additionally verified against the route Project. Archived Environments remain available to authorized callers for historical access.

---

## Archive Environment

```http
POST /api/v1/organizations/{organization}/projects/{project}/applications/{application}/environments/{environment}/archive
If-Match: "1"
```

Only `active → archived` is supported. Archival preserves operational history, removes the Environment from default active lists, publishes `environment.archived`, records Audit action `environment.archive`, and returns the updated quoted ETag. Stale versions return the canonical concurrency conflict response.

---

## Authorization and Errors

MVP permissions are `environment.create`, `environment.read`, and `environment.archive`. `environment_type` is descriptive and cannot grant authorization or bypass Policy enforcement.

Common errors are `environment.not_found`, `environment.slug_conflict`, `environment.archived`, `application.not_found`, `project.not_found`, `organization.suspended`, `authorization.denied`, and `concurrency.version_conflict`.

---

## Deferred

Environment update, suspension, reactivation, maintenance, provisioning, restoration, deletion, settings, criticality, and arbitrary custom environment types are deferred beyond the MVP.

---

## Related Documents

* `docs/api/07-projects-api.md`
* `docs/api/08-applications-api.md`
* `docs/modules/05-environment-module.md`
* `docs/database/04-application-environment-schema.md`
