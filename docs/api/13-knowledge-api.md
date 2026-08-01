# Knowledge API

## Purpose

This document defines the API for managing operational Knowledge.

Knowledge captures reusable information such as:

* Runbooks.
* remediation procedures.
* troubleshooting guides.
* Incident learnings.
* operational decisions.

Published Knowledge is versioned and remains traceable to its evidence.

---

## Resource

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "knowledge_entry",
  "attributes": {
    "title": "Recover checkout workers",
    "knowledge_type": "runbook",
    "status": "published",
    "summary": "Procedure for recovering stalled checkout workers.",
    "active_version": 3,
    "version": 5,
    "created_at": "2026-08-01T16:30:00Z",
    "updated_at": "2026-08-01T17:00:00Z"
  }
}
```

Supported Knowledge Types:

* `runbook`
* `troubleshooting_guide`
* `remediation`
* `incident_learning`
* `decision`
* `reference`
* `custom`

Supported statuses:

* `draft`
* `in_review`
* `published`
* `deprecated`
* `archived`

---

## Endpoints

| Method  | Endpoint                                                                          | Purpose               |
| ------- | --------------------------------------------------------------------------------- | --------------------- |
| `GET`   | `/organizations/{organization_id}/knowledge`                                      | List Knowledge        |
| `POST`  | `/organizations/{organization_id}/knowledge`                                      | Create an entry       |
| `GET`   | `/organizations/{organization_id}/knowledge/{knowledge_id}`                       | Read an entry         |
| `PATCH` | `/organizations/{organization_id}/knowledge/{knowledge_id}`                       | Update draft metadata |
| `POST`  | `/organizations/{organization_id}/knowledge/{knowledge_id}/versions`              | Create a Version      |
| `GET`   | `/organizations/{organization_id}/knowledge/{knowledge_id}/versions`              | List Versions         |
| `GET`   | `/organizations/{organization_id}/knowledge/{knowledge_id}/versions/{version_id}` | Read a Version        |
| `POST`  | `/organizations/{organization_id}/knowledge/{knowledge_id}/submit-review`         | Submit for review     |
| `POST`  | `/organizations/{organization_id}/knowledge/{knowledge_id}/publish`               | Publish a Version     |
| `POST`  | `/organizations/{organization_id}/knowledge/{knowledge_id}/deprecate`             | Deprecate             |
| `POST`  | `/organizations/{organization_id}/knowledge/{knowledge_id}/archive`               | Archive               |

---

## List Knowledge

```http
GET /api/v1/organizations/{organization_id}/knowledge
```

Supported filters:

* `knowledge_type`
* `status`
* `project_id`
* `application_id`
* `environment_id`
* `owner_id`
* `updated_from`
* `updated_to`

Default ordering:

```text
updated_at DESC, id DESC
```

Use the Search API for advanced full-text or semantic discovery.

---

## Create Knowledge

```http
POST /api/v1/organizations/{organization_id}/knowledge
Idempotency-Key: {key}
```

Request:

```json
{
  "title": "Recover checkout workers",
  "knowledge_type": "runbook",
  "summary": "Procedure for recovering stalled checkout workers.",
  "scope": {
    "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01"
  }
}
```

Response:

```http
201 Created
```

A new entry begins in `draft`.

---

## Create Version

```http
POST /api/v1/organizations/{organization_id}/knowledge/{knowledge_id}/versions
If-Match: "5"
Idempotency-Key: {key}
```

Request:

```json
{
  "content_format": "markdown",
  "content": "## Recovery steps\n\n1. Pause the queue...",
  "change_summary": "Added queue recovery procedure.",
  "evidence_ids": [
    "0198f3c4-572c-72bd-91e5-bcc0b36f92ab"
  ]
}
```

Knowledge Versions are immutable after creation.

Corrections require another Version.

---

## Read Knowledge

```http
GET /api/v1/organizations/{organization_id}/knowledge/{knowledge_id}
```

The response may include:

* Active Version.
* applicability scope.
* owner.
* review status.
* evidence references.
* related Incidents.
* next review date.

Only authorized content is returned.

---

## Submit for Review

```http
POST /api/v1/organizations/{organization_id}/knowledge/{knowledge_id}/submit-review
If-Match: "6"
```

Request:

```json
{
  "version_id": "0198f3ca-9e18-71ab-9d98-17f6d76d38a1",
  "reviewer_type": "team",
  "reviewer_id": "0198f3cb-1929-742a-af75-b7c92e793a52"
}
```

The selected Version must belong to the entry and Organization.

---

## Publish Knowledge

```http
POST /api/v1/organizations/{organization_id}/knowledge/{knowledge_id}/publish
If-Match: "7"
Idempotency-Key: {key}
```

Request:

```json
{
  "version_id": "0198f3ca-9e18-71ab-9d98-17f6d76d38a1",
  "publication_note": "Reviewed by the checkout operations team."
}
```

Publication may require:

* Completed review.
* valid evidence.
* authorized publisher.
* approved applicability scope.
* no unresolved validation errors.

Publishing sets the selected Version as Active.

Previous Versions remain preserved.

---

## Deprecate Knowledge

```http
POST /api/v1/organizations/{organization_id}/knowledge/{knowledge_id}/deprecate
If-Match: "8"
```

Request:

```json
{
  "reason": "The checkout worker architecture changed.",
  "replacement_knowledge_id": "0198f3cc-286c-79db-a807-33f60d778ad8"
}
```

Deprecated Knowledge may remain readable but should not be recommended as current guidance.

---

## Archive Knowledge

```http
POST /api/v1/organizations/{organization_id}/knowledge/{knowledge_id}/archive
If-Match: "9"
```

Archival removes the entry from normal active discovery while preserving its history and references.

Archival is not deletion.

---

## Applicability Scope

Knowledge may apply to:

* An Organization.
* Project.
* Application.
* Environment.
* Resource Type.
* Plugin.
* Workflow.
* Incident category.

All referenced scopes must belong to the same Organization.

Scope affects discovery and recommendations but does not replace authorization.

---

## Authorization

Typical permissions:

```text
knowledge.read
knowledge.create
knowledge.update
knowledge.review
knowledge.publish
knowledge.deprecate
knowledge.archive
```

Publication and deprecation may require stronger permissions than draft editing.

---

## Errors

Common errors:

```text
knowledge.not_found
knowledge.version_not_found
knowledge.invalid_transition
knowledge.review_required
knowledge.evidence_invalid
knowledge.scope_invalid
knowledge.replacement_invalid
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Knowledge Entry belongs to one Organization.
* Knowledge Versions are immutable.
* Published content always references one exact Version.
* Previous Versions remain preserved.
* Evidence and applicability scopes must belong to the same Organization.
* Publication may require review and evidence.
* Deprecated Knowledge must not be presented as current guidance.
* Archival preserves history.
* Mutable lifecycle operations use optimistic concurrency.
* Retryable commands use idempotency.
* Cross-Organization references are prohibited.

---

## Related Documents

* `docs/api/12-incidents-api.md`
* `docs/api/14-agents-api.md`
* `docs/api/17-search-api.md`
* `docs/database/08-knowledge-schema.md`
* `docs/functional/09-knowledge-management.md`
