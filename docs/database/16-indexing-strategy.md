# Database Indexing Strategy

## Purpose

This document defines the indexing strategy for Project Sentinel.

Indexes must support:

* Tenant-scoped queries.
* operational dashboards.
* Event ingestion.
* Alert and Incident response.
* background processing.
* Search Projections.
* retention processing.
* audit investigation.
* safe uniqueness enforcement.

Indexes must improve real query patterns without creating unnecessary write and storage cost.

---

## Core Principles

* Indexes are driven by real queries.
* Tenant-owned indexes normally begin with `organization_id`.
* Index column order must match filtering and sorting behavior.
* Unique business rules must be enforced by database constraints.
* Foreign-key columns should be indexed when used for lookup or deletion checks.
* High-write tables must use a limited number of indexes.
* Partial indexes should support active-state queries.
* Derived and historical tables may use different indexing strategies.
* Indexes must be monitored after deployment.
* Unused indexes should be reviewed and removed safely.
* Framework-generated indexes must not be accepted without architectural review.

---

## Primary Database

The initial indexing strategy targets:

```text
PostgreSQL
```

PostgreSQL index types used by Sentinel may include:

* B-tree.
* GIN.
* BRIN.
* hash indexes only when clearly justified.
* partial indexes.
* expression indexes.
* covering indexes using `INCLUDE`.

B-tree indexes remain the default.

---

## Tenant-Aware Indexing

Most tenant-owned queries begin with:

```text
organization_id
```

Therefore, most composite indexes should also begin with:

```text
organization_id
```

Example:

```sql
CREATE INDEX idx_incidents_org_status_severity
ON incidents (
    organization_id,
    status,
    severity
);
```

This supports:

```sql
SELECT *
FROM incidents
WHERE organization_id = :organization_id
  AND status = :status
  AND severity = :severity;
```

---

## Exceptions to Tenant-First Ordering

`organization_id` does not need to be the first column when:

* The table is platform-owned.
* A globally unique identifier is the only lookup requirement.
* A queue worker searches across all Organizations by schedule.
* A maintenance process scans by expiration or retry time.
* A PostgreSQL BRIN index is used for time-based scanning.
* A highly selective global field must lead the index.

Example:

```sql
CREATE INDEX idx_workflow_timers_due
ON workflow_timers (
    scheduled_for,
    organization_id
)
WHERE status = 'scheduled';
```

This supports a global scheduler that finds due Timers and then restores Organization context.

---

## Index Column Order

Composite index order should reflect:

1. Equality filters.
2. range filters.
3. ordering.
4. included output columns.

Example query:

```sql
SELECT id, title, severity, updated_at
FROM alerts
WHERE organization_id = :organization_id
  AND status IN ('open', 'acknowledged', 'investigating')
  AND severity = :severity
ORDER BY updated_at DESC
LIMIT 50;
```

Suitable index:

```sql
CREATE INDEX idx_alerts_org_severity_active_updated
ON alerts (
    organization_id,
    severity,
    updated_at DESC
)
WHERE status IN (
    'open',
    'acknowledged',
    'investigating'
);
```

---

## Equality Before Range

For an index such as:

```text
(organization_id, application_id, occurred_at)
```

PostgreSQL can efficiently filter by:

* `organization_id`
* `application_id`
* a range on `occurred_at`

Example:

```sql
SELECT *
FROM events
WHERE organization_id = :organization_id
  AND application_id = :application_id
  AND occurred_at >= :from
  AND occurred_at < :to;
```

Range columns should normally appear after equality columns.

---

## Sorting

Indexes should support common sort order when useful.

Example:

```sql
CREATE INDEX idx_incident_timeline_org_incident_time
ON incident_timeline (
    organization_id,
    incident_id,
    occurred_at ASC,
    recorded_at ASC
);
```

This supports ordered Incident timeline retrieval without an additional sort.

---

## Primary Keys

Every table must have a stable primary key unless it uses a deliberate composite key.

Typical Resource primary key:

```sql
PRIMARY KEY (id)
```

Join and mapping tables may use composite keys.

Example:

```sql
PRIMARY KEY (
    organization_id,
    team_id,
    membership_id
)
```

Primary-key design must reflect identity and duplicate-prevention requirements.

---

## Tenant-Aware Unique Constraints

Business uniqueness should normally include Organization scope.

Examples:

```sql
UNIQUE (organization_id, slug)
```

```sql
UNIQUE (
    organization_id,
    project_id,
    name
)
```

