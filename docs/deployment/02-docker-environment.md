# Docker Environment

## Purpose

This document defines the container-based development and deployment conventions for Project Sentinel.

Docker provides consistent runtime Versions and isolates application dependencies from developer machines and hosting infrastructure.

---

## Container Model

Sentinel should use one application image with different process commands.

Primary containers:

```text
app
worker
scheduler
postgres
redis
object-storage
mail
```

Optional containers may include:

```text
search
observability
webhook-inspector
```

Optional services must not block the core development environment.

---

## Application Image

The application image contains:

* PHP runtime.
* required PHP extensions.
* Composer dependencies.
* application source.
* runtime entrypoint.
* production process dependencies.

The same image should support:

```text
Web requests
Queue workers
Scheduler
Maintenance commands
Database migrations
```

Process behavior is selected through the container command.

---

## Image Stages

The Dockerfile should use multi-stage builds.

Suggested stages:

```text
base
dependencies
development
frontend-build
production
```

Goals:

* Avoid development tools in production.
* reduce image size.
* reuse dependency layers.
* build frontend assets once.
* produce immutable releases.

---

## Base Image

The base image should:

* Pin the PHP major and minor Version.
* include only required system packages.
* install required PHP extensions.
* run application processes as a non-root User.
* use a consistent working directory.

Avoid relying on floating tags such as:

```text
latest
```

---

## Dependency Installation

Composer dependencies should be installed using the lock file.

Production build example:

```bash
composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --no-progress \
  --optimize-autoloader
```

The image build should fail when the lock file and dependency definition disagree.

---

## Frontend Assets

Frontend dependencies should be built in a separate Node.js stage.

Production images should contain only compiled assets, not:

* Node.js runtime.
* npm cache.
* development source maps unless explicitly required.
* frontend development dependencies.

---

## Docker Compose

Local development should use:

```text
compose.yaml
```

Optional overrides may include:

```text
compose.override.yaml
compose.ci.yaml
```

The main Compose file should describe the standard local environment.

Environment-specific production topology should not depend directly on local Compose configuration.

---

## Recommended Services

### Application

Runs the web application.

Example command:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

A dedicated web server and PHP process manager may replace this in production.

### Worker

Runs Queue jobs.

Example:

```bash
php artisan queue:work --sleep=1 --tries=3
```

Different Worker groups may use different Queue selections and limits.

### Scheduler

Runs scheduled commands.

Example:

```bash
php artisan schedule:work
```

### PostgreSQL

Provides the authoritative local database.

Its major Version should match production.

### Redis

Provides local:

* Queue transport.
* cache.
* rate limiting.
* temporary coordination.

### Object Storage

A compatible local object-storage service may simulate production storage behavior.

### Mail Capture

A development-only mail service captures outgoing email safely.

---

## Service Networking

Containers should communicate through internal Docker networks using service names.

Example:

```dotenv
DB_HOST=postgres
REDIS_HOST=redis
```

PostgreSQL and Redis should not be exposed publicly.

Local host ports should be published only when developers need direct access.

---

## Persistent Volumes

Persistent local volumes may be used for:

* PostgreSQL data.
* Redis data when required.
* object-storage files.
* Composer cache.
* package-manager cache.

Application source code should normally use a bind mount in development.

Production containers must not depend on writable application source mounts.

---

## File Permissions

Container processes should avoid creating files owned by an unusable host User.

Development images may map the host User and group identifiers.

Writable paths should be limited to:

```text
storage
bootstrap/cache
temporary runtime directories
```

The full application directory should not be globally writable.

---

## Environment Variables

Compose configuration should load local values from:

```text
.env
```

The repository must provide:

```text
.env.example
```

Secrets must not be embedded in:

* Dockerfiles.
* Compose files.
* image layers.
* source-controlled scripts.

Production secrets should be injected through the deployment secret-management system.

---

## Health Checks

Infrastructure services should define health checks.

Examples:

```text
PostgreSQL accepts connections
Redis responds to health requests
Object storage is available
Application readiness endpoint succeeds
```

