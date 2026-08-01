# Disaster Recovery

## Purpose

This document defines how Project Sentinel restores critical service after a major infrastructure failure.

Disaster recovery addresses events beyond normal process restart, backup restoration, or routine incident handling.

---

## Disaster Scenarios

Examples include:

* Loss of the primary production host.
* PostgreSQL region or provider failure.
* destructive infrastructure changes.
* widespread credential compromise.
* object-storage unavailability.
* container registry failure.
* prolonged network outage.
* corrupted production data.
* loss of a hosting region.
* major third-party provider outage.

---

## Recovery Priorities

Recovery should follow this order:

1. Protect people, credentials, and remaining evidence.
2. contain ongoing security or destructive activity.
3. restore authoritative data.
4. restore authentication and tenant isolation.
5. restore core API and ingestion.
6. restore Workers and asynchronous processing.
7. restore integrations, Agents, Search, and non-critical services.
8. reconcile delayed operations.

---

## Critical Capabilities

### Tier 1

Required for minimum platform operation:

```text id="95a5do"
PostgreSQL
Web/API runtime
authentication
tenant isolation
secret access
object storage for required evidence
```

### Tier 2

Required for normal operational processing:

```text id="9cgvz9"
Redis
Queue Workers
Outbox publishing
Alert processing
Incident operations
Notifications
scheduler
```

### Tier 3

May recover after core service:

```text id="76y0p2"
Search Projections
Plugin synchronization
Agent execution
analytics
non-critical exports
```

Recovery order may change according to customer impact and Incident conditions.

---

## Recovery Objectives

Disaster recovery must define:

```text id="go1mlk"
Recovery Time Objective (RTO)
Recovery Point Objective (RPO)
Maximum Tolerable Downtime
```

Initial targets should remain consistent with the backup and production topology capabilities.

Targets are commitments only after they have been tested successfully.

---

## Recovery Strategy

The initial Sentinel strategy is:

```text id="98mgzk"
Rebuild infrastructure
↓
Restore authoritative PostgreSQL data
↓
Restore secrets and storage access
↓
Deploy the last trusted application image
↓
Rebuild derived systems
↓
Resume asynchronous processing
```

The early production architecture does not require an always-running secondary region.

---

## Recovery Site

Recovery infrastructure may be created in:

* Another host within the same provider.
* another availability zone.
* another provider region.
* a secondary provider when necessary.

The recovery location must support:

* Required PostgreSQL Version.
* container execution.
* Redis.
* object storage access.
* TLS.
* secret management.
* monitoring.

---

## Infrastructure as Code

Production infrastructure should be reproducible from version-controlled definitions.

Recovery must not depend on undocumented manual setup.

Infrastructure definitions should cover:

* Network boundaries.
* compute resources.
* database connectivity.
* Redis.
* object storage.
* secret references.
* DNS.
* monitoring.
* access controls.

Secrets themselves must not be stored in infrastructure repositories.

---

## Trusted Recovery Artifacts

Recovery requires access to:

```text id="azrwfz"
source repository
container registry
image digest
database backups
object-storage recovery controls
infrastructure definitions
deployment configuration
secret-management system
recovery documentation
```

Critical artifacts should not depend on one provider account or one individual administrator.

---

## Disaster Declaration

A disaster may be declared when:

* Production cannot be restored within normal operational procedures.
* the primary database or region is unavailable for an unacceptable period.
* infrastructure integrity cannot be trusted.
* widespread corruption or destructive access is confirmed.
* recovery requires rebuilding major platform components.

The declaration must identify a Disaster Recovery Lead.

---

## Recovery Roles

Typical roles include:

* Disaster Recovery Lead.
* Infrastructure Lead.
* Database Recovery Lead.
* Security Lead.
* Application Lead.
* Communications Lead.
* Validation Lead.

One person may hold multiple roles during early-stage operations.

Decision ownership must remain explicit.

---

## Recovery Runbook

The recovery process should follow:

```text id="ubzaxi"
Declare disaster
↓
Contain ongoing damage
↓
Preserve evidence
↓
Select trusted restore point
↓
Provision recovery infrastructure
↓
Restore PostgreSQL
↓
Restore storage and secret access
↓
Deploy trusted application release
↓
Run migrations if required
↓
Restore Redis and Workers
↓
Rebuild Search and other Projections
↓
Validate security and data integrity
↓
Switch traffic
↓
Monitor and reconcile
```

---

## Data Restoration

PostgreSQL restoration must use a verified backup or point-in-time recovery target.

Before traffic is enabled, validate:

* Database integrity.
* migration Version.
* Organization ownership.
* identities and Memberships.
* credential metadata.
* Outbox state.
* Audit Records.
* Resource relationships.
* encryption-key access.

Restoration must occur in an isolated environment first.

---

## Application Recovery

Application services should deploy from the last trusted immutable image.

The release must be identified by:

```text id="jyn5b5"
release version
source commit
image digest
migration compatibility
```

A newly built unverified image should not be introduced during emergency recovery unless no trusted artifact exists.

---

## Redis and Queue Recovery

Redis should be recreated after authoritative services are available.

Recovery steps include:

1. Start Redis.
2. start Outbox Workers.
3. republish unpublished Outbox Records.
4. restore Worker groups gradually.
5. inspect failed and interrupted jobs.
6. monitor Queue backlog and job age.
7. reconcile provider-side operations.

Duplicate processing must remain safe through idempotency.

---

## Search Recovery

Search is derived and should be rebuilt from authoritative state.

The rebuild should:

* Preserve Organization isolation.
* process in bounded batches.
* support checkpoints.
* validate document counts.
* compare selected Resources with PostgreSQL.
* replace stale indexes safely.

