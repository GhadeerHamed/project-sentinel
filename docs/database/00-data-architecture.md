# Data Architecture

## Purpose

This document defines the persistence principles for Project Sentinel.

It establishes how Sentinel stores, owns, protects, changes, and retrieves operational data.

The data architecture must support:

* Multi-tenancy.
* domain ownership.
* evidence traceability.
* transactional consistency.
* event-driven processing.
* historical reconstruction.
* controlled retention.
* independent scaling.
* framework-agnostic Core domains.

This document defines platform-wide rules.

Detailed tables and relationships are defined in the following database documents.

---

## Objectives

The data architecture enables Sentinel to:

* Preserve strict Organization isolation.
* maintain clear ownership of every record.
* keep authoritative data separate from derived data.
* preserve immutable operational evidence.
* support reliable domain transactions.
* publish domain events safely.
* prevent duplicate business effects.
* support optimistic concurrency.
* retain lifecycle and decision history.
* support Search without making Search authoritative.
* support future scaling without premature complexity.
* remain compatible with multiple runtime frameworks.

---

## Initial Database Strategy

Sentinel should initially use one relational database as the primary transactional store.

Recommended initial database:

```text
PostgreSQL
```

The initial architecture should use:

* One logical database.
* modular schemas or clear table naming.
* Organization-scoped records.
* relational constraints.
* transactional consistency.
* asynchronous projections.
* background workers.
* an Outbox Pattern for event publication.

The initial implementation should not introduce:

* One database per domain.
* one database per Organization.
* distributed transactions.
* multiple database technologies without a clear need.
* event sourcing for every Resource.
* a separate analytics warehouse.

These may be introduced later when scale or customer requirements justify them.

---

## Core Principles

### Domain Ownership

Every authoritative record belongs to one domain capability.

Examples:

| Record              | Owning Capability       |
| ------------------- | ----------------------- |
| Organization        | Organization Management |
| Project             | Project Management      |
| Application         | Application Management  |
| Environment         | Environment Management  |
| Event               | Event Processing        |
| Signal              | Event Processing        |
| Alert               | Alert Management        |
| Incident            | Incident Management     |
| Knowledge Entry     | Knowledge Management    |
| Agent Definition    | Agent Platform          |
| Plugin Installation | Plugin Platform         |
| Notification        | Notification Platform   |
| Workflow Instance   | Operational Workflows   |

Only the owning capability may change the authoritative state of its records.

Other capabilities interact through:

* Application commands.
* domain events.
* stable contracts.
* authorized queries.

Direct cross-domain table updates are prohibited.

---

### Organization Isolation

Every Organization-owned record must include an explicit Organization identifier.

Example:

```text
organization_id
```

Organization scope must be enforced through:

* Application-layer authorization.
* query constraints.
* database constraints where practical.
* tests.
* background-job context.
* event metadata.
* Search Projection ownership.

A record identifier alone must never be sufficient to access Organization data.

---

### Authoritative and Derived Data

Sentinel data is divided into two categories.

#### Authoritative Data

Authoritative Data represents the official business state.

Examples:

* Current Alert status.
* Incident severity.
* active Membership.
* published Knowledge Version.
* Plugin Installation state.
* Workflow Step state.

Authoritative Data is changed only by its owning capability.

#### Derived Data

Derived Data is generated from authoritative records.

Examples:

* Search Projections.
* dashboards.
* health summaries.
* reporting aggregates.
* cached permission results.
* semantic representations.
* usage summaries.

Derived Data may be rebuilt.

It must not become the source of truth.

---

## Data Categories

Sentinel stores several kinds of data.

| Category         | Description                                          |
| ---------------- | ---------------------------------------------------- |
| Master Data      | Organizations, Projects, Applications, Environments  |
| Operational Data | Events, Signals, Alerts, Incidents                   |
| Governance Data  | Policies, approvals, Roles, permissions              |
| Knowledge Data   | Knowledge Entries and Versions                       |
| Execution Data   | Agent, Plugin, Notification, and Workflow Executions |
| Evidence Data    | Immutable records supporting decisions               |
| Historical Data  | Lifecycle transitions and previous Versions          |
| Audit Data       | Security and administrative activity                 |
| Derived Data     | Search, dashboards, summaries, and analytics         |

Each category may have different:

* Write patterns.
* indexing needs.
* retention periods.
* sensitivity.
* scaling requirements.

---

## Record Identity

Every primary business record should use a globally unique identifier.

Recommended format:

```text
UUID
```

Identifiers should be:

* Generated independently from database sequence order.
* safe to expose through authorized APIs.
* unique across Organizations.
* stable for the life of the record.
* treated as identifiers, not authorization evidence.

Internal sequential keys may be used only when justified by performance and hidden from external contracts.

---

## Organization Identifiers

Organization-owned tables should include:

```text
id
organization_id
```

Related unique constraints should usually include `organization_id`.

Example:

```text
UNIQUE (organization_id, external_reference)
```

This prevents one Organization from affecting another Organization’s uniqueness rules.

---

## External Identifiers

Records imported from external systems may include:

```text
external_provider
external_id
external_reference
```

External identifiers must be scoped by:

* Organization.
* Plugin Installation or provider.
* Resource Type.

An external identifier must not be assumed globally unique unless the provider contract guarantees it.

---

## Standard Record Fields

Most mutable authoritative records should include:

```text
id
organization_id
created_at
updated_at
version
```

Depending on the Resource, they may also include:

```text
created_by
updated_by
archived_at
deleted_at
```

Fields should be added only when their business meaning is clear.

---

## Time Representation

All stored timestamps must use UTC.

Business-facing timezones are applied during presentation or scheduling.

Records may distinguish:

```text
occurred_at
recorded_at
created_at
updated_at
processed_at
```

These timestamps represent different meanings.

For example:

* `occurred_at`: when an operational fact happened.
* `recorded_at`: when Sentinel received or recorded it.
* `processed_at`: when processing completed.
* `created_at`: when the database record was created.

These fields must not be treated as interchangeable.

---

## Lifecycle State

Resources with business lifecycles should store one current state.

Examples:

```text
status
lifecycle_status
health_status
```

The current state is authoritative.

Previous state transitions must be preserved separately when historical reconstruction is required.

State values should use stable business semantics.

Database-specific enum types should be used cautiously because they can complicate migrations and provider portability.

---

## Versioning

Versioning is used for two different purposes.

### Optimistic Concurrency Version

Mutable records should include an incrementing version value where concurrent updates are possible.

Example:

```text
version
```

An update succeeds only when the expected Version matches the current Version.

### Business Version

Immutable definitions and published content may use explicit business Versions.

Examples:

* Knowledge Version.
* Agent Version.
* Plugin Version.
* Workflow Version.
* Configuration Version.

Business Versions represent historical domain artifacts.

They are separate from optimistic concurrency Versions.

---

## Immutable Records

Some records should be append-only after creation.

Examples include:

* Canonical Events.
* Alert history.
* Incident timeline entries.
* Knowledge Versions.
* Agent Execution results.
* approval decisions.
* audit records.
* Workflow Step history.

Corrections should normally create a new record referencing the incorrect record.

Historical evidence must not be silently overwritten.

---

## Evidence

Evidence records support operational conclusions and decisions.

Evidence may include references to:

* Events.
* Signals.
* Alerts.
* Incidents.
* deployment records.
* Logs.
* Metrics.
* Agent Findings.
* Plugin results.
* Notification results.
* audit records.

Evidence should normally be referenced rather than copied.

An evidence reference should preserve:

```text
source_type
source_id
organization_id
relationship_type
created_at
```

Copied evidence snapshots may be used only when needed for:

* External data preservation.
* legal requirements.
* provider data expiration.
* historical reproducibility.

---

## History and Audit

Domain History and Audit are separate concerns.

### Domain History

Domain History explains how a business Resource changed.

Examples:

* Alert status changed.
* Incident severity changed.
* Knowledge Version published.
* Workflow Task reassigned.

### Audit

Audit explains who or what performed a protected or administrative action.

Examples:

* Role assigned.
* credential revoked.
* Plugin suspended.
* retention Policy changed.
* sensitive data exported.

One action may produce both:

* A domain history record.
* an Audit Record.

They should not be treated as the same data model.

---

## Relationships

Relationships should use explicit foreign keys where the target belongs to the same transactional database and lifecycle.

Foreign keys should protect:

* Ownership.
* referential integrity.
* required parent-child relationships.

Cross-domain relationships should be used carefully.

A capability may reference another capability’s Resource by identifier without gaining permission to modify it.

---

## Polymorphic References

Generic references may be required for:

* Evidence.
* Audit targets.
* Notifications.
* Workflow context.
* external relationships.

A generic reference may contain:

```text
resource_type
resource_id
```

Polymorphic references should not replace normal foreign keys where the relationship is stable and known.

They require application-level validation because normal database foreign keys cannot protect every target type.

---

## Parent and Child Records

Parent-child relationships should define deletion and lifecycle behavior explicitly.

Possible behaviors include:

* Prevent parent deletion.
* archive children with the parent.
* detach optional children.
* preserve historical children.
* delete temporary dependent data.

Cascade deletion should not be used for important operational history without explicit justification.

---

## Transaction Boundaries

A transaction should normally update data owned by one capability.

A domain transaction may include:

* The aggregate state change.
* related history records.
* required internal records.
* Outbox messages.
* idempotency records.

Transactions should remain:

* Small.
* bounded.
* deterministic.
* independent from external providers.

External API calls must not remain open inside database transactions.

---

## Aggregate Boundaries

An Aggregate defines the consistency boundary for a business operation.

Examples may include:

* Organization.
* Application.
* Alert.
* Incident.
* Knowledge Entry.
* Plugin Installation.
* Workflow Instance.

One transaction should normally modify one Aggregate.

Changes across multiple Aggregates should use:

* Application coordination.
* domain events.
* Workflows.
* asynchronous processing.

---

## Cross-Capability Consistency

Sentinel should use eventual consistency between capabilities when immediate atomic consistency is not required.

Example:

```text
Alert linked to Incident
```

Possible flow:

1. Incident Management creates the Incident.
2. An event is published.
3. Alert Management records the Incident relationship.
4. Search updates its Projections.

Temporary differences between capabilities must be:

* Expected.
* observable.
* recoverable.
* reconcilable.

---

## Outbox Pattern

Domain events must be recorded through an Outbox Pattern.

Within the same database transaction, Sentinel should:

1. Change authoritative domain state.
2. Create the domain history record.
3. Add an Outbox message.
4. Commit the transaction.

A background publisher then publishes the Outbox message.

This prevents domain state from committing without its required event record.

---

## Outbox Record

An Outbox record should include:

```text
id
organization_id
event_type
aggregate_type
aggregate_id
aggregate_version
payload
occurred_at
created_at
published_at
attempt_count
status
```

Outbox payloads should contain only required information.

Sensitive data should not be copied unnecessarily.

---

## Inbox and Duplicate Protection

Consumers should use Inbox or processed-message records when duplicate delivery is possible.

A processed-message record may include:

```text
consumer
message_id
processed_at
result
```

Consumers must assume that the same message can be delivered more than once.

Processing should be idempotent.

---

## Idempotency

Commands that may be repeated should support idempotency.

Examples include:

* Event ingestion.
* API creation requests.
* Plugin callbacks.
* Notification delivery.
* Workflow triggers.
* Agent execution requests.

An idempotency record should include:

```text
organization_id
operation
idempotency_key
request_fingerprint
resource_id
status
created_at
expires_at
```

The same key with a different request must be rejected.

---

## Concurrency Control

Sentinel should use optimistic concurrency by default.

An update may follow:

```text
UPDATE resource
SET
    status = :new_status,
    version = version + 1,
    updated_at = :updated_at
WHERE
    id = :id
    AND organization_id = :organization_id
    AND version = :expected_version
```

If no row is updated, the operation must fail with a concurrency conflict.

Database locks may be used for narrow operations where optimistic concurrency is insufficient.

---

## Uniqueness

Uniqueness rules must reflect business ownership.

Examples:

```text
UNIQUE (organization_id, slug)
UNIQUE (organization_id, email)
UNIQUE (organization_id, application_id, slug)
UNIQUE (organization_id, plugin_installation_id, external_id)
```

Global uniqueness should be used only for truly platform-global records.

---

## Deletion Strategy

Important operational Resources should not be physically deleted during normal use.

Possible lifecycle actions include:

* Disable.
* archive.
* revoke.
* close.
* retire.
* deprecate.

Physical deletion should be reserved for:

* Retention expiration.
* privacy requirements.
* temporary data.
* invalid incomplete setup data.
* derived data that can be rebuilt.

---

## Soft Deletion

Soft deletion may be used when:

* Restoration is required.
* temporary hiding is needed.
* dependent records must remain.
* historical references must continue working.

Soft deletion should not be applied automatically to every table.

A `deleted_at` field does not replace a proper business lifecycle.

For example:

* An Alert is Closed, not soft-deleted.
* A Plugin is Disabled or Archived.
* A Membership is Removed.
* A credential is Revoked.

---

## Archival

Archival removes inactive data from normal operational views while preserving history.

Archived data may remain in:

* The primary database.
* archive partitions.
* lower-cost storage.
* compliant external storage.

Archived records must preserve:

* Organization ownership.
* identifiers.
* evidence relationships.
* retention deadlines.
* access restrictions.

---

## Retention

Every major data category must have a defined retention rule.

Retention should consider:

* Organization configuration.
* platform requirements.
* contractual requirements.
* legal requirements.
* security requirements.
* operational usefulness.
* storage cost.

Retention processing must also update:

* Search Projections.
* caches.
* derived summaries.
* external exports where governed.
* object storage.

---

## Legal Hold

Records under Legal Hold must not be deleted by normal retention processing.

Legal Hold must identify:

```text
organization_id
scope
reason
created_by
created_at
released_at
```

Legal Hold operations are privileged and auditable.

---

## Sensitive Data

Sensitive data may include:

* Credentials.
* personal data.
* customer identifiers.
* security findings.
* restricted Incident evidence.
* provider tokens.
* confidential Agent context.
* billing information.

Sensitive data should be:

* Minimized.
* classified.
* access-controlled.
* encrypted where required.
* excluded from Logs.
* excluded from unnecessary events.
* retained only as long as required.

---

## Credentials and Secrets

Credentials must not be stored as normal plaintext database fields.

Database records should store only:

* Secret reference.
* credential type.
* owner.
* scope.
* status.
* creation time.
* rotation time.
* expiration.
* revocation time.

Actual secret values should be stored using an approved secret-management mechanism.

---

## Encryption

Sentinel should use:

* Encryption in transit.
* encryption at rest.
* application-level encryption for selected highly sensitive fields where required.

Encrypted fields must still support:

* Key rotation.
* access control.
* backup recovery.
* auditability.

Sensitive plaintext must not appear in:

* Logs.
* Outbox payloads.
* Search Projections.
* error messages.
* telemetry.

---

## Search Data

Search uses derived Search Projections.

Search Projections may duplicate selected searchable fields from authoritative Resources.

They must preserve:

```text
organization_id
resource_type
resource_id
source_version
indexed_at
sensitivity
```

Search data may be deleted and rebuilt.

Search must not be used as the authoritative store for business operations.

---

## Analytics Data

Operational analytics should initially use:

* Read models.
* database views.
* scheduled aggregates.
* bounded reporting queries.

A dedicated analytics database or warehouse should be introduced only when transactional performance or reporting requirements justify it.

Analytics processing must not block Core operational transactions.

---

## Caching

Caches may store derived or frequently accessed data.

Examples include:

* Authorization results.
* Organization configuration.
* current ownership.
* feature access.
* Search suggestions.
* health summaries.

Cached data must define:

* Source of truth.
* expiration.
* invalidation behavior.
* stale-data risk.
* Organization scope.

Caches must not be the only copy of authoritative data.

---

## Indexing Principles

Indexes should support known query patterns.

Common index prefixes should begin with:

```text
organization_id
```

Examples:

```text
(organization_id, status)
(organization_id, created_at)
(organization_id, application_id, occurred_at)
(organization_id, incident_id)
```

Indexes should not be added without a real query requirement.

Excessive indexes increase:

* Storage.
* write cost.
* migration time.
* maintenance complexity.

Detailed indexing rules are defined in:

```text
docs/database/16-indexing-strategy.md
```

---

## Partitioning

Large append-heavy tables may later require partitioning.

Likely candidates include:

* Events.
* Signals.
* Audit Records.
* Agent Executions.
* Plugin Executions.
* Notification Attempts.
* Workflow history.

Partitioning may use:

* Time.
* Organization.
* region.
* lifecycle state.

Partitioning should be introduced only when table volume and query behavior justify it.

Application contracts must not depend on one physical partitioning strategy.

---

## Read and Write Patterns

Database design should reflect expected usage.

### Write-Heavy Data

Examples:

* Events.
* Signals.
* execution records.
* delivery attempts.
* Audit Records.

These require:

* Efficient inserts.
* bounded indexes.
* partitioning readiness.
* retention processing.

### Read-Write Data

Examples:

* Alerts.
* Incidents.
* Memberships.
* Plugin Installations.
* Workflow Instances.

These require:

* Concurrency protection.
* lifecycle indexes.
* ownership indexes.
* history records.

### Read-Heavy Data

Examples:

* Published Knowledge.
* configuration.
* Search Projections.
* reporting aggregates.

These may benefit from:

* Caching.
* Read Models.
* replicas.
* specialized indexes.

---

## Database Migrations

Every schema change must use a version-controlled migration.

Migrations must be:

* Repeatable through deployment automation.
* ordered.
* reviewed.
* tested with realistic data volume.
* compatible with rollback or forward recovery.
* safe for active production workloads.

Production migrations should avoid:

* Long blocking table rewrites.
* unbounded data updates.
* immediate removal of fields still used by running code.
* destructive changes without backup or recovery planning.