Application startup dependencies should rely on health and retry behavior rather than fixed sleep periods.

---

## Startup Behavior

Container startup should be safe when multiple instances launch concurrently.

The application entrypoint may perform:

* configuration validation.
* directory preparation.
* cache optimization.
* dependency checks.

It must not automatically run destructive database operations.

Database migrations should run as an explicit release step.

---

## Development Commands

The repository should expose simple commands such as:

```bash
docker compose up -d
docker compose down
docker compose logs -f
docker compose exec app php artisan test
docker compose exec app php artisan migrate
```

Preferred wrappers may provide:

```bash
make start
make stop
make logs
make test
make shell
```

---

## Worker Separation

Worker containers should be separable by workload.

Examples:

```text
worker-events
worker-notifications
worker-workflows
worker-plugins
worker-agents
worker-projections
```

The MVP may begin with fewer Worker containers while retaining Queue separation in configuration.

---

## Resource Limits

Local defaults should remain lightweight.

Production containers should define appropriate:

* CPU limits.
* memory limits.
* process limits.
* Queue concurrency.
* execution timeouts.

Agent and Plugin workloads may require stricter limits than normal domain jobs.

---

## Container Security

Production images should:

* Run as non-root.
* contain no development credentials.
* exclude unnecessary operating-system tools.
* use patched base images.
* use read-only filesystems where practical.
* restrict Linux capabilities.
* avoid privileged mode.
* expose only required ports.

Container access must not replace application authorization.

---

## Image Scanning

CI should scan:

* Base images.
* operating-system packages.
* PHP dependencies.
* Node.js dependencies.
* generated production images.

Critical vulnerabilities should block release unless explicitly accepted through a documented security exception.

---

## Image Tagging

Production images should use immutable identifiers.

Recommended tags:

```text
commit SHA
release Version
```

Deployment should record the image digest.

Mutable tags may exist for convenience but must not be the authoritative release identifier.

---

## Build Reproducibility

Image builds should use:

* Dependency lock files.
* pinned base image Versions.
* deterministic build commands.
* controlled build arguments.
* immutable artifacts.

Build-time secrets must use secure temporary mounts and must not remain in image layers.

---

## Database Initialization

Local PostgreSQL initialization may create:

* Database.
* development User.
* required extensions.

Application schema creation must remain owned by application migrations.

Container initialization scripts must not replace migration history.

---

## Reset Procedure

The local environment may be reset using:

```bash
docker compose down --volumes
docker compose up -d
```

The project wrapper may then run:

```bash
php artisan migrate:fresh --seed
```

Reset commands must clearly indicate that local data will be destroyed.

---

## CI Usage

CI may use the same application Dockerfile with a dedicated test stage.

CI containers should:

* use isolated services.
* use temporary databases.
* avoid persistent volumes.
* run migrations before tests.
* produce Logs and reports as artifacts.
* stop after the pipeline completes.

---

## Production Usage

Production may run the application image through:

* Docker Compose for an early single-server deployment.
* a managed container platform.
* a container orchestrator.

The image contract should remain the same regardless of the hosting platform.

---

## Prohibited Practices

The following are prohibited:

* Running production containers as root without justification.
* embedding secrets in images.
* using mutable application source mounts in production.
* automatically running destructive migrations at startup.
* using unpinned runtime Versions.
* exposing PostgreSQL or Redis publicly.
* installing development dependencies in production images.
* using one unrestricted Worker for every workload indefinitely.
* treating a container boundary as a security boundary for tenant data.

---

## Business Rules

* One application image supports multiple process roles.
* Docker Compose is the default local environment.
* production images are immutable and non-root.
* dependency and base-image Versions are pinned.
* migrations run as an explicit release operation.
* infrastructure services remain private by default.
* Worker workloads can scale independently.
* secrets never enter image layers or source-controlled Compose files.
* the container contract remains portable across hosting platforms.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/01-local-development.md`
* `docs/deployment/03-ci-cd-pipeline.md`
* `docs/deployment/04-environment-configuration.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/06-workers-queues.md`
* `docs/security/05-secrets-credentials.md`
