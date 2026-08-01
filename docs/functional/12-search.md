# Search

## Purpose

Search enables authorized users, Agents, and platform capabilities to discover operational information across Project Sentinel.

Search may return information from:

* Events.
* Signals.
* Alerts.
* Incidents.
* Knowledge.
* Applications.
* Environments.
* Plugins.
* Agents.
* Notifications.
* Workflows.
* Audit records.

Search is a discovery capability.

It does not replace the authoritative business capabilities that own the returned records.

---

## Objectives

Search enables Sentinel to:

* Discover operational records quickly.
* Search across multiple Resource Types.
* Support text and structured queries.
* Filter results by operational context.
* Rank results by relevance.
* preserve Organization isolation.
* enforce authorization on every result.
* support historical investigation.
* retrieve relevant Knowledge.
* support Agent context retrieval.
* provide traceable links to authoritative records.
* remain available independently from transactional storage.

---

## Responsibilities

Search is responsible for:

* Accepting Search Requests.
* validating queries.
* resolving Search Scope.
* enforcing authorization.
* applying filters.
* retrieving matching records.
* ranking results.
* grouping and faceting results.
* managing Search Projections.
* managing Search Indexes.
* tracking index freshness.
* supporting pagination.
* managing Saved Searches.
* publishing Search health events.
* preserving Search audit records where required.

---

## Functional Boundaries

Search does not:

* Own authoritative business records.
* modify Events, Alerts, Incidents, or Knowledge.
* bypass Authentication and Authorization.
* expose cross-Organization information.
* guarantee immediate indexing.
* treat Search Results as authoritative state.
* replace operational analytics.
* replace business reporting.
* infer facts unsupported by source records.
* preserve deleted source content improperly.

The owning capability remains authoritative for every returned Resource.

---

## Position in the Platform

```text
Authoritative Business Records

↓

Search Projections

↓

Authorized Search Query

↓

Ranked Search Results

↓

Authoritative Resource Access
```

Search Projections are derived data.

The original Resource remains the source of truth.

---

## Core Concepts

| Concept           | Description                                          |
| ----------------- | ---------------------------------------------------- |
| Search Request    | A request to discover matching Resources             |
| Search Query      | The text or structured expression used for discovery |
| Search Scope      | The Organization and Resources included in the query |
| Search Filter     | A condition restricting matching results             |
| Search Result     | A reference to a matching authoritative Resource     |
| Search Projection | A searchable representation of a Resource            |
| Search Index      | A managed collection of Search Projections           |
| Ranking           | The ordering of matching results                     |
| Facet             | A grouped value used to refine results               |
| Saved Search      | A reusable Search Request                            |
| Search Freshness  | How closely the index reflects current source state  |
| Semantic Search   | Meaning-based discovery where supported              |

---

## Search Ownership

Every Search Request executes within one Organization context.

Every Search Projection inherits the Organization ownership of its source Resource.

Organization context establishes:

* Tenant isolation.
* authorization boundaries.
* Search Scope.
* Policy scope.
* audit ownership.
* retention responsibility.

Cross-Organization Search is prohibited unless a future governed platform capability explicitly supports it.

---

## Searchable Resources

Search may include:

| Resource Type   | Example Use                                    |
| --------------- | ---------------------------------------------- |
| Project         | Find operational Projects                      |
| Application     | Find monitored Applications                    |
| Environment     | Find Environment-specific records              |
| Event           | Investigate operational facts                  |
| Signal          | Discover interpreted observations              |
| Alert           | Find current or historical Alerts              |
| Incident        | Find coordinated response records              |
| Knowledge Entry | Find diagnostic or remediation guidance        |
| Plugin          | Find integrations and Plugin Health            |
| Agent Execution | Find Agent Findings and Recommendations        |
| Notification    | Find delivery and acknowledgement history      |
| Workflow        | Find operational tasks and approvals           |
| Audit Record    | Investigate authorized administrative activity |

Resource availability depends on permissions and Organization Policies.

---

## Search Request

A Search Request may include:

* Query text.
* Resource Types.
* Project.
* Application.
* Environment.
* Service.
* status.
* severity.
* priority.
* owner.
* Team.
* source.
* time range.
* lifecycle state.
* related Alert or Incident.
* sort order.
* page size.
* pagination cursor.

Invalid or unsupported Search Requests must be rejected clearly.

---

## Search Modes

Search may support:

* Keyword Search.
* Structured Search.
* Identifier Search.
* Relationship Search.
* Semantic Search.
* Contextual Search.

