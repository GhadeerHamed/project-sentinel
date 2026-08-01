# Retention and Archival

## Purpose

This document defines how Project Sentinel retains, archives, restores, and deletes data.

The retention model must support:

* Operational usefulness.
* customer configuration.
* legal and contractual obligations.
* security investigations.
* cost control.
* privacy requirements.
* Legal Holds.
* evidence preservation.
* tenant isolation.

Retention applies to authoritative, historical, derived, and externally stored data.

---

## Design Principles

* Data is retained for an explicit reason.
* Every data category has a defined retention Policy.
* Organization configuration may increase or reduce retention within platform limits.
* Legal Hold overrides normal deletion.
* Archival is different from deletion.
* Archived data preserves Organization ownership.
* Deletion must include derived and external copies.
* Evidence required by retained Resources must not be deleted prematurely.
* Retention processing is idempotent and resumable.
* Deletion and export operations are auditable.
* Backup retention is governed separately from live database retention.
* Tenant data must never be mixed during archival or deletion.

---

## Data Lifecycle

```text
Active

↓

Inactive

↓

Archived

↓

Eligible for Deletion

↓

Deleted
```

Not every record passes through every state.

For example:

* Temporary processing data may move directly from Active to Deleted.
* Incident evidence may remain Archived for an extended period.
* Audit Records may remain retained because of regulation.
* Legal Hold may pause deletion at any stage.

---

## Retention Policy Hierarchy

Retention decisions follow this precedence:

```text
Legal and Regulatory Requirements

↓

Legal Hold

↓

Platform Minimum and Maximum Limits

↓

Contractual Requirements

↓

Organization Retention Policy

↓

Resource-Specific Policy
```

A lower-level Policy must not violate a mandatory higher-level requirement.

---

## Retention Policies

### `retention_policies`

Stores one Organization-specific retention Policy.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `description`            | TEXT        | Optional    |
| `status`                 | VARCHAR     | Required    |
| `default_action`         | VARCHAR     | Required    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Supported statuses:

* `draft`
* `active`
* `suspended`
* `archived`

Supported default actions:

* `retain`
* `archive`
* `delete`

Constraints:

```text
UNIQUE (organization_id, name)
```

---

## Retention Rules

### `retention_policy_rules`

Stores Resource-specific retention rules.

| Column                | Type        | Rules       |
| --------------------- | ----------- | ----------- |
| `id`                  | UUID        | Primary key |
| `organization_id`     | UUID        | Required    |
| `retention_policy_id` | UUID        | Required    |
| `resource_type`       | VARCHAR     | Required    |
| `resource_status`     | VARCHAR     | Optional    |
| `retention_days`      | INTEGER     | Required    |
| `archive_after_days`  | INTEGER     | Optional    |
| `delete_after_days`   | INTEGER     | Optional    |
| `action`              | VARCHAR     | Required    |
| `conditions`          | JSONB       | Optional    |
| `created_at`          | TIMESTAMPTZ | Required    |

Supported actions:

* `retain`
* `archive`
* `delete`
* `anonymize`

Constraints:

```text
UNIQUE (
    organization_id,
    retention_policy_id,
    resource_type,
    resource_status
)
```

---

## Retention Assignment

A Resource may receive its retention Policy from:

* Organization default.
* Project configuration.
* Application configuration.
* Environment configuration.
* Resource Type.
* sensitivity classification.
* legal or contractual requirement.

The effective Policy must be resolvable at processing time.

---

## Retention Categories

### Operational Data

Includes:

* Events.
* Signals.
* Alerts.
* Incidents.
* Workflow executions.
* Agent executions.
* Plugin executions.
* Notification delivery records.

Retention depends on:

* operational value.
* evidence relationships.
* customer contract.
* storage cost.
* security requirements.

### Identity and Access Data

Includes:

* Membership history.
* Role assignments.
* Session records.
* credential metadata.
* privileged-access records.

Security and attribution requirements may require longer retention than normal operational data.

### Knowledge Data

Includes:

* Knowledge Entries.
* Knowledge Versions.
* reviews.
* approvals.
* feedback.
* usage history.

Published and historically referenced Versions should normally be preserved.

### Audit Data

Includes:

* Audit Records.
* privileged-access history.
* security-sensitive changes.
* export activity.

Audit retention may be governed by stricter requirements.

### Derived Data

Includes:

* Search Projections.
* caches.
* analytics aggregates.
* semantic embeddings.
* temporary reports.