Search may remain unavailable while core Resource APIs operate.

---

## Object Storage Recovery

Object-storage recovery must verify:

* Bucket or container availability.
* evidence-object integrity.
* archive access.
* lifecycle configuration.
* private access controls.
* signed URL generation.
* Organization-scoped ownership.

Missing evidence files must be identified and associated with affected Resources.

---

## Secret and Credential Recovery

Recovery must verify access to:

* Application signing keys.
* database credentials.
* object-storage credentials.
* provider credentials.
* webhook signing secrets.
* encryption keys.

Credentials should be rotated when:

* Provider accounts may be compromised.
* recovery infrastructure changes trust boundaries.
* previous administrative access cannot be trusted.
* secret exposure is suspected.

---

## DNS and Traffic Cutover

Traffic should move only after readiness and validation succeed.

Cutover may use:

* DNS changes.
* load-balancer target changes.
* reverse-proxy changes.
* managed platform routing.

Cutover plans must include:

* Existing TTL values.
* certificate readiness.
* rollback route.
* health validation.
* communication timing.

---

## Validation Before Cutover

Required validation includes:

* HTTPS and certificates.
* authentication.
* Organization isolation.
* authorization.
* database reads and writes.
* Queue dispatch and processing.
* Outbox publication.
* object-storage access.
* scheduler heartbeat.
* Audit persistence.
* critical API smoke tests.
* monitoring and Alert delivery.

Optional providers may remain disabled until validated.

---

## Degraded Operation

Sentinel may temporarily operate with reduced capabilities.

Possible temporary restrictions:

* Disable Agent execution.
* pause Plugin synchronization.
* disable large exports.
* limit ingestion volume.
* use reduced Worker concurrency.
* temporarily disable Search.
* queue non-critical Notifications.

Degraded behavior must remain explicit and monitored.

Security controls must never be disabled to accelerate recovery.

---

## Data Reconciliation

After recovery, Sentinel must reconcile:

* Unpublished Outbox Records.
* interrupted Queue jobs.
* provider operations with uncertain outcomes.
* delayed Notifications.
* incomplete Plugin synchronizations.
* Agent executions interrupted during processing.
* Search Projection gaps.
* object-storage references.
* retention jobs missed during downtime.

Reconciliation must use idempotent operations.

---

## Security During Recovery

Recovery infrastructure must enforce:

* Strong administrator authentication.
* MFA.
* least-privilege access.
* short-lived credentials where possible.
* restricted network access.
* Audit recording.
* approved secret management.

Emergency access must not become permanent access.

---

## Communications

Communication should include:

* Disaster declaration.
* current customer impact.
* affected capabilities.
* recovery progress.
* expected operational limitations.
* recovery confirmation.
* required customer Actions.

Only verified information should be communicated.

Security-related details must follow the security Incident process.

---

## Recovery Monitoring

During recovery, monitor:

```text id="kkjvyv"
database restoration progress
application readiness
Queue backlog
oldest job age
error rate
request latency
storage failures
provider failures
data reconciliation progress
security Alerts
```

Monitoring must be operational before normal traffic resumes.

---

## Failback

When operating from temporary recovery infrastructure, returning to the preferred topology requires a separate controlled plan.

Failback must include:

* Data synchronization.
* compatibility validation.
* traffic cutover.
* rollback path.
* post-cutover verification.

Failback should not begin until the recovered service is stable.

---

## Recovery Testing

Disaster recovery exercises should test:

* Infrastructure recreation.
* backup restoration.
* secret access.
* trusted image deployment.
* DNS or traffic cutover.
* Queue recovery.
* Projection rebuild.
* security validation.
* communications workflow.

Recommended minimum:

```text id="15rsgk"
Tabletop exercise: every six months
Technical recovery exercise: annually
Additional exercise: after major topology changes
```

Early-stage exercises may be more frequent while procedures are being established.

---

## Exercise Evidence

Each exercise should record:

* Scenario.
* participants.
* start and completion times.
* restore point.
* achieved RPO and RTO.
* failed steps.
* undocumented dependencies.
* security findings.
* corrective Actions.
* responsible owners.

Unresolved critical findings must remain tracked.

---

## Recovery Documentation

Protected recovery documentation should include:

* Required access paths.
* infrastructure commands.
* backup locations.
* secret recovery procedures.
* DNS ownership.
* provider escalation paths.
* validation checklist.
* communication responsibilities.
* rollback and failback steps.

Documentation must remain accessible during primary-system failure.

---

## Prohibited Practices

The following are prohibited:

* Depending on one administrator for recovery.
* storing every recovery artifact in one failure domain.
* rebuilding from an untrusted release without review.
* enabling traffic before tenant-isolation validation.
* disabling security controls during recovery.
* assuming restored Queue state is authoritative.
* restoring compromised credentials unchanged.
* performing failback without a separate plan.
* declaring recovery complete before reconciliation.
* relying on an untested recovery runbook.

---

## Business Rules

* Disaster recovery restores authoritative capabilities before derived services.
* recovery infrastructure is reproducible from controlled definitions.
* only trusted backups and immutable application artifacts are used.
* tenant isolation and authorization are validated before traffic cutover.
* asynchronous processing resumes through Outbox and idempotent reconciliation.
* recovery may operate temporarily in an explicit degraded mode.
* compromised or uncertain credentials are rotated.
* monitoring is restored before normal traffic.
* recovery procedures are exercised and measured.
* disaster closure requires service restoration, reconciliation, and assigned corrective Actions.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/deployment/08-backup-recovery.md`
* `docs/deployment/10-release-strategy.md`
* `docs/deployment/11-scaling.md`
* `docs/security/08-security-monitoring.md`
* `docs/security/09-incident-response.md`
