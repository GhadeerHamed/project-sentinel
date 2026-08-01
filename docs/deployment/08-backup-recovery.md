# Backup and Recovery

## Purpose

This document defines how Project Sentinel protects recoverable production data and restores service after data loss, corruption, or operational failure.

Backup is not complete until restoration has been tested successfully.

---

## Scope

Backup and recovery must cover:

* PostgreSQL.
* object storage.
* secret-management configuration where supported.
* deployment configuration.
* infrastructure definitions.
* Search Projection rebuild procedures.
* critical operational metadata.

Redis cache and Queue state are not authoritative backups.

---

## Recovery Objectives

Sentinel should define:

```text
Recovery Point Objective (RPO)
Recovery Time Objective (RTO)
```

Initial targets should be documented per production tier.

Example starting targets:

| Capability                 | Initial target                               |
| -------------------------- | -------------------------------------------- |
| PostgreSQL RPO             | 15 minutes or less                           |
| PostgreSQL RTO             | 4 hours or less                              |
| Object storage RPO         | Provider durability or scheduled replication |
| Application deployment RTO | 1 hour or less                               |

Final commitments must reflect actual infrastructure capability.

---

## PostgreSQL Backups

Production PostgreSQL should support:

* Automated full backups.
* continuous transaction-log or WAL archiving.
* point-in-time recovery where available.
* encrypted backup storage.
* retention policies.
* backup integrity verification.
* restoration to an isolated Environment.

Managed PostgreSQL is preferred when it provides reliable backup automation and recovery tooling.

---

## Backup Frequency

Recommended initial policy:

```text
Full backup: daily
Point-in-time recovery data: continuous
Retention: minimum 14–30 days
Longer archival snapshots: according to business and compliance requirements
```

Retention must align with:

* Customer commitments.
* data classification.
* Legal Hold.
* infrastructure cost.
* regulatory requirements.

---

## Backup Isolation

Backups must not exist only on the primary application host.

They should be stored in:

* A separate storage service.
* a separate account or security boundary where practical.
* a different failure domain from the primary database.

An attacker or operator who can delete production data should not automatically be able to delete every backup.

---

## Encryption

Backups must be encrypted:

* In transit.
* at rest.
* during replication.
* during restoration transfer.

Encryption keys must remain separate from backup data.

Key recovery procedures must be documented and tested.

---

## Access Control

Backup access must be restricted to authorized operational identities.

Permissions should distinguish:

* Creating backups.
* listing backup metadata.
* restoring backups.
* deleting backups.
* changing retention settings.

Backup deletion and restoration are privileged operations.

---

## Object Storage

Object storage recovery should consider:

* Provider durability.
* object Versioning.
* lifecycle policies.
* accidental deletion.
* malicious deletion.
* archive restoration.
* cross-region or cross-account replication where justified.

Deletion policies must remain compatible with tenant deletion and Legal Hold requirements.

---

## Secret Management

Secrets should not be copied into ordinary backup files.

Recovery planning must cover:

* Secret-manager availability.
* key and credential recovery.
* emergency credential rotation.
* restoration of secret references.
* loss of encryption-key material.

A database backup without required encryption keys may be unusable.

---

## Application and Infrastructure Recovery

Application source is recovered from version control.

Runtime artifacts are recovered from:

* Container registry.
* release metadata.
* infrastructure-as-code repository.
* deployment configuration.
* secret-management references.

Production recovery must not depend on a developer workstation.

---

## Search Recovery

Search Projections are derived data.

They should normally be rebuilt from authoritative PostgreSQL state rather than restored as the primary recovery source.

Recovery procedures must define:

* Rebuild command.
* tenant scope.
* checkpointing.
* validation.
* traffic cutover.
* stale-index replacement.

---

## Redis Recovery

Redis is used for transient concerns such as:

* Queues.
* cache.
* rate limiting.
* temporary locks.

Redis loss may cause:

* Cache misses.
* delayed jobs.
* lost transient Queue reservations.
* temporary rate-limit reset.

Authoritative domain state must remain recoverable from PostgreSQL and Outbox Records.

Redis persistence may reduce disruption but is not the primary business recovery mechanism.

---

## Queue Recovery

After Queue loss or interruption:

1. Restore Queue infrastructure.
2. restart Workers.
3. inspect pending Outbox Records.
4. republish unpublished messages.
5. reconcile failed or interrupted operations.
6. verify idempotency.
7. monitor backlog recovery.

The Outbox pattern is the main recovery mechanism for committed but unpublished domain events.

---

## Restore Types

Sentinel should support:

### Full Environment Restore

Restores the entire database to a new isolated Environment.

### Point-in-Time Restore

Restores PostgreSQL to a selected timestamp before corruption or accidental deletion.

### Resource Recovery

Recovers selected data through a governed operational process when technically and legally valid.

### File Recovery

Restores deleted or corrupted object-storage data from Versioning, replication, or backups.

Direct partial database restoration must be used cautiously because relationships and Audit history may be affected.

---

## Restore Procedure

A standard restore should follow:

```text
Declare recovery operation
↓
Identify restore point
↓
Create isolated target infrastructure
↓
Restore data
↓
Validate integrity
↓
Apply required migrations
↓
Rebuild derived systems
↓
Verify tenant isolation and security
↓
Approve cutover
↓
Monitor restored service
```

Restoration must not overwrite the only remaining good copy.

---

## Validation

Recovery validation should include:

* Database integrity.
* migration status.
* Organization ownership.
* authentication and authorization.
* critical Resource counts.
* Outbox consistency.
* object references.
* Audit availability.
* Queue processing.
* Search Projection rebuild.
* encryption and secret access.

Smoke tests must use safe dedicated test Resources.

---

## Recovery Testing

Backups must be restored regularly.

Recommended minimum:

```text
Automated backup verification: daily
Isolated database restore test: monthly
Full recovery exercise: quarterly or after major topology changes
```

Tests should record:

* Backup selected.
* restoration duration.
* achieved RPO.
* achieved RTO.
* validation results.
* failures.
* corrective Actions.

---

## Backup Monitoring

Monitor:

* Last successful backup.
* backup duration.
* backup size.
* retention status.
* WAL or transaction-log archival delay.
* failed encryption.
* failed replication.
* failed restore tests.
* unexpected backup deletion.
* storage-capacity risk.

Missed or failed backups must create operational Alerts.

---

## Data Retention and Deletion

Backup retention must respect governed deletion requirements.

Deletion from active systems may remain temporarily present in immutable backups until normal backup expiration.

Recovery procedures must prevent previously deleted data from becoming active again without reapplying deletion state.

Legal Hold may require extended preservation.

---

## Tenant Isolation

Backups may contain data for multiple Organizations because the initial architecture uses a shared database.

Therefore:

* Backup access is platform-privileged.
* customer self-service access to raw backups is prohibited.
* restore operations require strict authorization.
* extracted tenant data must be isolated and validated.
* backup evidence must not be exposed across Organizations.

---

## Recovery During Security Incidents

When compromise is suspected:

* Preserve affected backups.
* choose a restore point before the compromise where possible.
* rotate credentials and keys.
* validate restored code and configuration.
* investigate persistence mechanisms.
* prevent compromised identities from accessing recovery infrastructure.

Restoring data alone does not remove an attacker.

---

## Failure Handling

When restoration fails:

1. Preserve Logs and evidence.
2. do not modify the original backup.
3. attempt restoration using another verified restore point.
4. escalate to the Incident Commander.
5. assess revised RPO and RTO.
6. communicate impact through the Incident process.

Repeated restore failure is a critical operational risk.

---

## Documentation Requirements

Recovery documentation should identify:

* Backup locations.
* retention policies.
* responsible roles.
* required credentials.
* restore commands.
* infrastructure prerequisites.
* validation steps.
* escalation contacts.
* expected RPO and RTO.
* known limitations.

Sensitive access details must remain in protected operational documentation.

---

## Prohibited Practices

The following are prohibited:

* Treating an untested backup as recoverable.
* storing all backups on the primary host.
* unencrypted production backups.
* unrestricted backup access.
* using Redis as the authoritative recovery source.
* restoring directly over the only working production copy.
* restoring deleted data without reapplying deletion rules.
* relying on one administrator’s local scripts.
* deleting backups without authorization and Audit.
* assuming application rollback restores lost data.

---

## Business Rules

* PostgreSQL is the primary authoritative backup target.
* production backups are automated, encrypted, and isolated.
* point-in-time recovery should be supported.
* backup restoration is tested regularly.
* object storage and encryption keys are included in recovery planning.
* Redis and Search are recoverable transient or derived systems.
* Outbox Records support asynchronous recovery.
* restoration occurs first in an isolated Environment.
* tenant isolation and deletion state are validated after restore.
* recovery performance is measured against defined RPO and RTO.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/deployment/09-disaster-recovery.md`
* `docs/deployment/10-release-strategy.md`
* `docs/database/15-retention-archival.md`
* `docs/security/06-data-protection.md`
* `docs/security/09-incident-response.md`