These modes may be combined.

---

## Keyword Search

Keyword Search may match:

* Titles.
* summaries.
* descriptions.
* identifiers.
* error messages.
* tags.
* Findings.
* Knowledge content.
* external references.

Keyword behavior should remain predictable and documented.

---

## Structured Search

Structured Search filters Resources using known business fields.

Examples include:

* Severity.
* status.
* Application.
* Environment.
* owner.
* Team.
* source.
* time range.
* lifecycle state.
* Incident relationship.

Structured fields must use stable business meaning.

---

## Identifier Search

Identifier Search locates Resources using exact identifiers such as:

* Resource identifier.
* external Event identifier.
* Alert fingerprint.
* Incident identifier.
* deployment identifier.
* Trace identifier.
* correlation identifier.
* external ticket identifier.
* Agent Execution identifier.
* Plugin Execution identifier.

Exact authorized identifier matches should receive high relevance.

---

## Relationship Search

Relationship Search discovers connected Resources.

Examples include:

* Events supporting a Signal.
* Signals supporting an Alert.
* Alerts associated with an Incident.
* Knowledge derived from an Incident.
* Agent Findings related to an Alert.
* Notifications generated by an Incident.
* Workflows created from Recommendations.

Explicit relationships must remain distinguishable from inferred similarity.

---

## Semantic Search

Semantic Search may find Resources with similar meaning even when exact terms differ.

It may help find:

* Similar Incidents.
* related Knowledge.
* recurring Alert patterns.
* similar error descriptions.
* previous remediation guidance.

Semantic similarity does not prove shared cause or identical remediation.

Semantic Search must preserve:

* Organization isolation.
* authorization.
* data sensitivity.
* provider restrictions.
* source traceability.

---

## Contextual Search

Search may use current operational context to improve relevance.

Context may include:

* Current Application.
* current Environment.
* current Alert.
* current Incident.
* current Service.
* current Agent Execution.

Context may prioritize related evidence, Knowledge, Alerts, Incidents, and recent changes.

---

## Search Scope

Search Scope may include:

* Entire Organization.
* selected Projects.
* selected Applications.
* selected Environments.
* selected Services.
* selected Resource Types.
* one Alert.
* one Incident.
* one time range.

Narrower Search Scope should improve relevance and reduce unnecessary data access.

---

## Authorization

Every Search Request must be authorized.

Authorization may consider:

* Organization membership.
* Identity Type.
* Role.
* permission.
* Resource ownership.
* Project Scope.
* Application Scope.
* Environment Scope.
* sensitivity.
* lifecycle state.
* Organization Policy.

Search must not reveal the existence of unauthorized Resources.

---

## Result-Level Authorization

Every Search Result must be authorized before return.

Result authorization protects against:

* Stale index permissions.
* ownership changes.
* sensitivity changes.
* Resource lifecycle changes.
* indexing errors.
* Policy changes.

A Resource must not be returned merely because it exists in a Search Index.

---

## Field-Level Authorization

A user may be allowed to discover a Resource while selected fields remain restricted.

Search may:

* Redact sensitive content.
* remove protected highlights.
* hide restricted relationships.
* return a limited summary.
* omit confidential fields.

Search counts, Facets, and suggestions must also respect authorization.

---

## Search Results

A Search Result should include:

* Resource identifier.
* Resource Type.
* title or primary label.
* summary.
* status.
* severity or priority where applicable.
* operational scope.
* source capability.
* creation or occurrence time.
* last update time.
* match explanation.
* authoritative Resource reference.

Results may be grouped by Resource Type or operational scope.

---

## Match Explanation

Search should explain why a result matched where practical.

Examples include:

* Identifier match.
* title match.
* content match.
* filter match.
* relationship match.
* semantic similarity.
* current operational context.

Match explanation improves trust and usability.

---

## Ranking

Search Ranking may consider:

* Exact identifier match.
* exact phrase match.
* text relevance.
* semantic similarity.
* current operational context.
* severity.
* active lifecycle state.
* recency.
* relationship strength.
* Knowledge confidence.

Ranking must not override authorization.

---

## Sorting

Search Results may be sorted by:

* Relevance.
* creation time.
* occurrence time.
* last update time.
* severity.
* priority.
* status.
* name.

Default ordering must remain deterministic.

---

## Facets

Facets may summarize results by:

* Resource Type.
* status.
* severity.
* Project.
* Application.
* Environment.
* Service.
* owner.
* Team.
* source.
* time range.
* lifecycle state.

Facet counts must include only authorized Resources.

---

## Pagination

Search Results must use bounded pagination.

Pagination should support:

* Maximum page size.
* deterministic ordering.
* protected continuation tokens.
* authorization re-evaluation.
* stable continuation where practical.

Unbounded Search Results are prohibited.

---

## Saved Searches

A Saved Search stores a reusable Search Request.

A Saved Search may include:

* Name.
* owner.
* query.
* filters.
* Resource Types.
* Search Scope.
* sort order.
* visibility.
* lifecycle status.

Saved Searches may be:

* Private.
* Team-visible.
* Project-visible.
* Organization-visible.

Saved Search visibility does not grant access to matching Resources.

Authorization is evaluated every time the Saved Search runs.

---

## Saved Search Lifecycle

Saved Searches may follow:

```text
Draft

↓

Active

↓

Disabled

↓

Archived
```

Historical scheduled executions should retain the Saved Search version used where required.

---

## Search Projections

A Search Projection is a derived searchable representation of an authoritative Resource.

A Projection may include:

* Resource identifier.
* Resource Type.
* Organization.
* searchable text.
* filterable fields.
* sortable fields.
* relationship references.
* lifecycle status.
* sensitivity.
* source version.
* index time.

Search Projections must not expand source permissions.

---

## Indexing

Indexing creates or updates Search Projections.

Indexing may be triggered by:

* Resource creation.
* Resource update.
* lifecycle change.
* ownership change.
* visibility change.
* sensitivity change.
* relationship change.
* scheduled reconciliation.
* manual rebuild.

Indexing should normally occur asynchronously.

---

## Indexing Flow

```text
Authoritative Resource Changed

↓

Search Update Event Published

↓

Search Projection Built

↓

Projection Validated

↓

Search Index Updated
```

Indexing failure must not modify or invalidate the authoritative Resource.

---

## Search Freshness

Search Freshness indicates how current an index is.

Freshness states may include:

| State      | Meaning                                          |
| ---------- | ------------------------------------------------ |
| Current    | Index reflects expected source state             |
| Delayed    | Updates are processing more slowly than expected |
| Stale      | Material source changes may be missing           |
| Rebuilding | The index is being reconstructed                 |
| Unknown    | Freshness cannot be determined                   |

Freshness should be visible where it affects result reliability.

---

## Stale Results

Search Results may be stale because indexing is asynchronous.

Before performing a protected operation from a Search Result, the platform must retrieve and authorize the current authoritative Resource.

Search Results must not be the sole basis for protected lifecycle changes.

---

## Reconciliation

Search Reconciliation compares indexes with authoritative Resources.

It may identify:

* Missing Projections.
* outdated Projections.
* deleted source Resources.
* incorrect ownership.
* incorrect sensitivity.
* broken relationships.
* unauthorized fields.

Reconciliation must not modify authoritative Resources.

---

## Index Rebuild

Indexes may be rebuilt when:

* Search schema changes.
* Projection logic changes.
* corruption is suspected.
* authorization fields change.
* semantic models change.
* migration occurs.

Rebuilds must preserve Organization isolation and source traceability.

---

## Performance Controls

Search may enforce:

* Query length limits.
* page-size limits.
* time-range limits.
* filter limits.
* query-complexity limits.
* semantic-search limits.
* concurrent-query limits.
* timeout limits.
* export limits.
* Organization Quotas.

Limits protect platform stability and tenant fairness.

---

## Search Availability

Search failure must not prevent Core business capabilities from operating.

When Search is unavailable:

* Direct authorized Resource access should continue where possible.
* Event processing should continue.
* Alert processing should continue.
* Incident response should continue.
* indexing changes may queue for later processing.
* users should receive a controlled availability status.

Search is important for discovery but is not the source of truth.

---

## Degraded Search

Search may operate in degraded mode when:

* Semantic Search is unavailable.
* one Resource Type is delayed.
* Facets are unavailable.
* suggestions are unavailable.
* an index is rebuilding.
* result freshness is reduced.

The affected behavior should be visible.

---

## Sensitive Search

Sensitive Resources may require:

* Stronger permission.
* restricted indexing.
* field redaction.
* limited highlights.
* no external semantic provider.
* enhanced audit.
* shorter cache retention.
* explicit operational purpose.

Sensitive content must not leak through counts, suggestions, or snippets.

