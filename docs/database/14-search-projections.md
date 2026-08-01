# Search Projections

## Purpose

This document defines the persistence model for Sentinel Search Projections.

Search Projections provide fast discovery across:

* Applications.
* Environments.
* Events.
* Signals.
* Alerts.
* Incidents.
* Knowledge.
* Agent results.
* Plugin records.
* Workflows.
* Audit records where authorized.

Search data is derived.

It is not authoritative business state.

---

## Design Principles

* Search Projections are rebuildable.
* Every tenant Projection includes `organization_id`.
* Search never bypasses authorization.
* Source capabilities remain authoritative.
* Projection updates are asynchronous.
* Stale Projections must be detectable.
* Deleted, archived, or restricted Resources must be removed or updated.
* Search results preserve source identifiers and Versions.
* Sensitive content is indexed only when explicitly permitted.
* Cross-Organization search is prohibited.
* Search provider details remain behind an adapter.

---

## Initial Search Strategy

The initial implementation should use PostgreSQL search capabilities where practical.

Recommended initial components:

* PostgreSQL full-text search.
* indexed structured fields.
* JSONB only for limited extensible metadata.
* background Projection workers.
* Redis for short-lived query caching where useful.

A dedicated search engine may be introduced later when required by:

* Data volume.
* ranking complexity.
* advanced faceting.
* fuzzy matching.
* semantic search.
* independent search scaling.

Possible future providers include:

* OpenSearch.
* Elasticsearch.
* another compatible search service.

Search contracts must not depend on one provider.

---

## Schema Overview

```text id="7lr4yi"
search_documents
├── search_document_fields
├── search_document_facets
├── search_document_relationships
└── search_document_embeddings

search_projection_checkpoints
search_projection_failures
search_rebuild_jobs
```

---

# Search Documents

## `search_documents`

Stores one current Search Projection for one authoritative Resource.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `resource_type`     | VARCHAR     | Required    |
| `resource_id`       | UUID        | Required    |
| `source_version`    | VARCHAR     | Required    |
| `title`             | TEXT        | Required    |
| `summary`           | TEXT        | Optional    |
| `searchable_text`   | TEXT        | Required    |
| `search_vector`     | TSVECTOR    | Optional    |
| `status`            | VARCHAR     | Optional    |
| `severity`          | VARCHAR     | Optional    |
| `priority`          | VARCHAR     | Optional    |
| `sensitivity`       | VARCHAR     | Required    |
| `visibility_scope`  | JSONB       | Required    |
| `occurred_at`       | TIMESTAMPTZ | Optional    |
| `source_created_at` | TIMESTAMPTZ | Optional    |
| `source_updated_at` | TIMESTAMPTZ | Optional    |
| `indexed_at`        | TIMESTAMPTZ | Required    |
| `archived_at`       | TIMESTAMPTZ | Optional    |
| `deleted_at`        | TIMESTAMPTZ | Optional    |

Constraints:

```text id="b2s5wp"
UNIQUE (
    organization_id,
    resource_type,
    resource_id
)
```

One active Projection should exist for each searchable authoritative Resource.

---

## Supported Resource Types

Initial Resource Types may include:

* `project`
* `application`
* `environment`
* `event`
* `signal`
* `alert`
* `incident`
* `knowledge_entry`
* `knowledge_version`
* `agent_finding`
* `agent_recommendation`
* `plugin_installation`
* `workflow_instance`
* `workflow_task`

Audit Resources should be indexed only through a restricted Projection and permission model.

---

## Source Version

`source_version` identifies the authoritative state represented by the Projection.

It may contain:

* Aggregate Version.
* immutable Version identifier.
* update sequence.
* domain event sequence.

Projection workers must ignore updates older than the stored source Version.

This prevents delayed messages from overwriting newer Search data.

---

## Searchable Text

`searchable_text` contains normalized text selected from the authoritative Resource.