Derived data should normally follow the authoritative source lifecycle and remain rebuildable.

---

## Suggested Initial Retention Defaults

Initial defaults should remain configurable.

| Data Category                  | Suggested Default |
| ------------------------------ | ----------------: |
| Rejected ingestion records     |           30 days |
| Event processing attempts      |           30 days |
| Canonical Events               |           90 days |
| Signals                        |          180 days |
| Closed Alerts                  |          365 days |
| Closed Incidents               |           3 years |
| Incident evidence              |  Same as Incident |
| Agent context snapshots        |           30 days |
| Agent execution metadata       |          365 days |
| Plugin execution metadata      |          365 days |
| Notification content           |           90 days |
| Notification delivery metadata |          365 days |
| Workflow execution history     |          365 days |
| Audit Records                  |           3 years |
| Search Projections             |    Same as source |
| Temporary exports              |            7 days |

These are product defaults, not universal legal requirements.

---

## Retention Anchors

Retention periods must begin from a clear lifecycle event.

Possible anchors include:

* `created_at`
* `recorded_at`
* `closed_at`
* `resolved_at`
* `archived_at`
* `revoked_at`
* `expired_at`
* `last_used_at`

Examples:

```text
Event deletion date
= recorded_at + event_retention_days
```

```text
Incident deletion date
= closed_at + incident_retention_days
```

The selected anchor must match the Resource’s business meaning.

---

## Retention State

Resources may expose retention metadata through a dedicated table.

### `resource_retention_states`

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `resource_type`        | VARCHAR     | Required    |
| `resource_id`          | UUID        | Required    |
| `retention_policy_id`  | UUID        | Optional    |
| `retention_anchor_at`  | TIMESTAMPTZ | Required    |
| `archive_eligible_at`  | TIMESTAMPTZ | Optional    |
| `deletion_eligible_at` | TIMESTAMPTZ | Optional    |
| `status`               | VARCHAR     | Required    |
| `legal_hold_count`     | INTEGER     | Required    |
| `updated_at`           | TIMESTAMPTZ | Required    |
| `version`              | BIGINT      | Required    |

Supported statuses:

* `active`
* `retained`
* `archive_eligible`
* `archived`
* `deletion_eligible`
* `deletion_pending`
* `deleted`
* `held`
* `failed`

Constraints:

```text
UNIQUE (
    organization_id,
    resource_type,
    resource_id
)
```

This table is optional for Resources where retention can be calculated directly.

---

## Archival

Archival moves inactive data away from normal operational queries while preserving historical access.

Archived data may remain in:

* Active PostgreSQL tables with an archived state.
* archive partitions.
* separate PostgreSQL tables.
* encrypted object storage.
* a dedicated archive database.

The initial implementation should prefer:

```text
Lifecycle state plus PostgreSQL partitioning when needed
```

External archival storage should be introduced only when volume or cost justifies it.

---

## Archive Records

### `archive_records`

Tracks archived data packages.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `resource_type`            | VARCHAR     | Required    |
| `scope_definition`         | JSONB       | Required    |
| `storage_provider`         | VARCHAR     | Required    |
| `storage_reference`        | VARCHAR     | Required    |
| `format`                   | VARCHAR     | Required    |
| `record_count`             | BIGINT      | Required    |
| `checksum`                 | VARCHAR     | Required    |
| `encryption_key_reference` | VARCHAR     | Required    |
| `status`                   | VARCHAR     | Required    |
| `archived_at`              | TIMESTAMPTZ | Required    |
| `retention_until`          | TIMESTAMPTZ | Optional    |
| `deleted_at`               | TIMESTAMPTZ | Optional    |

Supported statuses:

* `creating`
* `validated`
* `available`
* `restoring`
* `failed`
* `expired`
* `deleted`

Archive packages must remain Organization-scoped.

---

## Archive Requirements

An archive must preserve:

* Organization identifier.
* Resource identifiers.
* relationships.
* source Versions.
* lifecycle timestamps.
* evidence references.
* sensitivity classification.
* retention deadline.
* integrity checksum.
* encryption metadata.

Archived data must not lose its tenant boundary.

---

## Archive Validation

Before active data is removed, Sentinel must verify:

1. Archive creation completed.
2. record count matches expected count.
3. checksum is valid.
4. encryption is enabled.
5. storage is accessible.
6. retention metadata is present.
7. a sample restore succeeds where required.

Failed validation must prevent source deletion.

---

## Archive Restoration

Restoration may be required for:

* Security investigation.
* customer request.
* compliance review.
* Incident review.
* Legal Hold.
* recovery.

Restoration must:

* Preserve Organization scope.
* remain auditable.
* avoid overwriting newer authoritative data.
* restore into a controlled read-only area where possible.
* apply current authorization rules.
* define an expiration for temporary restored data.

---

## Legal Holds

A Legal Hold prevents deletion of selected data.

### `legal_holds`

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `name`                    | VARCHAR     | Required    |
| `reason`                  | TEXT        | Required    |
| `scope_type`              | VARCHAR     | Required    |
| `scope_definition`        | JSONB       | Required    |
| `status`                  | VARCHAR     | Required    |
| `created_by_identity_id`  | UUID        | Required    |
| `approved_by_identity_id` | UUID        | Optional    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `released_at`             | TIMESTAMPTZ | Optional    |
| `released_by_identity_id` | UUID        | Optional    |

Supported statuses:

* `active`
* `released`
* `cancelled`

Possible scopes include:

* Organization.
* Project.
* Application.
* Environment.
* Incident.
* Resource Type.
* Resource identifier.
* actor.
* date range.
* investigation.

---

## Legal Hold Assignments

### `legal_hold_resources`

Stores explicit Resource assignments when required.

| Column            | Type        | Rules    |
| ----------------- | ----------- | -------- |
| `organization_id` | UUID        | Required |
| `legal_hold_id`   | UUID        | Required |
| `resource_type`   | VARCHAR     | Required |
| `resource_id`     | UUID        | Required |
| `assigned_at`     | TIMESTAMPTZ | Required |

Primary key:

```text
PRIMARY KEY (
    organization_id,
    legal_hold_id,
    resource_type,
    resource_id
)
```

A Resource may belong to multiple active Legal Holds.

---

## Legal Hold Rules

* Active Legal Holds override normal deletion.
* Archival may continue only when the hold allows it.
* Held data must remain discoverable.
* Hold creation and release are privileged operations.
* Hold release does not delete data immediately.
* The next retention cycle reevaluates released Resources.
* Legal Hold actions must be audited.

---

## Deletion

Deletion must remove or invalidate all governed copies.

Possible targets include:

* Primary database records.
* archive records.
* Search Projections.
* caches.
* object storage.
* semantic embeddings.
* temporary exports.
* analytics aggregates.
* provider-stored data where contractually controlled.

Deletion must not leave searchable or downloadable copies behind.

---

## Deletion Jobs

### `retention_jobs`

Tracks one retention-processing job.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `job_type`          | VARCHAR     | Required    |
| `resource_type`     | VARCHAR     | Required    |
| `scope_definition`  | JSONB       | Required    |
| `status`            | VARCHAR     | Required    |
| `requested_by_type` | VARCHAR     | Required    |
| `requested_by_id`   | UUID        | Optional    |
| `started_at`        | TIMESTAMPTZ | Optional    |
| `completed_at`      | TIMESTAMPTZ | Optional    |
| `processed_count`   | BIGINT      | Required    |
| `failed_count`      | BIGINT      | Required    |
| `failure_code`      | VARCHAR     | Optional    |
| `created_at`        | TIMESTAMPTZ | Required    |

Supported job types:

* `evaluate`
* `archive`
* `delete`
* `anonymize`
* `restore`
* `verify`

Supported statuses:

* `requested`
* `running`
* `completed`
* `completed_with_failures`
* `failed`
* `cancelled`

---

## Retention Job Items

### `retention_job_items`

Tracks individual Resources processed by a job.

| Column             | Type        | Rules       |
| ------------------ | ----------- | ----------- |
| `id`               | UUID        | Primary key |
| `organization_id`  | UUID        | Required    |
| `retention_job_id` | UUID        | Required    |
| `resource_type`    | VARCHAR     | Required    |
| `resource_id`      | UUID        | Required    |
| `action`           | VARCHAR     | Required    |
| `status`           | VARCHAR     | Required    |
| `attempt_count`    | INTEGER     | Required    |
| `processed_at`     | TIMESTAMPTZ | Optional    |
| `failure_code`     | VARCHAR     | Optional    |
| `failure_message`  | TEXT        | Optional    |

Supported statuses:

* `pending`
* `processing`
* `completed`
* `skipped`
* `held`
* `failed`

Constraints:

```text
UNIQUE (
    organization_id,
    retention_job_id,
    resource_type,
    resource_id
)
```

---

## Deletion Ordering

Deletion should respect dependencies.

Example order:

```text
Search Projections and caches

↓

Temporary exports and embeddings

↓

Derived records

↓

Child operational records

↓

Authoritative parent record

↓

Retention metadata
```

When evidence relationships require preservation, deletion must stop or use archival instead.

---

## Evidence Preservation

A Resource must not be deleted while it remains required evidence for:

* An active Incident.
* a retained Incident.
* an active security investigation.
* a retained Audit Record.
* a Legal Hold.
* an approved Post-Incident Review.
* an unresolved Workflow.
* a contractual obligation.

Retention evaluation must inspect required evidence references.

---

## Referential Integrity

Physical deletion must not create invalid retained references.

Possible strategies include:

* Prevent deletion.
* extend retention of the referenced Resource.
* archive both Resources.
* preserve a governed evidence snapshot.
* replace the reference with a deletion marker where legally allowed.

Silently breaking evidence chains is prohibited.

---

## Anonymization

Anonymization may be used when:

* Operational history must remain.
* personal identifiers are no longer required.
* privacy obligations require de-identification.

Anonymization should:

* Remove direct identifiers.
* remove unnecessary indirect identifiers.
* preserve operational meaning where possible.
* be irreversible within normal platform operation.
* remain auditable.

Anonymization is not equivalent to deletion when re-identification remains reasonably possible.

---

## Organization Deletion

Organization deletion is a coordinated retention process.

It should include:

1. Suspend normal tenant activity.
2. evaluate Legal Holds.
3. complete required exports.
4. revoke Sessions and credentials.
5. stop Agents, Plugins, Workflows, and ingestion.
6. delete or archive tenant-owned database records.
7. remove Search Projections.
8. remove caches and embeddings.
9. delete object-storage content.
10. process provider-side data where supported.
11. preserve legally required Audit records.
12. verify completion.
13. record final deletion evidence.

Uncontrolled database cascading must not perform Organization deletion.

---

## Backup Retention

Backups may retain deleted records until backup expiration.

The platform must define:

* Backup retention period.
* backup encryption.
* access restrictions.
* restoration controls.
* handling of deletion requests after restore.
* maximum time until deleted data leaves all normal backups.

Restoring an older backup must reapply completed retention and deletion records before restored data becomes operational.

---

## Deletion Ledger

### `data_deletion_records`

Stores evidence that governed deletion occurred.

| Column                | Type        | Rules                                |
| --------------------- | ----------- | ------------------------------------ |
| `id`                  | UUID        | Primary key                          |
| `organization_id`     | UUID        | Optional after Organization deletion |
| `resource_type`       | VARCHAR     | Required                             |
| `resource_id_hash`    | VARCHAR     | Required                             |
| `deletion_reason`     | VARCHAR     | Required                             |
| `retention_policy_id` | UUID        | Optional                             |
| `retention_job_id`    | UUID        | Optional                             |
| `deleted_by_type`     | VARCHAR     | Required                             |
| `deleted_by_id`       | UUID        | Optional                             |
| `deleted_at`          | TIMESTAMPTZ | Required                             |
| `verification_status` | VARCHAR     | Required                             |

The ledger should avoid retaining deleted sensitive content.

It preserves only the minimum evidence required to prove deletion.

---

## Retention Processing Flow

```text
Select Eligible Organization

↓

Load Effective Retention Policy

↓

Find Eligible Resources

↓

Check Legal Holds

↓

Check Evidence Dependencies

↓

Archive When Required

↓

Validate Archive

↓

Delete Derived Copies

↓

Delete or Anonymize Authoritative Data

↓

Record Deletion Evidence

↓

Publish Completion Event
```

Each Organization should be processed independently.

---

## Batch Processing

Retention jobs must:

* Process bounded batches.
* be resumable.
* be idempotent.
* use deterministic ordering.
* limit database load.
* preserve Organization context.
* support safe retries.
* expose progress and failures.

Large retention jobs must not run in one database transaction.

---

## Failure Handling

When retention processing fails:

* The affected Resource remains protected from partial deletion.
* the failure is recorded.
* retries are scheduled where safe.
* operators can inspect the failed item.
* completed items are not repeated unnecessarily.
* critical failures create operational Alerts.

Archive validation failure must prevent source deletion.

---

## Optimistic Concurrency

Mutable Policies, retention states, and jobs should use optimistic concurrency.

Before deleting a mutable Resource, Sentinel should confirm:

* The expected Resource Version.
* the expected lifecycle state.
* no new Legal Hold exists.
* no new evidence dependency exists.
* the retention deadline remains valid.