---

## Search Auditability

The following Search activity may be audited:

* Sensitive Search Requests.
* Audit Record searches.
* restricted Resource searches.
* large exports.
* Saved Search sharing.
* Search administration.
* index rebuild.
* Projection correction.
* semantic processing of sensitive content.

Audit records may include:

* Organization.
* requesting identity.
* Search Scope.
* Resource Types.
* time.
* outcome.
* applicable Policy.

Search audit records should avoid storing unnecessary sensitive query content.

---

## Retention

Search retention applies to:

* Search Projections.
* Saved Searches.
* Search Jobs.
* Search History where enabled.
* query analytics.
* semantic representations.
* caches.
* audit records.

Derived Search data must follow the retention and deletion rules of its source Resources.

When a source Resource is deleted or restricted, related Search data must be removed or updated.

---

## Observability

The platform should measure:

* Search Request volume.
* query latency.
* query failures.
* timeout rate.
* zero-result rate.
* authorization rejection rate.
* indexing throughput.
* indexing failures.
* indexing backlog.
* index freshness.
* reconciliation errors.
* semantic provider health.

Observability must preserve Organization isolation.

---

## Capability Interactions

| Capability                       | Relationship                                     |
| -------------------------------- | ------------------------------------------------ |
| Organization Management          | Provides Organization and membership scope       |
| Project Management               | Provides Project scope                           |
| Application Management           | Provides Application ownership and metadata      |
| Environment Management           | Provides Environment context                     |
| Event Processing                 | Supplies Events and Signals                      |
| Alert Management                 | Supplies Alerts                                  |
| Incident Management              | Supplies Incidents and evidence relationships    |
| Knowledge Management             | Supplies governed Knowledge                      |
| Agent Platform                   | Uses authorized Search for operational context   |
| Plugin Platform                  | Supplies Plugin metadata and external references |
| Notification Platform            | Supplies delivery and acknowledgement history    |
| Authentication and Authorization | Protects queries and results                     |
| Administration                   | Configures Search governance and limits          |
| Observability                    | Measures Search health and freshness             |
| Public API                       | Exposes authorized Search operations             |
| Operational Workflows            | Uses Search to resolve context and ownership     |
| Policy Layer                     | Governs sensitive Search and exports             |
| Audit                            | Supplies authorized audit records                |

Search remains authoritative for Search Requests, Search Projections, ranking, Saved Searches, and index health.

Each source capability remains authoritative for the returned Resources.

---

## Business Rules

* Every Search Request executes within one Organization context.
* Every Search Projection references one authoritative Resource.
* Search Projections are derived data.
* Search must preserve Organization isolation.
* Every Search Result must be authorized.
* Search must not reveal unauthorized Resource existence.
* Counts, Facets, suggestions, and highlights must respect authorization.
* Search Results must reference authoritative Resources.
* Search must not modify source Resources.
* Stale Results must not drive protected state changes directly.
* Saved Searches do not preserve permissions.
* Semantic similarity does not prove shared cause.
* Sensitive fields must be redacted when required.
* Indexing failure must not corrupt authoritative state.
* Search unavailability must not stop Core operational processing.
* Derived Search data must follow source retention and deletion rules.
* Cross-Organization Search is prohibited.
* Unauthorized Search operations are rejected and auditable.

---

## Success Criteria

Search is successful when:

* Authorized users find relevant operational information quickly.
* Organization isolation is preserved.
* Results remain traceable to authoritative Resources.
* Sensitive information remains protected.
* Historical Incidents and relevant Knowledge are discoverable.
* Search supports active Alert and Incident investigation.
* Search freshness remains visible.
* Indexing failure does not affect authoritative business state.
* Saved Searches remain governed.
* Agents receive only authorized Search context.
* Search can scale independently from transactional workloads.

---

## Related Documents

* `docs/functional/00-platform-overview.md`
* `docs/functional/03-application-management.md`
* `docs/functional/04-environment-management.md`
* `docs/functional/05-event-processing.md`
* `docs/functional/06-alert-management.md`
* `docs/functional/07-incident-management.md`
* `docs/functional/08-knowledge-management.md`
* `docs/functional/09-agent-platform.md`
* `docs/functional/10-plugin-platform.md`
* `docs/functional/11-notification-platform.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/functional/14-administration.md`
* `docs/functional/15-observability.md`
* `docs/functional/16-public-api.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/08-security-architecture.md`
* `docs/architecture/09-scaling-strategy.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