```sql
UNIQUE (
    organization_id,
    plugin_installation_id,
    external_resource_type,
    external_resource_id
)
```

Global uniqueness should be used only for platform-global values.

---

## Composite Foreign Keys

Tenant-aware parent-child relationships may use:

```sql
UNIQUE (organization_id, id)
```

on the parent table.

The child may then use:

```sql
FOREIGN KEY (organization_id, application_id)
REFERENCES applications (organization_id, id)
```

Supporting indexes should exist on the child relationship when it is queried frequently.

Example:

```sql
CREATE INDEX idx_environments_org_application
ON environments (
    organization_id,
    application_id
);
```

---

## Foreign-Key Indexes

PostgreSQL does not automatically create indexes on referencing foreign-key columns.

Indexes should be created when foreign keys are used for:

* Parent-child queries.
* cascade checks.
* deletion validation.
* lifecycle processing.
* relationship traversal.

Example:

```sql
CREATE INDEX idx_alert_evidence_org_alert
ON alert_evidence (
    organization_id,
    alert_id
);
```

Not every foreign key requires a separate index if it is already covered by another composite index.

---

## Partial Indexes

Partial indexes should support frequent queries over a small active subset.

Example:

```sql
CREATE INDEX idx_alerts_org_active_updated
ON alerts (
    organization_id,
    updated_at DESC
)
WHERE status IN (
    'open',
    'acknowledged',
    'investigating',
    'suppressed'
);
```

Useful candidates include:

* Active Alerts.
* Open Incidents.
* Pending Workflow Tasks.
* Scheduled Timers.
* Active Plugin Installations.
* Expiring credentials.
* unresolved processing failures.
* pending Notification retries.
* active Legal Holds.

Partial indexes reduce:

* Index size.
* write amplification.
* scan cost.

---

## Partial Unique Indexes

Partial unique indexes may enforce uniqueness only for active Resources.

Example:

```sql
CREATE UNIQUE INDEX uq_alerts_org_active_fingerprint
ON alerts (
    organization_id,
    fingerprint
)
WHERE status IN (
    'open',
    'acknowledged',
    'investigating',
    'suppressed'
);
```

This permits historical Alerts with the same fingerprint while preventing duplicate active Alerts.

The active-state definition must match domain lifecycle rules.

---

## Covering Indexes

PostgreSQL `INCLUDE` may reduce table lookups for frequent read queries.

Example:

```sql
CREATE INDEX idx_alerts_org_status_updated
ON alerts (
    organization_id,
    status,
    updated_at DESC
)
INCLUDE (
    id,
    title,
    severity,
    priority
);
```

Covering indexes should be used only when:

* The query is frequent.
* Included columns are stable and relatively small.
* reduced heap access provides measurable benefit.

Large text or JSONB fields should not normally be included.

---

## Expression Indexes

Expression indexes may support normalized lookups.

Example:

```sql
CREATE UNIQUE INDEX uq_users_normalized_email
ON users (
    lower(email)
);
```

Preferred design is still to store a dedicated normalized field when normalization is part of the business model.

Expression indexes may also support:

* Case-insensitive identifiers.
* extracted JSONB values.
* date bucketing.
* calculated status fields.

Expressions must remain deterministic.

---

## JSONB Indexing

JSONB should be used for extensible configuration and bounded metadata.

It should not replace frequently queried relational columns.

GIN indexes may support JSONB containment queries.

Example:

```sql
CREATE INDEX idx_events_payload_gin
ON events
USING GIN (payload);
```

A broad GIN index on a high-volume Event table may be expensive.

Prefer targeted expression indexes when only specific keys are queried.

Example:

```sql
CREATE INDEX idx_events_org_payload_service
ON events (
    organization_id,
    (payload->>'service')
);
```

Frequently queried JSONB fields should eventually become dedicated columns.

---

## Full-Text Search Indexes

Search Projections may use PostgreSQL full-text search.

Example:

```sql
CREATE INDEX idx_search_documents_vector
ON search_documents
USING GIN (search_vector);
```

Tenant filters require a separate B-tree index.

Example:

```sql
CREATE INDEX idx_search_documents_org_type_status
ON search_documents (
    organization_id,
    resource_type,
    status
);
```

Search authorization remains separate from text matching.

---

## BRIN Indexes

BRIN indexes may support very large append-heavy tables where physical row order correlates with time.

Likely candidates:

* Events.
* Audit Records.
* Notification attempts.
* execution history.
* processing attempts.

Example:

```sql
CREATE INDEX idx_events_recorded_at_brin
ON events
USING BRIN (recorded_at);
```

BRIN indexes are small and cheap to maintain.

They are less precise than B-tree indexes and should support broad time-range scans rather than specific tenant lookups.

---

## Time-Based Tables

Append-heavy tables should normally include time-oriented indexes.

Examples:

```text
events
audit_records
alert_occurrences
incident_timeline
agent_executions
plugin_executions
notification_delivery_attempts
workflow_history
```

Typical index:

```sql
CREATE INDEX idx_events_org_occurred
ON events (
    organization_id,
    occurred_at DESC
);
```

Global maintenance scans may additionally use:

```sql
CREATE INDEX idx_events_recorded_brin
ON events
USING BRIN (recorded_at);
```

---

## Event Table Strategy

The `events` table is expected to have high write volume.

Initial required indexes should remain limited.

Recommended starting indexes:

```sql
CREATE INDEX idx_events_org_recorded
ON events (
    organization_id,
    recorded_at DESC
);
```

```sql
CREATE INDEX idx_events_org_application_occurred
ON events (
    organization_id,
    application_id,
    occurred_at DESC
);
```

```sql
CREATE INDEX idx_events_org_environment_occurred
ON events (
    organization_id,
    environment_id,
    occurred_at DESC
);
```

```sql
CREATE INDEX idx_events_org_type_occurred
ON events (
    organization_id,
    event_type,
    occurred_at DESC
);
```

```sql
CREATE INDEX idx_events_org_correlation
ON events (
    organization_id,
    correlation_id
)
WHERE correlation_id IS NOT NULL;
```

Additional indexes require measured query need.

---

## Signal Table Strategy

Recommended indexes:

```sql
CREATE INDEX idx_signals_org_active_severity
ON signals (
    organization_id,
    severity,
    last_observed_at DESC
)
WHERE status = 'active';
```

```sql
CREATE INDEX idx_signals_org_application_status
ON signals (
    organization_id,
    application_id,
    status
);
```

```sql
CREATE INDEX idx_signals_org_fingerprint_status
ON signals (
    organization_id,
    fingerprint,
    status
)
WHERE fingerprint IS NOT NULL;
```

---

## Alert Table Strategy

Recommended indexes:

```sql
CREATE INDEX idx_alerts_org_active_updated
ON alerts (
    organization_id,
    updated_at DESC
)
WHERE status IN (
    'open',
    'acknowledged',
    'investigating',
    'suppressed'
);
```

```sql
CREATE INDEX idx_alerts_org_application_active
ON alerts (
    organization_id,
    application_id,
    severity,
    updated_at DESC
)
WHERE status IN (
    'open',
    'acknowledged',
    'investigating'
);
```

```sql
CREATE INDEX idx_alerts_org_assignee
ON alert_assignments (
    organization_id,
    assignee_type,
    assignee_id
)
WHERE released_at IS NULL;
```

---

## Incident Table Strategy

Recommended indexes:

```sql
CREATE INDEX idx_incidents_org_active_severity
ON incidents (
    organization_id,
    severity,
    updated_at DESC
)
WHERE status NOT IN (
    'closed',
    'cancelled',
    'merged'
);
```

```sql
CREATE INDEX idx_incidents_org_application_status
ON incidents (
    organization_id,
    application_id,
    status
);
```

```sql
CREATE INDEX idx_incident_roles_org_assignee
ON incident_roles (
    organization_id,
    assignee_type,
    assignee_id
)
WHERE released_at IS NULL;
```

```sql
CREATE INDEX idx_incident_timeline_org_incident_time
ON incident_timeline (
    organization_id,
    incident_id,
    occurred_at,
    recorded_at
);
```

---

## Knowledge Table Strategy

Recommended indexes:

```sql
CREATE INDEX idx_knowledge_entries_org_published_type
ON knowledge_entries (
    organization_id,
    knowledge_type,
    updated_at DESC
)
WHERE status = 'published';
```

```sql
CREATE INDEX idx_knowledge_applicability_org_scope
ON knowledge_applicability (
    organization_id,
    scope_type,
    scope_id
);
```

```sql
CREATE INDEX idx_knowledge_review_due
ON knowledge_review_schedules (
    next_review_at,
    organization_id
)
WHERE status IN (
    'current',
    'due',
    'overdue'
);
```

---

## Agent and Plugin Strategy

Recommended Agent indexes:

```sql
CREATE INDEX idx_agent_executions_org_status_requested
ON agent_executions (
    organization_id,
    status,
    requested_at DESC
);
```