It may include:

* Title.
* summary.
* approved descriptions.
* normalized identifiers.
* safe labels.
* selected evidence summaries.

It must not include:

* Credentials.
* secret values.
* unrestricted payloads.
* private Agent prompts.
* raw provider responses.
* sensitive fields not approved for Search.

---

# Structured Fields

## `search_document_fields`

Stores additional typed fields used for filtering or sorting.

| Column               | Type        | Rules    |
| -------------------- | ----------- | -------- |
| `organization_id`    | UUID        | Required |
| `search_document_id` | UUID        | Required |
| `field_name`         | VARCHAR     | Required |
| `field_type`         | VARCHAR     | Required |
| `string_value`       | TEXT        | Optional |
| `integer_value`      | BIGINT      | Optional |
| `decimal_value`      | DECIMAL     | Optional |
| `boolean_value`      | BOOLEAN     | Optional |
| `timestamp_value`    | TIMESTAMPTZ | Optional |
| `identifier_value`   | UUID        | Optional |
| `created_at`         | TIMESTAMPTZ | Required |

Primary key:

```text id="1t3fgl"
PRIMARY KEY (
    organization_id,
    search_document_id,
    field_name
)
```

Supported field types:

* `string`
* `integer`
* `decimal`
* `boolean`
* `timestamp`
* `identifier`

Frequently used fields should use dedicated columns on `search_documents` instead of generic field records.

---

# Search Facets

## `search_document_facets`

Stores multi-valued filter classifications.

| Column               | Type        | Rules    |
| -------------------- | ----------- | -------- |
| `organization_id`    | UUID        | Required |
| `search_document_id` | UUID        | Required |
| `facet_name`         | VARCHAR     | Required |
| `facet_value`        | VARCHAR     | Required |
| `created_at`         | TIMESTAMPTZ | Required |

Primary key:

```text id="s03han"
PRIMARY KEY (
    organization_id,
    search_document_id,
    facet_name,
    facet_value
)
```

Possible facets include:

* Project.
* Application.
* Environment.
* status.
* severity.
* priority.
* owner.
* Team.
* Resource Type.
* Agent Type.
* Plugin Type.
* Knowledge Type.
* tag.

Facets must not expose values from unauthorized Resources.

---

# Search Relationships

## `search_document_relationships`

Stores Search-oriented relationships between Resources.

| Column                  | Type        | Rules       |
| ----------------------- | ----------- | ----------- |
| `id`                    | UUID        | Primary key |
| `organization_id`       | UUID        | Required    |
| `search_document_id`    | UUID        | Required    |
| `related_resource_type` | VARCHAR     | Required    |
| `related_resource_id`   | UUID        | Required    |
| `relationship_type`     | VARCHAR     | Required    |
| `created_at`            | TIMESTAMPTZ | Required    |

Possible relationships:

* `belongs_to`
* `related_to`
* `triggered_by`
* `linked_to`
* `affects`
* `owned_by`
* `derived_from`

These relationships improve filtering and navigation.

They do not become authoritative domain relationships.

---

# Visibility Scope

The `visibility_scope` field defines the restrictions required before returning a result.

It may contain references to:

* Project.
* Application.
* Environment.
* Team.
* sensitivity level.
* required permission.
* restricted Incident role.

Search must still perform current authorization checks.

Stored visibility information is only a filtering optimization.

It must not replace runtime Policy evaluation.

---

# Sensitivity

Every Search Document must have one sensitivity classification.

Suggested values:

* `internal`
* `confidential`
* `restricted`
* `regulated`

The Projection worker must determine sensitivity from authoritative data.

A Search Projection must not reduce the source Resource’s classification.

---

# Semantic Search

Semantic search is optional and should not be required for the initial MVP.

If introduced, embeddings must remain:

* Organization-scoped.
* sensitivity-aware.
* linked to the exact source Version.
* removable through retention.
* rebuildable.

