# Search API

## Purpose

This document defines the API for searching authorized Sentinel Resources.

Search uses derived Projections and is eventually consistent.

Authoritative Resource APIs remain the source of truth.

---

## Endpoint

```http
GET /api/v1/organizations/{organization_id}/search
```

---

## Request

Example:

```http
GET /api/v1/organizations/{organization_id}/search?q=checkout&resource_type=incident&status=open&limit=25
```

Supported parameters:

| Parameter        | Purpose                   |
| ---------------- | ------------------------- |
| `q`              | Text query                |
| `resource_type`  | Restrict Resource Types   |
| `project_id`     | Filter by Project         |
| `application_id` | Filter by Application     |
| `environment_id` | Filter by Environment     |
| `status`         | Filter by status          |
| `severity`       | Filter by severity        |
| `created_from`   | Inclusive start timestamp |
| `created_to`     | Exclusive end timestamp   |
| `sort`           | Result ordering           |
| `limit`          | Page size                 |
| `cursor`         | Pagination cursor         |

Multiple `resource_type`, `status`, or `severity` parameters may be supplied.

---

## Supported Resource Types

Initial searchable Resource Types include:

* `application`
* `environment`
* `event`
* `signal`
* `alert`
* `incident`
* `knowledge_entry`
* `agent_finding`
* `agent_recommendation`
* `plugin_installation`
* `workflow_instance`
* `workflow_task`

Audit search requires separate permission and may use a dedicated endpoint.

---

## Response

```json
{
  "data": [
    {
      "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
      "type": "incident",
      "attributes": {
        "title": "Checkout service disruption",
        "summary": "Checkout requests are failing.",
        "status": "investigating",
        "severity": "high",
        "source_updated_at": "2026-08-01T16:40:00Z",
        "indexed_at": "2026-08-01T16:40:02Z"
      },
      "relationships": {
        "application_id": "0198f3b5-e313-7d70-8778-72c427f32c01"
      }
    }
  ],
  "pagination": {
    "next_cursor": null,
    "has_more": false
  },
  "meta": {
    "request_id": "0198f3b6-91c8-7c30-b718-6f85c83e3875"
  }
}
```

Search results return a summary representation.

Clients should use the authoritative Resource endpoint for complete current state.

---

## Pagination

Search uses cursor pagination.

Default ordering:

```text
relevance DESC, source_updated_at DESC, id DESC
```

When no text query is supplied, the endpoint may default to:

```text
source_updated_at DESC, id DESC
```

Cursors are opaque and query-specific.

---

## Authorization

Authorization applies to:

* Results.
* result counts.
* facets.
* highlights.
* suggestions.
* related Resource previews.

Search must not reveal the existence of unauthorized Resources.

Every query is scoped to the Organization in the route.

---

## Staleness

Search is eventually consistent.

Each result includes:

* `source_updated_at`
* `indexed_at`

Recently changed Resources may not appear immediately.

Critical workflows should read authoritative APIs directly.

---

## Sensitivity

Search must not index or return:

* Credentials.
* secrets.
* unrestricted Event payloads.
* private Agent prompts.
* raw provider responses.
* restricted evidence without permission.

Search Projections must preserve or increase the source Resource sensitivity classification.

---

## Highlighting

When supported, matches may include safe text highlights.

Example:

```json
{
  "highlights": {
    "title": [
      "<mark>Checkout</mark> service disruption"
    ]
  }
}
```

Highlight output must be sanitized before rendering.

---

## Semantic Search

Semantic search may be introduced later.

If enabled, it must remain:

* Organization-scoped.
* authorization-filtered.
* sensitivity-aware.
* removable through retention.
* independent from one model provider.

Semantic search is not required for the initial MVP.

---

## Errors

Common errors:

```text
search.query_required
search.invalid_resource_type
search.invalid_filter
search.cursor_invalid
search.range_too_large
search.unavailable
authorization.denied
```

---

## Business Rules

* Search Projections are derived and rebuildable.
* Search is not authoritative state.
* Every query is Organization-scoped.
* Authorization applies to results and metadata.
* Search must not expose unauthorized counts or facets.
* Sensitive content must not be indexed without approval.
* Cursor pagination is required.
* Recently changed Resources may be temporarily stale.
* Cross-Organization search is prohibited.

---

## Related Documents

* `docs/api/01-api-conventions.md`
* `docs/api/04-pagination-filtering.md`
* `docs/database/14-search-projections.md`
* `docs/database/16-indexing-strategy.md`
