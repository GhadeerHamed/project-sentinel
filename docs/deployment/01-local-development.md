# Local Development

## Purpose

This document defines the standard local development workflow for Project Sentinel.

The goal is to provide a predictable environment that closely matches production without requiring paid infrastructure.

---

## Development Principles

Local development must be:

* Reproducible.
* inexpensive.
* isolated from production.
* easy to reset.
* close to production behavior.
* suitable for Web, Worker, and scheduler processes.

Local convenience must not create production-only assumptions.

---

## Required Services

The initial local environment requires:

```text
Application runtime
PostgreSQL
Redis
Object storage emulator or local-compatible storage
Mail testing service
```

Optional services may include:

* Search engine.
* tracing collector.
* local AI provider mock.
* webhook inspection service.

Optional infrastructure should not block basic development.

---

## Recommended Tooling

The Laravel runtime should use:

```text
PHP
Composer
Node.js
npm
PostgreSQL
Redis
Docker and Docker Compose
Git
```

Exact supported Versions must be declared in the repository through files such as:

```text
composer.json
package.json
Dockerfile
compose.yaml
```

Developers should not rely on undocumented globally installed Versions.

---

## Standard Development Path

Docker Compose is the recommended default development environment.

It provides consistent Versions for:

* PHP.
* PostgreSQL.
* Redis.
* supporting services.

Native execution may be supported for developers who prefer it, but it must use the same service contracts and configuration conventions.

Docker-specific configuration is defined in:

```text
docs/deployment/02-docker-environment.md
```

---

## Repository Setup

Initial setup should follow this sequence:

```bash
git clone <repository-url>
cd project-sentinel

cp .env.example .env

composer install
npm install

php artisan key:generate
php artisan migrate
php artisan db:seed
```

Container-based commands may wrap these operations.

The repository should provide a single documented bootstrap command where practical.

Example:

```bash
make setup
```

---

## Local Configuration

`.env.example` defines all required local configuration keys.

Local `.env` files:

* must not be committed.
* must not contain production secrets.
* should use safe development defaults.
* should reference local service names.
* should remain replaceable.

Configuration changes must also update `.env.example` and relevant documentation.

---

## Example Local Configuration

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=sentinel
DB_USERNAME=sentinel
DB_PASSWORD=sentinel

CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=smtp
```

These values are examples and must not be reused as production credentials.

---

## Application Processes

Local development should run the same logical processes used in production:

```text
Web Application
Queue Worker
Scheduler
Asset Development Server
```

Typical commands:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
npm run dev
```

Each process should be independently restartable.

---

## Queue Processing

Developers should normally run at least one Queue Worker.

Queue processing is required for features such as:

* Event processing.
* Alert evaluation.
* Notifications.
* Workflows.
* Plugins.
* Agents.
* Search Projections.
* Outbox publishing.

Tests must not assume asynchronous work completed unless the Worker or test Queue driver processes it.

---

## Scheduler

The local scheduler should run continuously when testing:

* Workflow Timers.
* retention checks.
* scheduled Plugin synchronization.
* credential-expiration checks.
* maintenance operations.

Recommended command:

```bash
php artisan schedule:work
```

Scheduled behavior should also be directly testable without waiting for clock execution.

---

## Database Setup

The local PostgreSQL database should be disposable.

Standard operations should include:

```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
```

Seed data should provide:

* A development Organization.
* sample Users and Memberships.
* sample Project, Application, and Environment.
* representative Events, Alerts, and Incidents.
* safe test credentials or provider mocks.

Seed data must not contain real customer information.

---

## Database Migrations

Developers must:

* create migrations for schema changes.
* avoid manually modifying shared databases.
* test migrations from a clean database.
* test upgrades from the previous schema.
* avoid destructive migrations without a migration plan.

Schema and application changes should remain compatible during deployment transitions.

---

## Local Object Storage

Local development may use:

* A compatible object-storage emulator.
* a dedicated local filesystem adapter.