## `search_document_embeddings`

Stores semantic representation metadata.

| Column                | Type        | Rules       |
| --------------------- | ----------- | ----------- |
| `id`                  | UUID        | Primary key |
| `organization_id`     | UUID        | Required    |
| `search_document_id`  | UUID        | Required    |
| `source_version`      | VARCHAR     | Required    |
| `provider`            | VARCHAR     | Required    |
| `model`               | VARCHAR     | Required    |
| `dimensions`          | INTEGER     | Required    |
| `embedding_reference` | TEXT        | Required    |
| `content_hash`        | VARCHAR     | Required    |
| `created_at`          | TIMESTAMPTZ | Required    |
| `expires_at`          | TIMESTAMPTZ | Optional    |

The vector may be stored:

* In PostgreSQL using an approved extension.
* in a dedicated vector index.
* through an external search provider.

`embedding_reference` must not reveal credentials.

---

# Projection Updates

Search Projections should be updated from domain events.

Example:

```text id="1zk0s6"
Incident Updated

↓

Outbox Event Published

↓

Search Projection Worker Consumes Event

↓

Source Resource Loaded

↓

Authorization and Sensitivity Fields Calculated

↓

Search Projection Upserted
```

Search workers must not write to authoritative domain tables.

---

# Projection Upsert

A Projection update should:

1. Resolve Organization context.
2. validate the source Resource.
3. load the current authoritative state.
4. build safe searchable content.
5. compare source Versions.
6. insert or update the Search Document.
7. replace dependent fields, facets, and relationships.
8. record the indexing checkpoint.
9. emit operational telemetry.

The update must be idempotent.

---

# Source Version Ordering

A Projection update must not overwrite newer data.

Conceptual rule:

```text id="j8k5av"
incoming_source_version > stored_source_version
```

When Versions are not directly comparable, the worker should reload the current source Resource before rebuilding the Projection.

---

# Projection Deletion

When an authoritative Resource is deleted under governed retention:

* Mark or delete its Search Document.
* remove structured fields.
* remove facets.
* remove relationships.
* remove embeddings.
* invalidate relevant caches.

Search deletion must be idempotent.

Archived Resources may remain searchable when authorized but should be excluded from default active searches.

---

# Projection Checkpoints

## `search_projection_checkpoints`

Tracks consumer progress.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `projection_name`   | VARCHAR     | Required    |
| `partition_key`     | VARCHAR     | Required    |
| `last_message_id`   | UUID        | Optional    |
| `last_sequence`     | BIGINT      | Optional    |
| `last_processed_at` | TIMESTAMPTZ | Optional    |
| `updated_at`        | TIMESTAMPTZ | Required    |
| `version`           | BIGINT      | Required    |

Constraints:

```text id="ggq40h"
UNIQUE (
    projection_name,
    partition_key
)
```

Checkpoints support resumable Projection processing.

They must not be the only duplicate-protection mechanism.

---

# Projection Failures

## `search_projection_failures`

Stores failed indexing attempts.

| Column            | Type        | Rules       |
| ----------------- | ----------- | ----------- |
| `id`              | UUID        | Primary key |
| `organization_id` | UUID        | Optional    |
| `projection_name` | VARCHAR     | Required    |
| `resource_type`   | VARCHAR     | Required    |
| `resource_id`     | UUID        | Required    |
| `source_version`  | VARCHAR     | Optional    |
| `message_id`      | UUID        | Optional    |
| `failure_code`    | VARCHAR     | Required    |
| `failure_message` | TEXT        | Required    |
| `attempt_count`   | INTEGER     | Required    |
| `first_failed_at` | TIMESTAMPTZ | Required    |
| `last_failed_at`  | TIMESTAMPTZ | Required    |
| `next_retry_at`   | TIMESTAMPTZ | Optional    |
| `resolved_at`     | TIMESTAMPTZ | Optional    |

