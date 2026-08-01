# Pagination and Filtering

## Purpose

This document defines collection pagination, filtering, and sorting conventions for Project Sentinel APIs.

---

## Cursor Pagination

Large collections use cursor-based pagination.

Example:

```http
GET /api/v1/organizations/{organization_id}/incidents?limit=50&cursor={cursor}
```

Response:

```json
{
  "data": [],
  "pagination": {
    "next_cursor": "opaque-cursor",
    "has_more": true
  }
}
```

Cursors must be:

* Opaque to clients.
* scoped to the query.
* based on stable ordering.
* rejected when malformed or incompatible.

Clients must not construct or modify cursors.

---

## Stable Ordering

Every paginated collection must use deterministic ordering.

Recommended default:

```text
created_at DESC, id DESC
```

The identifier acts as a tie-breaker when multiple Resources share the same timestamp.

The cursor must preserve all values required to continue this ordering.

---

## Page Size

Collection endpoints should define:

* A default page size.
* a maximum page size.
* endpoint-specific limits where necessary.

Recommended defaults:

```text
default: 50
maximum: 100
```

Requests exceeding the maximum return a validation error or are capped according to the documented endpoint contract.

---

## Offset Pagination

Offset pagination should not be used for high-volume operational Resources.

It may be acceptable for:

* Small administrative collections.
* static reference data.
* bounded internal tools.

Cursor pagination remains the default.

---

## Filtering

Filters use query parameters.

Example:

```http
GET /alerts?status=open&severity=critical&application_id={application_id}
```

Common filters include:

```text
status
severity
priority
project_id
application_id
environment_id
owner_id
created_from
created_to
updated_from
updated_to
```

Each endpoint must document its supported filters.

Unsupported filters must be rejected.

---

## Multiple Values

Multiple values should use repeated parameters:

```http
GET /alerts?status=open&status=investigating
```

This normally means:

```text
status IN (open, investigating)
```

Comma-separated values should not be used unless explicitly documented.

---

## Date Ranges

Date filters use ISO 8601 UTC timestamps.

Example:

```http
GET /events?occurred_from=2026-08-01T00:00:00Z&occurred_to=2026-08-02T00:00:00Z
```

Recommended range semantics:

```text
from: inclusive
to: exclusive
```

Invalid or excessive date ranges must be rejected.

---

## Resource Scope

Filtering never replaces tenant isolation.

Every tenant query must include the authenticated:

```text
organization_id
```

Additional Resource filters must also be validated against the same Organization.

---

## Sorting

Sorting uses the `sort` parameter.

Examples:

```http
GET /incidents?sort=created_at
GET /incidents?sort=-created_at
```

A leading `-` means descending order.

Only documented fields may be sorted.

When the requested field is not unique, Sentinel adds `id` as a stable secondary sort key.

---

## Default Sorting

Each collection must define a default sort order.

Examples:

```text
Events: occurred_at DESC, id DESC
Alerts: updated_at DESC, id DESC
Incidents: declared_at DESC, id DESC
Tasks: due_at ASC, id ASC
```

Default ordering must remain stable within an API major Version.

---

## Search Parameter

Endpoints that support lightweight text matching may use:

```http
GET /incidents?q=checkout
```

The `q` parameter must not replace the dedicated Search API for complex cross-resource search.

Search results remain authorization-filtered.

---

## Null Filters

Null filtering must be explicit.

Examples:

```http
GET /incidents?owner_id=null
GET /workflows?completed_at=null
```

Endpoints must document whether null filtering is supported.

An omitted filter means no filtering by that field.

---

## Boolean Filters

Boolean filters accept only:

```text
true
false
```

Example:

```http
GET /plugins?enabled=true
```

Values such as `1`, `yes`, or `on` should be rejected.

---

## Filter Validation

Sentinel must reject:

* Unknown filter names.
* invalid UUIDs.
* unsupported enum values.
* invalid timestamps.
* invalid ranges.
* unauthorized Resource references.
* excessive filter complexity.

Example error:

```json
{
  "error": {
    "code": "request.invalid_filter",
    "message": "The requested filter is not supported.",
    "details": {
      "filter": "unknown_field"
    }
  }
}
```

---

## Authorization

Authorization applies to:

* Returned Resources.
* totals.
* facets.
* suggestions.
* related-resource filters.
* search matches.

Collection metadata must not reveal unauthorized Resources.

---

## Performance Rules

Filtering and sorting should use approved indexed fields.

Endpoints must avoid:

* Unbounded result sets.
* arbitrary database-column filtering.
* client-defined SQL expressions.
* expensive wildcard queries on authoritative tables.
* sorting by unindexed high-volume fields.

Complex discovery should use Search Projections.

---

## Business Rules

* Large collections use cursor pagination.
* Cursors are opaque and query-specific.
* Ordering includes a stable tie-breaker.
* Page size is bounded.
* Supported filters are explicitly documented.
* Unsupported filters and sort fields are rejected.
* Date ranges use UTC and defined boundaries.
* Authorization applies to results and collection metadata.
* Filtering never replaces Organization isolation.
* High-volume APIs must not depend on offset pagination.

---

## Related Documents

* `docs/api/00-api-overview.md`
* `docs/api/01-api-conventions.md`
* `docs/api/03-errors.md`
* `docs/api/05-idempotency-concurrency.md`
* `docs/api/17-search-api.md`
* `docs/database/14-search-projections.md`
* `docs/database/16-indexing-strategy.md`