```sql
CREATE INDEX idx_agent_executions_org_target
ON agent_executions (
    organization_id,
    target_resource_type,
    target_resource_id
);
```

Recommended Plugin indexes:

```sql
CREATE INDEX idx_plugin_installations_org_active
ON plugin_installations (
    organization_id,
    plugin_definition_id
)
WHERE status IN (
    'active',
    'degraded'
);
```

```sql
CREATE INDEX idx_plugin_sync_due
ON plugin_sync_states (
    next_sync_at,
    organization_id
)
WHERE status IN (
    'scheduled',
    'idle',
    'degraded'
);
```

---

## Notification Strategy

Recommended indexes:

```sql
CREATE INDEX idx_notifications_org_pending_schedule
ON notifications (
    scheduled_at,
    organization_id
)
WHERE status IN (
    'created',
    'scheduled'
);
```

```sql
CREATE INDEX idx_notification_attempts_retry
ON notification_delivery_attempts (
    next_retry_at,
    organization_id
)
WHERE status = 'failed'
  AND retryable = TRUE;
```

```sql
CREATE INDEX idx_notification_recipients_org_pending
ON notification_recipients (
    organization_id,
    notification_id,
    status
);
```

---

## Workflow Strategy

Recommended indexes:

```sql
CREATE INDEX idx_workflow_instances_org_active
ON workflow_instances (
    organization_id,
    updated_at DESC
)
WHERE status IN (
    'created',
    'running',
    'waiting',
    'blocked',
    'suspended'
);
```

```sql
CREATE INDEX idx_workflow_tasks_org_assignee_open
ON workflow_tasks (
    organization_id,
    assignee_type,
    assignee_id,
    due_at
)
WHERE status IN (
    'open',
    'in_progress',
    'blocked'
);
```

```sql
CREATE INDEX idx_workflow_timers_due
ON workflow_timers (
    scheduled_for,
    organization_id
)
WHERE status = 'scheduled';
```

```sql
CREATE INDEX idx_workflow_actions_org_target
ON workflow_actions (
    organization_id,
    target_resource_type,
    target_resource_id,
    status
);
```

---

## Audit Strategy

Audit tables are append-heavy and investigation-oriented.

Recommended indexes:

```sql
CREATE INDEX idx_audit_records_org_occurred
ON audit_records (
    organization_id,
    occurred_at DESC
);
```

```sql
CREATE INDEX idx_audit_records_org_actor_time
ON audit_records (
    organization_id,
    actor_type,
    actor_id,
    occurred_at DESC
);
```

```sql
CREATE INDEX idx_audit_records_org_target_time
ON audit_records (
    organization_id,
    target_type,
    target_id,
    occurred_at DESC
);
```

```sql
CREATE INDEX idx_audit_records_org_event_time
ON audit_records (
    organization_id,
    event_type,
    occurred_at DESC
);
```

```sql
CREATE INDEX idx_audit_records_occurred_brin
ON audit_records
USING BRIN (occurred_at);
```

---

## Retention Processing Strategy

Global retention workers commonly query eligibility by time.

Recommended indexes:

```sql
CREATE INDEX idx_retention_states_archive_due
ON resource_retention_states (
    archive_eligible_at,
    organization_id
)
WHERE status = 'archive_eligible';
```

```sql
CREATE INDEX idx_retention_states_delete_due
ON resource_retention_states (
    deletion_eligible_at,
    organization_id
)
WHERE status = 'deletion_eligible';
```

```sql
CREATE INDEX idx_retention_job_items_pending
ON retention_job_items (
    retention_job_id,
    id
)
WHERE status = 'pending';
```

Workers must restore Organization context before processing each Resource.

---

## Queue and Worker Queries

Database-backed work selection should use partial indexes.

Example:

```sql
CREATE INDEX idx_outbox_unpublished
ON outbox_messages (
    created_at,
    id
)
WHERE published_at IS NULL;
```

Example:

```sql
CREATE INDEX idx_projection_failures_retry
ON search_projection_failures (
    next_retry_at,
    id
)
WHERE resolved_at IS NULL;
```

Workers should use bounded batches and safe row locking.

Example:

```sql
SELECT id
FROM outbox_messages
WHERE published_at IS NULL
ORDER BY created_at, id
FOR UPDATE SKIP LOCKED
LIMIT 100;
```

---

## Index Naming Convention

Index names should be predictable.

Recommended format:

```text
idx_{table}_{column_or_purpose}
```

