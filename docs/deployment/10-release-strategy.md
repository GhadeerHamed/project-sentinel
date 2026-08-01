# Release Strategy

## Purpose

This document defines how Project Sentinel prepares, approves, deploys, verifies, and rolls back application releases.

The release strategy must preserve service availability, data integrity, Queue compatibility, and tenant isolation.

---

## Core Principles

* Every release is immutable and traceable.
* The same artifact moves from staging to production.
* Releases must remain backward-compatible during rollout.
* Database changes use safe staged migration patterns.
* Features may be deployed separately from being enabled.
* Every production release has a rollback or recovery plan.
* Release success is determined by production verification, not deployment completion alone.

---

## Release Unit

The primary release unit is the production application image.

One image may run as:

```text id="yw8uz1"
Web process
Queue Worker
Scheduler
Maintenance command
Migration command
```

The release must identify:

```text id="qlv39n"
release_version
source_commit
image_digest
pipeline_run
migration_version
build_timestamp
```

---

## Versioning

Sentinel should use semantic-style product Versions:

```text id="sr0giq"
MAJOR.MINOR.PATCH
```

Meaning:

* `MAJOR` — incompatible platform or contract change.
* `MINOR` — backward-compatible capability or substantial improvement.
* `PATCH` — backward-compatible fix.

Pre-release identifiers may be used:

```text id="ohwgpo"
1.4.0-rc.1
```

The immutable image digest remains the authoritative deployed artifact identifier.

---

## Release Types

### Standard Release

Contains planned features, fixes, migrations, and operational changes.

### Patch Release

Contains limited backward-compatible fixes.

### Hotfix

Addresses an urgent production defect or security issue.

### Infrastructure Release

Changes deployment topology or managed infrastructure without necessarily changing application behavior.

### Data Migration Release

Performs controlled backfill, correction, or schema transition work.

Different release types may require different approval and validation depth.

---

## Release Frequency

Sentinel should prefer small, frequent releases over large infrequent deployments.

A practical early-stage approach is:

* Deploy to staging after every successful main-branch build.
* Deploy production changes when the release is verified and operationally ready.
* Avoid fixed release trains unless coordination needs justify them.

Release frequency must not weaken testing or approval requirements.

---

## Release Readiness

A release is ready for production when:

* Required CI checks pass.
* The production image is built and scanned.
* staging deployment succeeds.
* migrations are reviewed.
* Queue payload compatibility is verified.
* security impact is assessed.
* operational dashboards and Alerts are ready.
* rollback or recovery steps are documented.
* known risks are accepted by an authorized owner.

---

## Change Classification

Each change should be classified by risk.

| Risk     | Examples                                                                                       |
| -------- | ---------------------------------------------------------------------------------------------- |
| Low      | Internal refactor, isolated bug fix, documentation                                             |
| Medium   | New endpoint, new Queue job, additive schema change                                            |
| High     | Authentication, authorization, tenant isolation, destructive migration, billing, data movement |
| Critical | Emergency security containment or active production recovery                                   |

Risk determines review, approval, rollout, and monitoring requirements.

---

## Release Manifest

Every production release should have a manifest containing:

```text id="09hpod"
release_version
image_digest
source_commit
included changes
database migrations
configuration changes
feature flags
Queue compatibility notes
known risks
verification steps
rollback procedure
release owner
```

The manifest may be generated automatically from pipeline and repository metadata.

---

## Staging Promotion

Production must use the exact image verified in staging.

Promotion should validate:

* Image digest matches the approved artifact.
* required configuration exists.
* secret references are valid.
* migration prerequisites are satisfied.
* target Environment is healthy.
* no conflicting deployment is active.

Production must not rebuild the application from source.

---

## Deployment Strategy

The initial production strategy may use:

* Rolling deployment when multiple instances exist.
* controlled recreate deployment for an early single-host topology.

Later strategies may include:

* Blue-green deployment.
* canary deployment.
* progressive traffic shifting.

The chosen strategy must preserve compatibility between old and new processes during the transition.

---

## Process Deployment Order

Deployment order depends on compatibility.

A common sequence is:

```text id="wye0jk"
Run additive migrations
↓
Deploy compatible Web processes
↓
Deploy compatible Workers
↓
Deploy scheduler
↓
Verify processing
↓
Enable new behavior
```

Workers must not consume payloads they cannot understand.

---

## Database Changes

Database changes should follow:

```text id="jin0ot"
Expand
↓
Deploy compatible code
↓
Backfill
↓
Switch reads and writes
↓
Verify
↓
Contract in a later release
```

Examples of expand changes:

* Add nullable column.
* add new table.
* add compatible index.
* add new enum representation without removing the old one.

Destructive removal must occur only after all active code no longer depends on the old structure.

---

## Data Backfills

Large data backfills must run separately from normal request handling.

Backfills should be:

* Bounded.
* resumable.
* idempotent.
* tenant-aware.
* observable.
* rate-limited.
* safe to stop and restart.

A deployment should not wait on an unbounded backfill.

---

## Queue Compatibility

Queued jobs may outlive the process Version that created them.

Therefore:

* Breaking payload changes require Versioning.
* new Workers should tolerate supported older payloads.
* old Workers must not receive unsupported new payloads.
* job handlers must reject unknown Versions safely.
* delayed and retrying jobs must be considered before removing compatibility.

Queue compatibility is part of release readiness.

---

## API Compatibility

Public API changes should remain backward-compatible within the supported API Version.

Breaking changes require:

* A new API Version or explicit migration contract.
* deprecation notice.
* documented replacement.
* transition period.
* usage monitoring.
* controlled removal.

Internal APIs should also avoid unnecessary breaking changes during rolling deployment.

---

## Feature Flags

Features may be deployed disabled and enabled later.

Feature flags are useful for:

* Gradual rollout.
* selected Organization rollout.
* provider migration.
* emergency disablement.
* experimental capabilities.
* separating deployment from release.

Feature flags must not bypass:

* Authorization.
* tenant isolation.
* data validation.
* Audit requirements.

---

## Flag Lifecycle

Every feature flag should have:

```text id="eq0zm0"
owner
purpose
default state
target scope
created_at
removal condition
```

Temporary flags must be removed after rollout stabilizes.

Long-lived product settings should move into governed configuration.

---

## Gradual Rollout

Higher-risk releases may roll out through:

```text id="wbqsfl"
Internal use
↓
Staging validation
↓
Selected test Organizations
↓
Limited production percentage
↓
Full production
```

Rollout decisions should use measured health and business outcomes.

Tenant selection must remain explicit and authorized.

---

## Release Verification

Verification should include:

* Application readiness.
* API health.
* authentication.
* tenant-scoped access.
* database reads and writes.
* Queue dispatch and completion.
* Outbox publication.
* scheduler heartbeat.
* critical provider connectivity.
* error and latency comparison.
* security monitoring.

Verification must use dedicated safe Resources.

---

## Observation Window

After production deployment, the release owner should observe:

* Error rate.
* request latency.
* Queue backlog.
* failed jobs.
* database load.
* provider failures.
* authentication and authorization failures.
* tenant-isolation Alerts.
* customer-impact indicators.

The required observation duration depends on release risk and traffic volume.

---

## Release Success

A release is successful only when:

* Deployment completes.
* required health checks pass.
* smoke tests pass.
* critical Queues process normally.
* monitoring shows no unacceptable regression.
* required feature activation succeeds.
* no rollback criteria are triggered.

A pipeline completion alone does not establish release success.

---

## Rollback Criteria

Rollback should be considered when:

* Error rate exceeds the accepted threshold.
* critical requests fail.
* tenant isolation or authorization is uncertain.
* database corruption is suspected.
* Queue processing is incompatible.
* production performance degrades severely.
* critical provider behavior becomes unsafe.
* verification cannot be completed.

Security uncertainty should favor containment over continued rollout.

---

## Application Rollback

Application rollback restores the previous trusted image.

Before rollback, verify:

* The previous Version supports the current database schema.
* queued payloads remain compatible.
* configuration remains compatible.
* feature flags are returned to a safe state.
* Workers are rolled back consistently.

Rollback must create a deployment record.

---

## Database Rollback

Database migrations should not normally depend on automatic down migrations in production.

Safer options include:

* Keep additive schema changes.
* disable new behavior.
* deploy corrective forward migration.
* restore from backup only when data integrity requires it.

Data restoration is a recovery operation, not a routine application rollback.

---

## Feature Rollback

When code remains safe, a feature may be disabled without rolling back the full application.

Feature rollback is appropriate when:

* The failure is isolated.
* stored data remains compatible.
* disabled behavior does not leave incomplete operations.
* security is not compromised.

Feature disablement must remain observable and traceable.

---

## Failed Release

When a release fails:

1. Stop rollout.
2. disable affected feature flags where appropriate.
3. preserve Logs and deployment evidence.
4. assess database and Queue state.
5. roll back or apply a corrective release.
6. create an Incident when impact warrants it.
7. document the failure and follow-up Actions.

Repeated automatic production retries are prohibited.

---

## Hotfix Process

A hotfix may use an expedited path but must still include:

* Identified source commit.
* automated tests relevant to the defect.
* security and migration review.
* immutable image build.
* authorized production approval.
* deployment verification.
* follow-up normal review if emergency controls were used.

Urgency does not justify untraceable manual modification.

---

## Security Releases

Security fixes may require:

* Confidential preparation.
* credential rotation.
* immediate feature disablement.
* Plugin or Agent suspension.
* coordinated customer communication.
* mandatory update deadlines.

Security release details must be shared according to need-to-know.

---

## Release Notes

Each customer-visible release should include concise notes covering:

* New capabilities.
* important improvements.
* resolved defects.
* relevant behavior changes.
* deprecations.
* required customer Actions.

Internal release notes should additionally include migrations, risks, and operational details.

Secrets and exploitable security details must not be included.

---

## Deprecation

Deprecation should define:

```text id="5mncjs"
deprecated capability
replacement
announcement date
support period
removal target
affected consumers
```

Usage should be monitored during the transition.

Removal must not occur while supported consumers still depend on the deprecated behavior without an approved exception.

---

## Release Communication

Relevant stakeholders should be informed about:

* Planned production deployment.
* expected impact.
* maintenance or degraded behavior.
* release completion.
* rollback.
* customer Actions.
* unresolved issues.

Routine low-risk releases may use automated communication.

---

## Maintenance Windows

Sentinel should aim for zero-downtime releases.

A maintenance window is justified only when the change cannot safely occur online.

Maintenance releases require:

* Defined start and end.
* customer communication where applicable.
* tested execution plan.
* rollback or recovery plan.
* active monitoring.
* explicit completion confirmation.

---

## Audit and Traceability

Production release records should preserve:

```text id="9v004b"
release_version
image_digest
initiating identity
approving identity
deployment timestamps
target Environment
migration outcome
verification outcome
rollback outcome
```

Emergency access and manual intervention require additional Audit recording.

---

## Release Metrics

Useful release metrics include:

* Deployment frequency.
* deployment duration.
* change failure rate.
* rollback frequency.
* mean time to recovery.
* failed verification rate.
* post-release Incident rate.
* lead time from merge to production.

Metrics should improve the process rather than encourage unsafe deployment volume.

---

## Prohibited Practices

The following are prohibited:

* Rebuilding a different artifact for production.
* deploying mutable tags without digest verification.
* releasing without rollback or recovery planning.
* destructive schema changes in one step.
* breaking queued-job payloads without Versioning.
* enabling high-risk features globally without verification.
* using feature flags to bypass security controls.
* silently retrying failed production deployments.
* manual production code changes outside the release process.
* declaring success before post-deployment verification.

---

## Business Rules

* The same immutable image moves from staging to production.
* every production release is attributable and traceable.
* releases remain compatible during rolling transitions.
* database changes use expand-and-contract migration.
* queued jobs remain compatible across supported Versions.
* feature deployment may be separated from feature activation.
* high-risk changes use gradual rollout where practical.
* release success requires verification and observation.
* rollback never assumes database changes are automatically reversible.
* hotfixes remain tested, immutable, approved, and audited.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/03-ci-cd-pipeline.md`
* `docs/deployment/04-environment-configuration.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/06-workers-queues.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/deployment/08-backup-recovery.md`
* `docs/deployment/09-disaster-recovery.md`
* `docs/deployment/11-scaling.md`