---

## Expand and Contract Changes

Risky schema changes should use an expand-and-contract process.

Example:

1. Add the new column or table.
2. deploy code supporting old and new structures.
3. backfill data.
4. switch reads and writes.
5. verify behavior.
6. stop using the old structure.
7. remove the old structure in a later release.

This supports safe rolling deployments.

---

## Data Backfills

Backfills must be:

* Resumable.
* idempotent.
* observable.
* rate-limited.
* scoped.
* auditable where sensitive.
* safe to retry.

Large backfills should not run inside one transaction.

---

## Backup and Recovery

The database strategy must support:

* Automated backups.
* point-in-time recovery.
* backup encryption.
* restoration testing.
* documented recovery objectives.
* Organization data recovery where technically possible.
* recovery of Outbox and execution state.

A backup is not considered reliable until restoration is tested.

---

## Failure Handling

Database failure must not create silent business success.

When persistence fails:

* The transaction must fail.
* the caller receives a controlled error.
* no success event is published.
* no external action should be confirmed as completed.
* retries must follow idempotency rules.
* operational telemetry should record the failure.

---

## Scaling Strategy

Database scaling should proceed incrementally.

Recommended order:

1. Improve schema and queries.
2. add appropriate indexes.
3. remove inefficient access patterns.
4. introduce caching.
5. move heavy work to asynchronous processing.
6. add read replicas.
7. partition large tables.
8. isolate heavy modules when justified.
9. introduce additional databases only when necessary.

Premature database distribution should be avoided.

---

## Framework Independence

The Core data model must not depend on:

* Laravel model behavior.
* Eloquent events.
* framework-specific polymorphic naming.
* automatic timestamp assumptions.
* hidden global scopes.
* framework-generated cascade behavior.

Laravel may implement persistence adapters.

The domain contracts and data meaning must remain framework-independent.

---

## Prohibited Practices

The following practices are prohibited:

* Accessing records without Organization scope.
* using Search as authoritative state.
* direct cross-domain table updates.
* storing plaintext credentials.
* treating identifiers as authorization.
* overwriting immutable evidence.
* deleting operational history without retention rules.
* publishing events before transaction commit.
* calling external providers inside long transactions.
* unsafe automatic retries.
* unbounded queries.
* unversioned schema changes.
* relying only on application code for critical uniqueness.
* hiding failed data processing.
* adding distributed databases without demonstrated need.

---

## Business Rules

* Every Organization-owned record includes `organization_id`.
* Every authoritative record has one owning capability.
* Only the owning capability may change authoritative state.
* Derived data must reference its authoritative source.
* Derived data must be rebuildable where practical.
* Record identifiers do not grant access.
* Timestamps are stored in UTC.
* Occurrence time and recording time remain distinct.
* Immutable evidence must not be overwritten.
* Historical Versions remain traceable.
* Domain History and Audit remain separate.
* Cross-capability operations use stable contracts.
* Domain events are published through the Outbox Pattern.
* Message consumers must tolerate duplicate delivery.
* Protected repeated operations require idempotency.
* Concurrent updates must not silently overwrite each other.
* Important operational Resources use lifecycle states instead of deletion.
* Retention processing must preserve Legal Holds.
* Sensitive data must be minimized and protected.
* Credentials must not be stored in plaintext.
* Search and analytics must not become authoritative stores.
* Schema changes require version-controlled migrations.
* Database scaling must be driven by measured need.
* When Organization ownership cannot be determined, the operation is rejected.

---

## Success Criteria

The data architecture is successful when:

* Organization data remains isolated.
* domain ownership is clear.
* authoritative and derived data remain separate.
* operational evidence remains traceable.
* concurrent changes are protected.
* duplicate requests do not create duplicate effects.
* domain events are published reliably.
* failed processing remains recoverable.
* historical state can be reconstructed.
* retention can be enforced safely.
* sensitive data remains protected.
* Search and reporting scale independently.
* database changes can be deployed safely.
* the system can scale without redesigning every domain.
* persistence remains independent from the Laravel runtime.

---

## Related Documents

* `docs/07-domain-model.md`
* `docs/08-software-requirements.md`
* `docs/architecture/02-domain-architecture.md`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/05-event-model.md`
* `docs/architecture/08-security-architecture.md`
* `docs/architecture/09-scaling-strategy.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/functional/15-observability.md`
* `docs/functional/17-operational-workflows.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/05-event-signal-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/database/16-indexing-strategy.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-005-domain-driven-design.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-009-core-is-framework-agnostic.md`
* `docs/adr/ADR-011-canonical-event-model.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