Unique indexes:

```text
uq_{table}_{column_or_purpose}
```

Examples:

```text
idx_alerts_org_active_updated
uq_projects_org_slug
idx_workflow_timers_due
idx_events_recorded_brin
```

Names should describe query purpose rather than list every column when the full name would become excessive.

---

## Index Size Management

Large indexes increase:

* Storage consumption.
* insert cost.
* update cost.
* replication traffic.
* backup size.
* vacuum work.
* migration duration.

High-volume tables should avoid:

* Duplicated indexes.
* indexes with identical leading columns.
* broad GIN indexes without measured need.
* many low-selectivity single-column indexes.
* indexes on frequently changed fields without query justification.
* included large text or JSON fields.

---

## Duplicate Index Detection

The team should review indexes that:

* Have identical columns.
* are covered by a longer index.
* duplicate unique constraints.
* are never scanned.
* differ only by unnecessary sort direction.
* exist because of framework defaults.

An index should not be removed until dependent queries and constraints are verified.

---

## Selectivity

Indexes are most useful when they reduce the number of scanned rows significantly.

Low-selectivity fields include:

* Boolean flags.
* small status sets.
* common severity values.

These fields are often more effective when combined with:

```text
organization_id
```

or when used in a partial index.

Poor standalone index:

```sql
CREATE INDEX idx_alerts_status
ON alerts (status);
```

Better:

```sql
CREATE INDEX idx_alerts_org_active_updated
ON alerts (
    organization_id,
    updated_at DESC
)
WHERE status IN (
    'open',
    'acknowledged',
    'investigating'
);
```

---

## Write Amplification

Every index must be updated during relevant inserts, updates, and deletes.

Write-heavy tables require stricter index review.

Before adding an index, evaluate:

* Query frequency.
* query latency.
* rows scanned.
* table write volume.
* index size.
* update frequency of indexed columns.
* effect on ingestion throughput.

An index that helps a rare query but slows every Event insert may not be justified.

---

## Updating Indexed Columns

Frequently updated fields increase index maintenance.

Examples include:

* `status`
* `updated_at`
* `last_observed_at`
* `occurrence_count`
* `version`

Partial indexes and carefully selected composites should be used to minimize excessive updates.

Indexing counters such as `occurrence_count` should be avoided unless range queries genuinely require it.

---

## Query Analysis

Index decisions must use:

```sql
EXPLAIN
```

and when safe:

```sql
EXPLAIN ANALYZE
```

Review:

* Scan type.
* estimated rows.
* actual rows.
* filter rows removed.
* sort behavior.
* buffer usage.
* execution time.
* planning time.
* parallel execution.
* partition pruning.

Production-like data volume should be used for meaningful evaluation.

---

## Index Monitoring

PostgreSQL statistics should be reviewed through sources such as:

```text
pg_stat_user_indexes
pg_stat_all_indexes
pg_statio_user_indexes
pg_stat_statements
```

Monitor:

* Index scans.
* index size.
* cache hit behavior.
* unused indexes.
* slow queries.
* sequential scans.
* index bloat.
* duplicate indexes.
* high write overhead.

Statistics must be interpreted carefully because restarts may reset counters.

---

## Unused Indexes

An index may be considered for removal when:

* It has no meaningful scans over a representative period.
* no constraint depends on it.
* no periodic or emergency query depends on it.
* it is covered by another index.
* removing it improves write performance or storage.

Removal should use a controlled migration.

---

## Index Bloat and Maintenance

Indexes may require maintenance because of:

* Frequent updates.
* deletes.
* long-running transactions.
* poor vacuum behavior.
* high churn.

Maintenance options include:

* `VACUUM`.
* `ANALYZE`.
* `REINDEX`.
* concurrent index rebuilding.
* table partitioning.
* fill-factor adjustment where justified.

Routine maintenance must minimize operational disruption.

---

## Index Creation in Production

Large indexes should normally be created using:

```sql
CREATE INDEX CONCURRENTLY
```

where supported and appropriate.

Production index creation must consider:

* Table size.
* database load.
* replication lag.
* lock behavior.
* available disk space.
* migration timeout.
* failure cleanup.

Concurrent index creation cannot run inside a normal transaction block.

Migration tooling must account for this.

---

## Unique Constraint Deployment

Adding a unique constraint to existing data requires:

1. Detect duplicates.
2. define conflict-resolution behavior.
3. clean or reconcile invalid records.
4. create a unique index safely.
5. attach the constraint where appropriate.
6. deploy application behavior relying on the constraint.