A Resource changed after eligibility evaluation must be reevaluated.

---

## Tenant Isolation

Every tenant retention operation must include `organization_id`.

The process must prevent:

* One Organization’s Policy affecting another Organization.
* cross-Organization archive packages.
* shared deletion batches without tenant boundaries.
* restoring data into the wrong Organization.
* deleting another Organization’s object-storage path.
* cross-tenant Legal Hold assignments.

Organization scope must be included in queries, jobs, archive paths, and deletion records.

---

## Required Indexes

Recommended indexes include:

```text
retention_policies (
    organization_id,
    status
)

retention_policy_rules (
    organization_id,
    retention_policy_id,
    resource_type
)

resource_retention_states (
    organization_id,
    status,
    archive_eligible_at
)

resource_retention_states (
    organization_id,
    status,
    deletion_eligible_at
)

legal_holds (
    organization_id,
    status
)

legal_hold_resources (
    organization_id,
    resource_type,
    resource_id
)

retention_jobs (
    organization_id,
    status,
    created_at
)

retention_job_items (
    organization_id,
    retention_job_id,
    status
)

archive_records (
    organization_id,
    resource_type,
    archived_at
)

data_deletion_records (
    resource_type,
    resource_id_hash,
    deleted_at
)
```

---

## Security Requirements

* Archive storage must be encrypted.
* archive access must be authorized.
* encryption keys must use secret references.
* deletion operations require privileged authorization.
* Legal Hold creation and release require strong authorization.
* restored data must follow current access controls.
* retention failures must not expose sensitive data.
* archive and deletion paths must remain tenant-scoped.
* deletion logs must not preserve deleted secrets.

---

## Audit Requirements

Auditable operations include:

* Retention Policy creation.
* Retention Policy activation.
* retention period reduction.
* archive creation.
* archive restoration.
* Legal Hold creation.
* Legal Hold release.
* manual deletion request.
* Organization deletion.
* anonymization.
* deletion failure override.
* backup restoration involving deleted data.
* archive export or download.

Normal automatic batch processing may use structured retention records in addition to required Audit Records.

---

## Monitoring

Sentinel should monitor:

* Resources past retention deadlines.
* archive validation failures.
* failed deletion items.
* Legal Hold conflicts.
* unexpected retention growth.
* incomplete Organization deletion.
* orphaned Search Projections.
* orphaned object-storage files.
* expired exports that remain accessible.
* backup retention violations.
* restore operations that require deletion replay.

Critical retention failures should create Alerts.

---

## Prohibited Practices

The following are prohibited:

* Deleting data without an applicable Policy.
* deleting data under active Legal Hold.
* deleting evidence required by retained Resources.
* using uncontrolled cascades for Organization deletion.
* removing active data before archive validation.
* storing multiple Organizations in one unscoped archive package.
* keeping Search or cache copies after authoritative deletion.
* silently ignoring failed retention jobs.
* changing active retention rules without Audit.
* using backup retention as permanent storage.
* restoring deleted data without reapplying deletion records.
* storing deleted sensitive values in the deletion ledger.

---

## Business Rules

* Every major data category has a defined retention rule.
* Legal Hold overrides normal deletion.
* Archival does not remove Organization ownership.
* Archived data remains encrypted and access-controlled.
* Archive validation occurs before active data removal.
* Derived data follows the authoritative Resource lifecycle.
* Evidence required by retained Resources must not be deleted.
* Retention processing is idempotent and resumable.
* Large retention jobs use bounded batches.
* Deletion removes governed derived and external copies.
* Organization deletion is a coordinated process.
* Restored backups must reapply completed deletions.
* Retention and deletion operations are auditable.
* Cross-Organization retention processing is prohibited.
* Missing or conflicting Organization ownership results in rejection.

---

## Success Criteria

Retention and archival are successful when:

* Data remains available for its required lifetime.
* unnecessary data is removed predictably.
* Legal Holds prevent deletion.
* retained evidence chains remain intact.
* archives remain encrypted and restorable.
* deletion includes derived and external copies.
* Organization deletion can be verified.
* backup restoration does not resurrect deleted data permanently.
* failures remain observable and recoverable.
* tenant isolation is preserved.
* storage costs can be controlled without losing required operational history.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
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
* `docs/database/16-indexing-strategy.md`
* `docs/functional/14-administration.md`
* `docs/architecture/08-security-architecture.md`
* `docs/architecture/09-scaling-strategy.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
