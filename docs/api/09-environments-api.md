# Environments API

## Purpose

This document defines the API for managing runtime Environments within an Application.

Examples include:

* Production.
* staging.
* testing.
* development.

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
    "version": 2,
    "created_at": "2026-08-01T16:30:00Z",
    "updated_at": "2026-08-01T17:00:00Z"
  }
}
```

Supported environment types:

* `production`
* `staging`
* `testing`
* `development`
* `sandbox`
* `custom`

Supported statuses:

* `active`
* `suspended`
* `archived`

---

## Endpoints

| Method  | Endpoint                                                                      | Purpose                   |
| ------- | ----------------------------------------------------------------------------- | ------------------------- |
| `GET`   | `/organizations/{organization_id}/environments`                               | List Environments         |
| `POST`  | `/organizations/{organization_id}/applications/{application_id}/environments` | Create an Environment     |
| `GET`   | `/organizations/{organization_id}/environments/{environment_id}`              | Read an Environment       |
| `PATCH` | `/organizations/{organization_id}/environments/{environment_id}`              | Update an Environment     |
| `POST`  | `/organizations/{organization_id}/environments/{environment_id}/suspend`      | Suspend an Environment    |
| `POST`  | `/organizations/{organization_id}/environments/{environment_id}/reactivate`   | Reactivate an Environment |
| `POST`  | `/organizations/{organization_id}/environments/{environment_id}/archive`      | Archive an Environment    |

---

## List Environments

```http
GET /api/v1/organizations/{organization_id}/environments
```

Supported filters:

* `application_id`
* `environment_type`
* `status`
* `slug`

Default ordering:

```text
created_at DESC, id DESC
```

---

## Create Environment

```http
POST /api/v1/organizations/{organization_id}/applications/{application_id}/environments
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

Response:

```http
201 Created
```

The Application and Organization must match.

---

## Read Environment

```http
GET /api/v1/organizations/{organization_id}/environments/{environment_id}
```

Lookup and authorization must include both:

```text
organization_id
environment_id
```

---

## Update Environment

```http
PATCH /api/v1/organizations/{organization_id}/environments/{environment_id}
If-Match: "2"
```

Request:

```json
{
  "name": "Primary Production"
}
```

Mutable fields may include:

* `name`
* approved metadata
* selected operational settings

Changing Application ownership should require a dedicated operation, if supported.

---

## Suspend Environment

```http
POST /api/v1/organizations/{organization_id}/environments/{environment_id}/suspend
If-Match: "2"
```

Request:

```json
{
  "reason": "Environment temporarily disabled"
}
```

Suspension may prevent:

* New Event ingestion.
* Plugin synchronization.
* Agent execution.
* Workflow execution.
* automated Actions.

Historical data remains available according to authorization and retention rules.

---

## Reactivate Environment

```http
POST /api/v1/organizations/{organization_id}/environments/{environment_id}/reactivate
If-Match: "3"
```

The parent Organization, Project, and Application must be Active.

---

## Archive Environment

```http
POST /api/v1/organizations/{organization_id}/environments/{environment_id}/archive
If-Match: "4"
```

Archival:

* Stops normal activity.
* preserves historical operational data.
* excludes the Environment from default active listings.
* does not delete data.

---

## Authorization

Typical permissions:

```text
environment.read
environment.create
environment.update
environment.suspend
environment.reactivate
environment.archive
```

Access may also be restricted by Project, Application, Team, or Environment scope.

Production Environments may require stronger permissions than non-production Environments.

---

## Errors

Common errors:

```text
environment.not_found
environment.slug_conflict
environment.suspended
environment.archived
application.not_found
application.suspended
project.suspended
organization.suspended
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Environment belongs to one Application and Organization.
* Environment lookup always includes Organization scope.
* Environment slugs are unique within their Application.
* Parent Resource state may restrict Environment operations.
* Suspended or Archived Environments cannot accept normal new activity.
* Production Environments may have stricter authorization Policies.
* Creation uses idempotency.
* Lifecycle changes use optimistic concurrency.
* Archival preserves operational history.
* Cross-Organization relationships are prohibited.

---

## Related Documents

* `docs/api/07-projects-api.md`
* `docs/api/08-applications-api.md`
* `docs/api/10-events-api.md`
* `docs/database/04-application-environment-schema.md`
* `docs/database/05-event-signal-schema.md`
