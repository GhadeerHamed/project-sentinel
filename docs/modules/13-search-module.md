# Search Module

## Purpose

The Search module provides cross-module discovery using derived, authorization-aware Search Projections.

Search improves retrieval but never becomes the authoritative source of domain state.

---

## Owns

* Search Projection definitions.
* Search documents.
* indexing jobs.
* indexing checkpoints.
* Projection rebuild state.
* Search query execution.
* indexing failure records.

Source Resources remain owned by their domain modules.

---

## Responsibilities

The module is responsible for:

* Building searchable Projections.
* processing domain events.
* executing tenant-scoped Search.
* applying authorization filters.
* supporting filters, sorting, facets, and suggestions.
* rebuilding indexes from authoritative data.
* removing stale or deleted documents.
* monitoring indexing lag and failures.

---

## Does Not Own

The module does not own:

* Applications.
* Events.
* Alerts.
* Incidents.
* Knowledge.
* Agents.
* Plugins.
* Workflow state.
* authoritative lifecycle decisions.

Search results must reference owning modules for current state.

---

## Search Document

A Search document should contain:

```text
document_id
organization_id
resource_type
resource_id
title
summary
searchable_text
status
classification
application_id
environment_id
indexed_at
source_version
```

Only fields approved for Search may be indexed.

---

## Supported Resource Types

Initial Search may include:

```text
project
application
environment
event
signal
alert
incident
knowledge
agent
plugin
workflow
```

Each owning module defines the fields eligible for indexing.

---

## Commands

```text
IndexResource
RemoveResourceFromIndex
RebuildProjection
RebuildOrganizationProjection
RetryIndexingFailure
```

Indexing commands must be idempotent.

---

## Queries

```text
SearchResources
SearchResourceType
GetSearchSuggestions
GetSearchFacets
GetIndexingStatus
```

Search queries must include Tenant Context.

---

## Published Events

```text
search.document_indexed
search.document_removed
search.rebuild_started
search.rebuild_completed
search.rebuild_failed
search.indexing_failed
```

These events describe Projection behavior, not source-domain changes.

---

## Consumed Events

The module may consume lifecycle events such as:

```text
project.created
project.updated
application.created
environment.updated
event.accepted
signal.detected
alert.created
alert.updated
incident.created
incident.updated
knowledge.published
knowledge.archived
agent.updated
plugin.installed
workflow.updated
```

Each consumer must tolerate duplicate delivery.

---

## Projection Rules

Every Projection must define:

* Source module.
* source Resource.
* indexed fields.
* sensitivity rules.
* deletion behavior.
* source Version.
* rebuild strategy.

Projection logic should remain deterministic where practical.

---

## Source Versioning

Every document should store the latest indexed source Version.

An indexing operation must not overwrite a newer document with an older event.

Stale events should be ignored safely.

---

## Tenant Isolation

Every Search document includes:

```text
organization_id
```

Every Search query must filter by the active Organization before returning:

* Results.
* counts.
* facets.
* suggestions.
* related Resources.
* semantic matches.

Cross-Organization Search is prohibited.

---

## Authorization

Search results must respect:

* Identity permissions.
* Project scope.
* Application scope.
* Environment scope.
* Resource classification.
* field-level restrictions.
* Resource lifecycle state.

A document being indexed does not make it visible to every Organization Member.

---

## Result Validation

For highly sensitive or state-changing use cases, the caller should reload the Resource from its owning module.

Search results may be stale because indexing is asynchronous.

Search must not be used to authorize an operation.

---

## Classification

Search documents inherit the classification of their source data.

Derived summaries must not reduce source sensitivity.

Restricted content may require:

* Separate fields.
* separate indexes.
* stronger query filters.
* omission from general Search.

---

## Knowledge Search

Only eligible published Knowledge should appear in normal Search.

Draft, rejected, or restricted Knowledge must follow explicit visibility rules.

Search must preserve Knowledge Version and classification.

---

## Event Search

Event and Signal indexing should be selective.

Avoid indexing:

* Credentials.
* unrestricted raw payloads.
* personal data without need.
* large binary content.
* secrets.
* provider-specific noise.

Searchable Event attributes should come from normalized canonical fields.

---

## Semantic Search

Semantic or vector Search may be added later.

It must preserve:

* Organization filtering.
* classification.
* deletion.
* retention.
* source references.
* embedding provider security.

Cross-tenant nearest-neighbor results are prohibited.

---

## Indexing Flow

Typical flow:

```text
Domain event published
→ indexing job queued
→ source state loaded
→ authorization-safe document built
→ document indexed
→ checkpoint updated
```

Search indexing must not delay the source-domain transaction.

---

## Rebuilds

Every Projection must be rebuildable from authoritative data.

A rebuild should:

* Support one Organization or Resource type.
* process bounded batches.
* maintain checkpoints.
* tolerate interruption.
* avoid duplicate documents.
* validate completion.
* replace stale indexes safely.

---

## Deletion

When a source Resource is deleted, archived, or made ineligible:

* Remove or update its Search document.
* remove related suggestions.
* remove semantic embeddings.
* preserve only data required by retention Policy.

Legal Hold may affect source deletion but does not automatically require Search visibility.

---

## Failure Handling

Indexing failures should record:

```text
organization_id
resource_type
resource_id
source_version
failure_class
attempts
failed_at
```

Transient failures may retry with bounded backoff.

Permanent failures require investigation or corrected source data.

---

## Repository Interfaces

```text
SearchDocumentRepository
SearchQueryRepository
ProjectionCheckpointRepository
IndexingFailureRepository
```

These repositories manage derived Search state only.

---

## Audit Requirements

Audit Records may be required for:

* Restricted Search access.
* sensitive exports from Search.
* manual Projection rebuilds.
* privileged cross-tenant platform investigations.
* Search configuration changes.

Routine Search queries use operational telemetry unless stricter Policy applies.

---

## Testing Requirements

Tests must cover:

* Tenant filtering.
* authorization filtering.
* stale event rejection.
* duplicate event handling.
* document deletion.
* Projection rebuild.
* classification preservation.
* indexing failure recovery.
* Search result revalidation.
* semantic Search isolation when introduced.

---

## Prohibited Practices

The Search module must not:

* Become authoritative storage.
* authorize domain Actions.
* index secrets.
* return cross-tenant counts or suggestions.
* overwrite newer documents with stale events.
* expose Draft or restricted content incorrectly.
* require source modules to write Search storage directly.
* retain deleted derived data indefinitely.

---

## Business Rules

* Search is derived and rebuildable.
* every Search document belongs to one Organization.
* Search results preserve source authorization and classification.
* source modules define eligible indexed fields.
* indexing is asynchronous and idempotent.
* stale events cannot overwrite newer Projections.
* sensitive operations reload authoritative state.
* deletion and retention propagate to Search.
* Search infrastructure may change without changing domain ownership.

---

## Related Documents

* `docs/modules/09-knowledge-module.md`
* `docs/modules/16-observability-module.md`
* `docs/database/14-search-projections.md`
* `docs/api/17-search-api.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/06-data-protection.md`
* `docs/deployment/07-monitoring-observability.md`