Failures must remain observable and retryable.

Sensitive source content must not appear in failure messages.

---

# Rebuild Jobs

## `search_rebuild_jobs`

Tracks full or scoped Projection rebuilds.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Optional    |
| `resource_type`            | VARCHAR     | Optional    |
| `scope_definition`         | JSONB       | Required    |
| `status`                   | VARCHAR     | Required    |
| `requested_by_identity_id` | UUID        | Required    |
| `requested_at`             | TIMESTAMPTZ | Required    |
| `started_at`               | TIMESTAMPTZ | Optional    |
| `completed_at`             | TIMESTAMPTZ | Optional    |
| `processed_count`          | BIGINT      | Required    |
| `failed_count`             | BIGINT      | Required    |
| `failure_code`             | VARCHAR     | Optional    |

Supported statuses:

* `requested`
* `running`
* `completed`
* `completed_with_failures`
* `failed`
* `cancelled`

A rebuild may target:

* One Resource.
* one Resource Type.
* one Organization.
* one date range.
* the complete Search index.

---

# Rebuild Strategy

A safe rebuild should:

1. Read authoritative Resources in bounded batches.
2. build Projections idempotently.
3. preserve Organization scope.
4. avoid blocking normal domain writes.
5. track progress.
6. retry failed Resources.
7. validate counts and Versions.
8. remove stale documents after verification.

For an external search engine, Sentinel may build a new index and switch aliases after validation.

---

# Search Queries

Every tenant Search query must include:

```text id="oe82gm"
organization_id
```

Queries may also filter by:

* Resource Type.
* Project.
* Application.
* Environment.
* status.
* severity.
* priority.
* owner.
* date range.
* sensitivity.
* tags.

Runtime authorization must be applied before returning results.

---

# Result Authorization

A Search result is returned only when:

1. The Search Document belongs to the active Organization.
2. The acting identity can view the Resource Type.
3. The identity can access the Resource scope.
4. The identity can access the sensitivity level.
5. The authoritative Resource still exists and remains accessible where revalidation is required.

High-risk Resources may require authoritative revalidation before display.

---

# Counts, Facets, and Suggestions

Authorization applies to:

* Result lists.
* result counts.
* facets.
* autocomplete.
* suggestions.
* highlights.
* related-resource previews.
* semantic matches.

Search metadata must not reveal the existence of unauthorized Resources.

---

# Staleness

Search is eventually consistent.

Each result should retain:

* `source_version`
* `source_updated_at`
* `indexed_at`

The platform may identify stale Projections by comparing:

```text id="4vvnt4"
source_updated_at > indexed_at
```

or by comparing authoritative and projected Versions.

Critical operational screens may query the authoritative capability directly instead of relying only on Search.

---

# Query Caching

Search queries may be cached only when:

* The cache key includes Organization.
* identity or authorization scope is included when required.
* filters and pagination are included.
* expiration is short.
* authorization changes invalidate or bypass the cache.

Example cache key:

```text id="80251j"
search:{organization_id}:{authorization_version}:{query_hash}
```

Cached results must not cross Organization boundaries.

---

# Transaction Boundaries

Projection updates are independent from authoritative domain transactions.

The authoritative transaction commits:

1. Domain state.
2. domain history where required.
3. Outbox message.

The Search Projection updates asynchronously.

Search failure must not roll back an already committed domain operation.

---

# Tenant Isolation

Every tenant-owned Search table includes `organization_id`.

The schema must prevent:

* Cross-Organization Search Documents.
* cross-Organization facets.
* relationships across Organizations.
* semantic search across Organizations.
* cache sharing across Organizations.
* rebuild jobs applying the wrong tenant scope.

Platform-wide public Resources require a separate explicit ownership and authorization model.

---

# Required Indexes

Recommended PostgreSQL indexes include:

```text id="c96ihy"
search_documents (
    organization_id,
    resource_type,
    status
)

search_documents (
    organization_id,
    application_id
)
```

