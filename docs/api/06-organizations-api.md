# Organizations API

## Purpose

This document defines the public API for Organization management.

An Organization is the top-level tenant boundary in Project Sentinel.

---

## Resource

Example Organization:

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "organization",
  "attributes": {
    "name": "Acme Operations",
    "slug": "acme-operations",
    "status": "active",
    "timezone": "Europe/Bucharest",
    "version": 3,
    "created_at": "2026-08-01T16:30:00Z",
    "updated_at": "2026-08-01T17:00:00Z"
  }
}
```

Supported statuses:

* `active`
* `suspended`
* `deletion_pending`
* `archived`

---

## Endpoints

| Method  | Endpoint                                            | Purpose                       |
| ------- | --------------------------------------------------- | ----------------------------- |
| `GET`   | `/organizations`                                    | List accessible Organizations |
| `POST`  | `/organizations`                                    | Create an Organization        |
| `GET`   | `/organizations/{organization_id}`                  | Read an Organization          |
| `PATCH` | `/organizations/{organization_id}`                  | Update Organization settings  |
| `POST`  | `/organizations/{organization_id}/suspend`          | Suspend the Organization      |
| `POST`  | `/organizations/{organization_id}/reactivate`       | Reactivate the Organization   |
| `POST`  | `/organizations/{organization_id}/request-deletion` | Request governed deletion     |

Physical deletion is never exposed as a direct API operation.

---

## List Organizations

```http
GET /api/v1/organizations
```

Returns Organizations accessible to the authenticated User or API Client.

Supported filters:

* `status`
* `name`
* `slug`

The endpoint must not reveal inaccessible Organizations.

---

## Create Organization

```http
POST /api/v1/organizations
Idempotency-Key: {key}
```

Request:

```json
{
  "name": "Acme Operations",
  "slug": "acme-operations",
  "timezone": "Europe/Bucharest"
}
```

Response:

```http
201 Created
```

Creation should also establish:

* Initial Organization Membership.
* initial administrative Role assignment.
* default settings.
* Audit Record.

The creator does not receive unrestricted platform privileges.

---

## Read Organization

```http
GET /api/v1/organizations/{organization_id}
```

Requires active access to the Organization.

Sensitive billing, security, or retention settings should be exposed through dedicated authorized endpoints rather than the general Organization representation.

---

## Update Organization

```http
PATCH /api/v1/organizations/{organization_id}
If-Match: "3"
```

Request:

```json
{
  "name": "Acme Platform Operations",
  "timezone": "Europe/Bucharest"
}
```

Mutable fields may include:

* `name`
* `timezone`
* approved display settings

Changing the Organization identifier or tenant ownership is not supported.

---

## Suspend Organization

```http
POST /api/v1/organizations/{organization_id}/suspend
If-Match: "3"
```

Request:

```json
{
  "reason": "Security review"
}
```

Suspension should:

* Block normal tenant activity.
* stop new ingestion where configured.
* prevent new Agent, Plugin, and Workflow executions.
* preserve data.
* preserve administrative recovery access.
* create an Audit Record.

Suspension does not delete data.

---

## Reactivate Organization

```http
POST /api/v1/organizations/{organization_id}/reactivate
If-Match: "4"
```

Request:

```json
{
  "reason": "Security review completed"
}
```

Reactivation requires the Organization to remain eligible under platform and security Policy.

---

## Request Deletion

```http
POST /api/v1/organizations/{organization_id}/request-deletion
If-Match: "5"
```

Request:

```json
{
  "reason": "Customer requested account closure"
}
```

Response:

```http
202 Accepted
```

The response should reference an asynchronous deletion operation.

Deletion processing must evaluate:

* Legal Holds.
* retention requirements.
* active exports.
* contractual obligations.
* backup handling.
* external storage.
* Search Projections and caches.

---

## Authorization

Typical permissions:

```text
organization.read
organization.create
organization.update
organization.suspend
organization.reactivate
organization.delete_request
```

Sensitive lifecycle operations may require:

* Strong authentication.
* Organization Owner or equivalent Role.
* explicit confirmation.
* approval.
* Audit recording.

---

## Errors

Common errors:

```text
organization.not_found
organization.slug_conflict
organization.suspended
organization.already_active
organization.deletion_already_requested
organization.legal_hold_active
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* An Organization is the tenant boundary.
* Organization access requires valid identity scope.
* Organization identifiers never grant access.
* Slugs are globally or platform-region unique according to deployment Policy.
* Suspension preserves data.
* Physical deletion occurs only through governed retention processing.
* Lifecycle operations use optimistic concurrency.
* Organization creation and deletion requests use idempotency.
* Sensitive lifecycle changes are audited.
* Cross-Organization access is prohibited.

---

## Related Documents

* `docs/api/00-api-overview.md`
* `docs/api/02-authentication.md`
* `docs/api/05-idempotency-concurrency.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/03-organization-project-schema.md`
* `docs/database/15-retention-archival.md`