Application code must use the storage abstraction rather than direct filesystem paths.

Local behavior should preserve:

* Organization ownership.
* access authorization.
* file metadata.
* retention behavior.

---

## Email and Notifications

Development email must use a safe local capture service.

Local Notifications must not be delivered to real recipients by default.

External providers should use:

* Sandbox accounts.
* fake credentials.
* provider mocks.
* explicit opt-in delivery.

---

## External Integrations

Plugins and provider integrations should support local test modes.

Local development must not require production credentials.

Preferred options:

* Provider sandbox.
* mock server.
* recorded safe fixtures.
* fake adapter.
* disabled integration.

Provider failures should be reproducible locally.

---

## Agent Development

Agent development should support a fake or deterministic provider.

The fake provider should allow testing:

* Structured outputs.
* failures.
* timeouts.
* invalid responses.
* Action proposals.
* token or cost accounting.

Real provider use must be optional and use developer-owned test credentials.

---

## Testing

Standard test commands should include:

```bash
php artisan test
```

Additional suites may include:

```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Integration
```

Tests should verify:

* Tenant isolation.
* authorization.
* idempotency.
* optimistic concurrency.
* Queue behavior.
* Outbox publishing.
* Agent and Plugin capability limits.

---

## Code Quality

The repository should provide commands for:

* Formatting.
* static analysis.
* automated tests.
* architecture validation.
* dependency security checks.

Example interface:

```bash
make format
make analyse
make test
make check
```

The same checks should run in CI.

---

## Debugging

Local debugging may include:

* Application Logs.
* Queue failure inspection.
* database query inspection.
* request tracing.
* Redis inspection.
* webhook capture.
* debugger integration.

Debug tools must not become production dependencies.

Sensitive values must still be redacted in local Logs.

---

## Failed Jobs

Developers should be able to inspect and retry failed jobs.

Typical commands:

```bash
php artisan queue:failed
php artisan queue:retry <job-id>
php artisan queue:forget <job-id>
```

Retrying a job must preserve:

* Organization context.
* identity context.
* correlation identifier.
* idempotency behavior.

---

## Resetting the Environment

The environment should support a complete reset.

Example:

```bash
make reset
```

A reset may:

1. Stop local processes.
2. remove disposable containers and volumes.
3. recreate services.
4. install dependencies.
5. run migrations.
6. seed development data.

Reset commands must never target production infrastructure.

---

## Local Security Rules

Developers must not:

* Use production data locally.
* copy production credentials.
* disable tenant isolation.
* commit `.env` files.
* bypass authorization for convenience.
* log plaintext secrets.
* expose local services publicly without protection.

Production incidents should be reproduced using sanitized fixtures.

---

## Developer Workflow

Recommended workflow:

```text
Pull current branch
↓
Start local services
↓
Run migrations
↓
Implement change
↓
Run focused tests
↓
Run full checks
↓
Review migration and security impact
↓
Commit
```

Changes involving architecture or business rules should update the corresponding documentation.

---

## Required Repository Commands

The project should eventually provide stable commands for:

```text
setup
start
stop
restart
test
check
reset
logs
worker
scheduler
```

Their implementation may use Make, shell scripts, Composer scripts, or another lightweight task runner.

The command interface should remain simple and documented.

---

## Business Rules

* Docker Compose is the recommended local development path.
* Local services use disposable development data.
* Production credentials and customer data are prohibited locally.
* Web, Worker, and scheduler responsibilities remain separate.
* PostgreSQL behavior should match production as closely as practical.
* external providers must support sandbox, fake, or disabled modes.
* local security controls must not be bypassed.
* repository commands should make setup, testing, and reset predictable.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/02-docker-environment.md`
* `docs/deployment/03-ci-cd-pipeline.md`
* `docs/deployment/04-environment-configuration.md`
* `docs/deployment/06-workers-queues.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/security/05-secrets-credentials.md`