The database must not be expected to resolve existing duplicate business data automatically.

---

## Index Removal in Production

Safe removal should include:

1. Verify the index is not required by a constraint.
2. inspect usage over a representative period.
3. inspect important periodic queries.
4. deploy query changes first if required.
5. remove using a safe migration.
6. monitor query performance afterward.

Use:

```sql
DROP INDEX CONCURRENTLY
```

where appropriate.

---

## Partitioned Tables

Indexes on partitioned tables must support:

* Tenant queries.
* time-range queries.
* partition pruning.
* retention.
* local partition maintenance.

Each partition may have local indexes.

Index design must avoid requiring scans across every partition for common queries.

The application must not depend on partition names.

---

## Pagination

Large collections should use keyset pagination where possible.

Example:

```sql
SELECT *
FROM events
WHERE organization_id = :organization_id
  AND occurred_at < :cursor_time
ORDER BY occurred_at DESC, id DESC
LIMIT 100;
```

Supporting index:

```sql
CREATE INDEX idx_events_org_occurred_id
ON events (
    organization_id,
    occurred_at DESC,
    id DESC
);
```

Large offset pagination should be avoided for high-volume tables.

---

## Multi-Column Cursor Ordering

When pagination ordering can contain duplicate timestamps, include a stable tie-breaker.

Recommended order:

```text
occurred_at DESC, id DESC
```

The supporting index should use the same order.

This prevents missing or duplicated records between pages.

---

## Case-Insensitive Search

Case-insensitive business identifiers should use:

* A normalized stored value.
* PostgreSQL `citext` where explicitly approved.
* an expression index on `lower(value)`.

The same normalization must be used consistently by:

* Validation.
* uniqueness constraints.
* lookup queries.
* API behavior.

---

## Index Review Checklist

Before adding an index, confirm:

* Which query requires it?
* How often does the query run?
* How many rows does it scan today?
* Which columns are equality filters?
* Which columns are range filters?
* What ordering is required?
* Is Organization scope included?
* Can a partial index be used?
* Is the query already covered?
* What is the write cost?
* What is the expected index size?
* Can it be deployed safely?
* How will success be measured?

---

## Prohibited Practices

The following are prohibited:

* Adding indexes without a documented query.
* omitting Organization scope from tenant indexes without justification.
* indexing every foreign key automatically.
* creating broad JSONB GIN indexes by default.
* using many single-column indexes when one composite index matches the query.
* duplicating unique-constraint indexes.
* adding indexes solely because a framework generated them.
* using offset pagination for large operational tables.
* creating large blocking indexes during normal production deployment.
* removing indexes without checking constraints and periodic queries.
* indexing secret or encrypted plaintext values.
* relying on indexes instead of authorization.
* treating index presence as proof of query performance.

---

## Business Rules

* Indexes support queries and constraints; they do not define domain ownership.
* Tenant-owned query indexes normally begin with `organization_id`.
* Business uniqueness is enforced by database constraints.
* Partial indexes should support common active-state queries.
* High-write tables use the minimum effective index set.
* Frequently queried JSONB fields should become dedicated columns.
* Search text uses dedicated Search Projections.
* Time-based append-heavy tables may use BRIN indexes.
* Pagination indexes include a stable tie-breaker.
* Index creation and removal require version-controlled migrations.
* Production index changes must minimize locking.
* Index effectiveness must be measured after deployment.
* Unused and duplicate indexes must be reviewed periodically.

---

## Success Criteria

The indexing strategy is successful when:

* Tenant-scoped queries remain efficient.
* Event ingestion is not overloaded by unnecessary indexes.
* active Alerts and Incidents load quickly.
* responder Tasks and scheduled work can be selected efficiently.
* Search Projections remain discoverable.
* retention and retry workers find eligible records efficiently.
* uniqueness rules remain database-enforced.
* pagination remains stable at high volume.
* index storage and write overhead remain controlled.
* production index changes can be deployed safely.
* query performance is measurable and explainable.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/03-organization-project-schema.md`
* `docs/database/04-application-environment-schema.md`
* `docs/database/05-event-signal-schema.md`
* `docs/database/06-alert-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/08-knowledge-schema.md`
* `docs/database/09-agent-schema.md`
* `docs/database/10-plugin-schema.md`
* `docs/database/11-notification-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/architecture/09-scaling-strategy.md`
* `docs/deployment/06-workers-queues.md`
* `docs/deployment/11-scaling.md`