Application or Environment identifiers may be stored as dedicated columns if these become common query patterns.

Core indexes should include:

```text id="tz5hva"
search_documents (
    organization_id,
    indexed_at
)

search_documents (
    organization_id,
    source_updated_at
)

search_documents
USING GIN (search_vector)

search_document_fields (
    organization_id,
    field_name,
    string_value
)

search_document_fields (
    organization_id,
    field_name,
    identifier_value
)

search_document_fields (
    organization_id,
    field_name,
    timestamp_value
)

search_document_facets (
    organization_id,
    facet_name,
    facet_value
)

search_document_relationships (
    organization_id,
    related_resource_type,
    related_resource_id
)

search_projection_failures (
    next_retry_at
)
WHERE resolved_at IS NULL
```

Indexes must reflect actual Search behavior.

---

# Retention

Search Projections follow the retention state of their authoritative Resources.

When source data is removed:

* Search Documents must be removed.
* fields and facets must be removed.
* relationships must be removed.
* embeddings must be removed.
* caches must be invalidated.

Projection failure records and rebuild history may use shorter operational retention.

Legal Hold applies to authoritative data, not necessarily to rebuildable Search Projections.

---

# Security Requirements

* Search must enforce Organization scope.
* Search must not expose unauthorized counts or facets.
* sensitive text must not be indexed without approval.
* credentials and secrets must never be indexed.
* semantic embeddings must preserve tenant isolation.
* external search providers must meet data-protection requirements.
* Search logs must not contain unrestricted query or result data.
* high-risk Resources may require authoritative revalidation.

---

# Audit Requirements

Auditable Search operations may include:

* Sensitive Search access.
* Audit Record Search.
* restricted Incident Search.
* cross-resource export.
* Search rebuild requests.
* external search provider configuration.
* semantic indexing activation.

Normal low-risk Search queries may use operational access Logs rather than general Audit Records unless Policy requires otherwise.

---

# Prohibited Practices

The following are prohibited:

* Using Search as authoritative state.
* returning results without Organization scope.
* indexing plaintext credentials.
* copying unrestricted source payloads.
* treating stored visibility scope as final authorization.
* allowing cross-Organization semantic similarity.
* exposing unauthorized result counts.
* updating authoritative Resources from Search.
* silently ignoring repeated Projection failures.
* relying on one search provider’s schema in domain contracts.
* deleting authoritative data because its Projection was removed.

---

# Business Rules

* Search Projections are derived and rebuildable.
* Every tenant Search Document includes `organization_id`.
* Every Search Document references one authoritative Resource.
* Search Documents preserve source Version.
* Older Projection messages must not overwrite newer data.
* Search never becomes the source of truth.
* Search authorization applies to results, counts, facets, and suggestions.
* Sensitive content must not be indexed without explicit permission.
* Search Projections must not reduce source sensitivity.
* Projection updates are idempotent.
* Projection failures must remain observable.
* Search deletion follows authoritative retention.
* Semantic search is tenant-scoped.
* Cross-Organization Search is prohibited.
* Missing or conflicting Organization ownership results in rejection.

---

# Success Criteria

The Search Projection model is successful when:

* Users can discover authorized Resources quickly.
* authoritative domains remain independent from Search.
* Projections can be rebuilt safely.
* stale documents can be detected.
* failed Projection updates can be retried.
* result authorization prevents data leakage.
* tenant isolation is preserved.
* sensitive content remains protected.
* PostgreSQL can support the initial product.
* a dedicated search provider can be introduced later without changing domain contracts.

---

# Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/05-event-signal-schema.md`
* `docs/database/06-alert-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/08-knowledge-schema.md`
* `docs/database/09-agent-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/15-retention-archival.md`
* `docs/database/16-indexing-strategy.md`
* `docs/functional/12-search.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/09-scaling-strategy.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
