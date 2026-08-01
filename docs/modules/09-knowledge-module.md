# Knowledge Module

## Purpose

The Knowledge module owns reusable operational knowledge created from Incidents, evidence, investigations, and expert input.

It converts resolved operational experience into searchable guidance for future response.

---

## Owns

* Knowledge Article.
* Knowledge Version.
* publication status.
* Knowledge classification.
* source references.
* tags and categories.
* review state.
* archival state.

---

## Does Not Own

* Incident lifecycle.
* Event, Signal, or Alert evidence.
* Search infrastructure.
* Agent execution.
* file storage.
* Workflow execution.

The module references source Resources without modifying them.

---

## Knowledge Article

Important attributes:

```text
id
organization_id
title
summary
content
status
classification
source_type
source_id
created_by
reviewed_by
published_at
version
```

The primary identifier uses UUIDv7.

---

## Lifecycle

Suggested states:

```text
draft
in_review
published
archived
```

Typical transitions:

```text
draft → in_review
in_review → draft
in_review → published
published → archived
```

Published content must remain versioned.

---

## Commands

```text
CreateKnowledgeArticle
UpdateKnowledgeDraft
SubmitKnowledgeForReview
ApproveKnowledgeArticle
RejectKnowledgeArticle
PublishKnowledgeArticle
CreateKnowledgeVersion
ArchiveKnowledgeArticle
AttachKnowledgeSource
AddKnowledgeTag
RemoveKnowledgeTag
```

Published Versions must not be edited in place.

---

## Queries

```text
GetKnowledgeArticle
GetKnowledgeVersion
ListKnowledgeArticles
ListKnowledgeVersions
ListKnowledgeBySource
ListKnowledgeByTag
```

Search across Knowledge is provided through the Search module.

---

## Published Events

```text
knowledge.created
knowledge.review_requested
knowledge.review_rejected
knowledge.published
knowledge.version_created
knowledge.archived
knowledge.source_attached
```

Events must include:

```text
organization_id
knowledge_id
version_id where applicable
event_id
occurred_at
correlation_id
causation_id
event_version
```

---

## Consumed Events

The module may consume:

```text
incident.resolved
incident.closed
organization.suspended
organization.archived
```

Incident closure may create a Knowledge drafting opportunity.

It must not automatically publish content.

---

## Incident Integration

Knowledge may reference:

* Incident.
* Incident timeline.
* Alerts.
* Events and Signals.
* remediation Actions.
* Post-Incident Review.

The Knowledge module must not copy unrestricted evidence when references are sufficient.

Source access remains subject to the source module’s authorization rules.

---

## Versioning

Every published change creates a new immutable Knowledge Version.

A Version should record:

```text
version_number
title
summary
content
created_by
created_at
change_note
```

Historical published Versions remain available according to retention Policy.

---

## Review and Publication

Publishing requires:

* Complete content.
* authorized reviewer.
* valid source references.
* sensitivity classification.
* no unresolved restricted content.
* current review approval.

Review and author identities should remain distinct where Policy requires it.

---

## Classification

Suggested classifications:

```text
internal
confidential
restricted
```

Classification controls:

* Who may read the article.
* whether Agents may use it.
* whether Search may index its content.
* whether it may appear in exports.
* which source evidence may be exposed.

---

## Agent Integration

Agents may:

* Search authorized Knowledge.
* summarize published content.
* recommend relevant articles.
* draft new Knowledge from Incident evidence.
* propose updates.

Agent-generated content remains a Draft until reviewed and published through Knowledge commands.

---

## Search Integration

The Search module may index published Knowledge fields such as:

* Title.
* summary.
* content.
* tags.
* source references.
* classification.
* publication timestamp.

Search must preserve:

* Organization scope.
* classification.
* authorization.
* archival state.
* Version selection.

Draft content should not be generally searchable.

---

## Workflow Integration

Workflows may coordinate:

* Draft creation after Incident resolution.
* review assignment.
* approval.
* scheduled review.
* archival.

Workflow execution must invoke Knowledge commands and cannot write Knowledge state directly.

---

## Repository Interfaces

```text
KnowledgeRepository
KnowledgeVersionRepository
KnowledgeSourceRepository
KnowledgeReviewRepository
```

Other modules must not write Knowledge persistence directly.

---

## Authorization

Typical permissions:

```text
knowledge.create
knowledge.read
knowledge.update
knowledge.review
knowledge.publish
knowledge.archive
knowledge.restricted.read
```

Publication and restricted-content access require elevated authorization.

---

## Tenant Isolation

Every Knowledge Article, Version, tag, and source reference belongs to one Organization.

Referenced Incidents, Alerts, Events, and files must belong to the same Organization.

---

## Audit Requirements

Audit Records are required for:

* Publication.
* rejection.
* Version creation.
* classification changes.
* archival.
* restricted-content access.
* privileged source access.

Draft editing history may remain in Knowledge Version history where sufficient.

---

## Prohibited Practices

The Knowledge module must not:

* Edit published Versions in place.
* publish Agent-generated content automatically.
* copy credentials or secrets from evidence.
* reference cross-Organization Resources.
* expose restricted evidence through summaries.
* use Search as authoritative storage.
* modify Incident or Alert state.
* allow publication without authorization.

---

## Business Rules

* Knowledge belongs to one Organization.
* published Knowledge is immutable and versioned.
* Draft and published content remain separate.
* Incident resolution may initiate drafting but not automatic publication.
* source evidence remains owned by its original module.
* classification follows the sensitivity of included source material.
* Agents may draft but cannot publish independently.
* Search indexes only authorized and eligible content.
* publication and classification changes are auditable.

---

## Related Documents

* `docs/modules/08-incident-module.md`
* `docs/modules/10-agent-module.md`
* `docs/modules/13-search-module.md`
* `docs/modules/17-workflow-module.md`
* `docs/database/08-knowledge-schema.md`
* `docs/api/13-knowledge-api.md`
* `docs/security/06-data-protection.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
