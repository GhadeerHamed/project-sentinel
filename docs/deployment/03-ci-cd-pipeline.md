# CI/CD Pipeline

## Purpose

This document defines the continuous integration and deployment pipeline for Project Sentinel.

The pipeline must produce tested, secure, traceable, and reproducible releases.

---

## Objectives

The CI/CD process should:

* Detect defects before merge.
* enforce architecture and quality rules.
* build immutable application images.
* scan dependencies and images.
* deploy through controlled Environments.
* support safe rollback.
* preserve release evidence.

---

## Pipeline Stages

Recommended pipeline:

```text
Validate
↓
Install dependencies
↓
Static analysis
↓
Automated tests
↓
Security checks
↓
Build image
↓
Publish artifact
↓
Deploy
↓
Verify
```

Independent checks should run in parallel where practical.

---

## Pull Request Pipeline

Every pull request should run:

* Dependency validation.
* code formatting checks.
* static analysis.
* unit tests.
* feature and integration tests.
* architecture tests.
* migration validation.
* frontend build.
* dependency vulnerability scanning.

A pull request must not merge while required checks are failing.

---

## Main Branch Pipeline

A successful merge to the main branch should:

1. Run the complete validation suite.
2. build the production image.
3. scan the final image.
4. publish it to the container registry.
5. record image digest and source commit.
6. deploy automatically to staging.
7. run staging verification.

Production deployment remains a separate controlled action.

---

## Release Identification

Every release must be traceable to:

```text
source commit
pipeline run
application Version
container image digest
migration Version
deployment Environment
deployment timestamp
```

The image digest is the authoritative deployed artifact identifier.

---

## Dependency Installation

CI must install dependencies from lock files.

Required examples:

```bash
composer install --no-interaction --prefer-dist
npm ci
```

CI should fail when:

* Lock files are missing.
* dependency definitions and lock files disagree.
* required platform extensions are unavailable.
* dependency installation modifies tracked files.

---

## Code Quality

Required checks should include:

```text
Formatting
Static analysis
Coding standards
Architecture boundaries
Configuration validation
```

Suggested command interface:

```bash
make format-check
make analyse
make architecture-test
```

Local and CI commands should remain equivalent.

---

## Automated Tests

The pipeline should run:

* Unit tests.
* domain behavior tests.
* application integration tests.
* API tests.
* database integration tests.
* tenant-isolation tests.
* authorization tests.
* Queue and Outbox tests.

Critical business rules must not depend only on end-to-end tests.

---

## Database Testing

CI should start an isolated PostgreSQL instance and run:

```bash
php artisan migrate
php artisan test
```

Migration checks should verify:

* Clean installation.
* upgrade from the previous schema where practical.
* tenant ownership fields.
* required indexes.
* safe constraints.
* absence of destructive accidental changes.

Large production data migrations require separate validation.

---

## Queue and Scheduler Testing

CI should verify asynchronous behavior for:

* Domain event handling.
* Outbox publishing.
* retries.
* idempotency.
* failed jobs.
* Workflow Steps.
* Notifications.
* Agent and Plugin executions.

Time-dependent behavior should use controlled clocks rather than real waiting.

---

## Security Checks

Required security checks should include:

* PHP dependency audit.
* Node.js dependency audit.
* secret scanning.
* container image scanning.
* static security analysis where useful.
* prohibited configuration checks.

Critical findings should block release unless covered by an approved and documented exception.

---

## Secret Protection

CI secrets must:

* Come from the CI secret store.
* be scoped to the required Environment.
* use least privilege.
* remain masked in Logs.
* avoid pull request execution from untrusted forks.
* be rotated and revocable.

Build secrets must not remain inside image layers or artifacts.

---

## Image Build

The production image should be built once per release.

The same immutable image should move through:

```text
Staging
↓
Production
```

Production must not rebuild source independently.

---

## Artifact Publishing

Published artifacts may include:

* Container image.
* dependency audit reports.
* test reports.
* code coverage.
* migration plan.
* software bill of materials.
* release metadata.

Artifacts should use defined retention periods.

---

## Staging Deployment

Deployment to staging should be automatic after the main branch pipeline succeeds.

Staging verification should include:

* Application readiness.
* database connectivity.
* Redis connectivity.
* Queue processing.
* scheduler health.
* migration status.
* critical API smoke tests.
* Event-to-Alert processing smoke test.

---

## Production Approval

Production deployment should require an explicit authorized action.

Approval must identify:

* Release Version.
* image digest.
* migration changes.
* known risks.
* rollback path.

Emergency deployment procedures may reduce approval steps but must remain attributable and audited.

---

## Deployment Sequence

Recommended sequence:

```text
Validate target Environment
↓
Acquire deployment coordination lock
↓
Run safe migrations
↓
Deploy application instances
↓
Deploy Worker groups
↓
Verify readiness
↓
Run smoke tests
↓
Record deployment
↓
Release coordination lock
```

The exact order may vary when compatibility requirements demand Workers or Web processes deploy first.

---

## Migration Safety

Database migrations must be compatible with rolling deployment.

Preferred pattern:

```text
Expand
↓
Deploy compatible application
↓
Backfill
↓
Switch behavior
↓
Contract later
```

Schema removal should not occur in the same release that stops using the old schema.

---

## Deployment Concurrency

Only one production deployment should modify the same Environment at a time.

The pipeline must prevent:

* concurrent migrations.
* overlapping releases.
* rollback and deployment executing simultaneously.
* multiple schedulers performing release tasks.

---

## Smoke Tests

Post-deployment smoke tests should verify:

* Health and readiness endpoints.
* authentication.
* Organization-scoped Resource access.
* database writes.
* Queue execution.
* Outbox publishing.
* cache connectivity.
* critical provider integration health.

Tests must use dedicated safe Resources.

---

## Rollback

Rollback should restore the previous known-good application image.

Rollback must consider database compatibility.

A release is rollback-safe only when the previous application Version can operate with the current schema.

Destructive data changes may require a separate recovery procedure instead of a simple image rollback.

---

## Failed Deployment

When verification fails, the pipeline should:

1. Stop further rollout.
2. preserve deployment Logs.
3. assess migration state.
4. roll back where safe.
5. restore Worker compatibility.
6. create an operational Incident when required.
7. record the failed release.

A failed deployment must not be silently retried indefinitely.

---

## Deployment Strategies

Initial production deployment may use:

* Rolling replacement.
* recreate deployment for a small single-server environment.

Later deployments may support:

* Blue-green deployment.
* canary deployment.
* progressive traffic shifting.

The strategy must preserve migration and Worker compatibility.

---

## Branch Protection

The main branch should require:

* Reviewed pull request.
* successful required checks.
* resolved review discussions.
* restricted direct pushes.
* controlled administrative bypass.

Emergency bypasses must be exceptional and attributable.

---

## Environment Separation

CI/CD credentials and deployment targets must remain separate for:

* Testing.
* staging.
* production.

A staging pipeline must not have permission to modify production.

Production deployment credentials should be available only to protected branches and authorized workflows.

---

## Pipeline Observability

The pipeline should expose:

* Stage duration.
* failure reason.
* test results.
* security findings.
* artifact identifiers.
* deployment status.
* rollback status.

Deployment markers should be sent to the observability platform.

---

## Notifications

Relevant teams should be notified about:

* Failed main branch builds.
* failed staging deployment.
* production deployment start and completion.
* production rollback.
* critical security findings.
* migration failure.

Notifications must link to the exact pipeline and release identifiers.

---

## Pipeline Maintenance

CI/CD configuration is production code.

Changes must receive:

* Review.
* testing.
* least-privilege evaluation.
* secret-access review.
* rollback consideration.

Third-party pipeline Actions should be pinned to immutable Versions or commit identifiers.

---

## Prohibited Practices

The following are prohibited:

* Deploying untested local builds.
* rebuilding a different image for production.
* using mutable tags as the authoritative release.
* embedding secrets in pipeline files.
* exposing production secrets to untrusted pull requests.
* running destructive migrations automatically.
* ignoring failed security scans without documented acceptance.
* allowing overlapping production deployments.
* deploying without post-release verification.
* assuming application rollback reverses database changes.

---

## Business Rules

* Every merge requires successful automated validation.
* Releases use one immutable image across Environments.
* Production deployments are explicit and attributable.
* database migrations support rolling compatibility.
* only one production deployment runs at a time.
* critical security findings block release unless formally accepted.
* every release has a documented rollback path.
* deployment success requires health and smoke-test verification.
* CI/CD configuration follows the same review standards as application code.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/01-local-development.md`
* `docs/deployment/02-docker-environment.md`
* `docs/deployment/04-environment-configuration.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/08-backup-recovery.md`
* `docs/deployment/10-release-strategy.md`
* `docs/security/05-secrets-credentials.md`
* `docs/security/08-security-monitoring.md`
