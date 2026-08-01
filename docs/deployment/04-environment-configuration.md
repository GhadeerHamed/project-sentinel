# Environment Configuration

## Purpose

This document defines how Project Sentinel manages runtime configuration across local, testing, staging, and production Environments.

Configuration must remain explicit, validated, secure, and independent from application builds.

---

## Core Principles

* Application images are immutable.
* Configuration is supplied at runtime.
* Secrets are managed separately from normal configuration.
* Every Environment has isolated values.
* Required configuration is validated before the application accepts work.
* Production behavior must not depend on undocumented defaults.

---

## Configuration Categories

Sentinel configuration is divided into:

```text
Application configuration
Infrastructure configuration
Feature configuration
Provider configuration
Security configuration
Tenant-managed configuration
Secrets and credentials
```

Tenant-managed configuration belongs in authoritative domain storage rather than deployment environment variables.

---

## Configuration Sources

Approved sources include:

* Environment variables.
* mounted configuration files.
* deployment-platform configuration.
* secret-management references.
* authoritative database configuration for tenant-owned settings.

Configuration precedence must be documented and deterministic.

---

## Recommended Precedence

From highest to lowest:

```text
Runtime override
↓
Environment-specific deployment configuration
↓
Application default
```

Secrets must not be replaced by unsafe defaults.

---

## Environment Separation

Required Environments:

* `local`
* `testing`
* `staging`
* `production`

Each Environment must use separate:

* Databases.
* Redis instances or namespaces.
* object-storage locations.
* credentials.
* encryption context.
* Queue configuration.
* external provider accounts where practical.

Production credentials must never be available in lower Environments.

---

## Environment Identifier

Every runtime must define:

```dotenv
APP_ENV=production
```

Supported values must be validated.

Unknown Environment names should fail startup rather than silently fall back to development behavior.

---

## Required Configuration

The application must explicitly declare required configuration.

Examples:

```text
Application URL
database connection
Redis connection
Queue driver
storage driver
mail driver
encryption configuration
authentication configuration
observability configuration
```

Missing required values must prevent readiness.

---

## Configuration Validation

Startup validation should detect:

* Missing required values.
* invalid URLs.
* unsupported drivers.
* invalid numeric ranges.
* insecure production defaults.
* incompatible feature combinations.
* unavailable secret references.
* malformed encryption keys.
* duplicate instance identities where prohibited.

Validation errors must identify the configuration key without revealing secret values.

---

## `.env.example`

The repository must contain:

```text
.env.example
```

It should:

* List all locally required variables.
* use safe development values.
* document optional variables.
* avoid real credentials.
* remain synchronized with application configuration.

Local `.env` files must not be committed.

---

## Configuration Naming

Environment variable names should be:

* Uppercase.
* stable.
* capability-oriented.
* grouped by prefix.
* unambiguous.

Examples:

```dotenv
APP_ENV=production
APP_URL=https://sentinel.example.com

DB_HOST=postgres
DB_PORT=5432

REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=redis

STORAGE_DRIVER=s3
```

Avoid provider-specific names in domain-level configuration unless the setting is truly provider-specific.

---

## Boolean Values

Boolean values must use a consistent representation.

Preferred:

```dotenv
FEATURE_ENABLED=true
```

Application parsing must reject ambiguous values where practical.

---

## Numeric Values

Timeouts and limits should include the unit in the variable name.

Examples:

```dotenv
HTTP_TIMEOUT_SECONDS=15
QUEUE_RETRY_DELAY_SECONDS=30
AGENT_MAX_EXECUTION_SECONDS=300
UPLOAD_MAX_BYTES=10485760
```

Avoid values whose unit must be inferred.

---

## URLs and Endpoints

Endpoint configuration must use complete validated URLs.

Example:

```dotenv
SEARCH_ENDPOINT=https://search.internal
```

Production configuration should reject:

* insecure HTTP where TLS is required.
* loopback addresses for external services.
* unexpected protocols.
* credentials embedded in URLs.

---

## Secrets

Secret values must come from an approved secret-management mechanism.

Examples include:

* Database passwords.
* API signing keys.
* provider credentials.
* webhook secrets.
* encryption keys.

Normal configuration may contain only secret references where supported.

Example:

```dotenv
DATABASE_PASSWORD_SECRET_REF=production/database/password
```

---

## Application Key Material

Application encryption and signing keys must:

* Be generated securely.
* differ across Environments.
* remain outside source control.
* support controlled rotation.
* be backed up securely where recovery requires them.

Changing key material without a migration plan may make encrypted data unreadable.

---

## Database Configuration

Database configuration should include:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=sentinel
DB_USERNAME=sentinel_app
```

Production should use:

* TLS where supported.
* least-privilege credentials.
* connection limits.
* bounded connection and query timeouts.
* separate migration credentials where appropriate.

---

## Redis Configuration

Redis configuration may include:

```dotenv
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DATABASE=0
```

Production should define:

* Authentication where required.
* TLS where supported.
* connection and command timeouts.
* Queue and cache namespace separation.
* eviction behavior appropriate to each use.

Redis configuration must not imply authoritative persistence.

---

## Queue Configuration

Queue configuration should define:

* Driver.
* default Queue.
* retry behavior.
* visibility timeout.
* failed-job storage.
* Worker shutdown timeout.

Example:

```dotenv
QUEUE_CONNECTION=redis
QUEUE_DEFAULT=default
QUEUE_RETRY_AFTER_SECONDS=120
```

Worker-specific Queue selection belongs in deployment process configuration.

---

## Storage Configuration

Storage configuration should define:

* Driver.
* bucket or container.
* region.
* endpoint where applicable.
* path prefix.
* signed URL duration.

Example:

```dotenv
STORAGE_DRIVER=s3
STORAGE_BUCKET=sentinel-production
STORAGE_REGION=eu-central-1
```

Credentials must remain in secret management.

---

## Mail and Notification Configuration

Notification configuration should define:

* Default transport.
* sender identity.
* provider endpoint.
* delivery timeout.
* retry behavior.
* safe recipient controls for non-production Environments.

Staging must not accidentally notify production recipients.

---

## Observability Configuration

Observability configuration may include:

```dotenv
LOG_LEVEL=info
LOG_FORMAT=json
TRACING_ENABLED=true
METRICS_ENABLED=true
SERVICE_NAME=sentinel-web
```

Secrets, raw credentials, and unrestricted tenant payloads must remain excluded regardless of Log level.

---

## Feature Flags

Operational feature flags may control:

* Gradual rollout.
* temporary disablement.
* provider migration.
* experimental behavior.
* emergency containment.

Feature flags must not replace authorization or tenant-isolation controls.

Long-lived product configuration should move into governed application storage.

---

## Environment-Specific Defaults

### Local

May enable:

* Debugging.
* local mail capture.
* fake providers.
* verbose Logs.
* disposable storage.

### Testing

Should use:

* Deterministic values.
* isolated databases.
* fake external providers.
* fast retry behavior.
* controlled clocks.

### Staging

Should resemble production while using isolated data and credentials.

### Production

Must require:

* Debug mode disabled.
* secure cookies.
* trusted proxy configuration.
* encrypted transport.
* managed secrets.
* restricted provider destinations.
* structured Logs.

---

## Production Safety Validation

Production startup must reject configurations such as:

```text
APP_DEBUG=true
default development passwords
local mail transport
fake authentication providers
public database exposure
insecure cookie configuration
unrestricted CORS
disabled authorization controls
```

These checks should run before readiness succeeds.

---

## Configuration Reload

Most configuration should load at process startup.

Changes normally require:

* New deployment.
* rolling process restart.
* Worker restart.

Dynamic reload should be used only where correctness and consistency can be guaranteed.

---

## Laravel Configuration Cache

Production deployments may use:

```bash
php artisan config:cache
```

Configuration caching must occur after runtime values are available.

Application code must read configuration through the configuration layer rather than calling environment-variable functions directly outside configuration files.

---

## Configuration Versioning

Deployment configuration should be version-controlled where it does not contain secrets.

Changes should identify:

* Changed keys.
* affected services.
* required restart.
* migration or rollout dependency.
* rollback behavior.

Secret values remain outside version control, but secret-reference changes may be reviewed.

---

## Configuration Drift

Sentinel should detect or prevent drift between expected and active configuration.

Useful controls include:

* Infrastructure as code.
* configuration manifests.
* deployment checksums.
* startup validation.
* release metadata.
* periodic environment audits.

Manual production changes should be exceptional and recorded.

---

## Failure Behavior

If critical configuration becomes unavailable:

* New processes must fail startup.
* existing processes should fail safely.
* affected health checks should report unready.
* security controls must not fall back to permissive behavior.
* operational Alerts should be created where appropriate.

Optional provider failure should not necessarily make the full platform unavailable.

---

## Testing Requirements

Tests should cover:

* Missing required values.
* invalid values.
* production-insecure values.
* configuration precedence.
* secret-reference failures.
* feature flag behavior.
* Environment separation.
* configuration caching.

---

## Prohibited Practices

The following are prohibited:

* Hard-coded production configuration.
* secrets committed to source control.
* real credentials in `.env.example`.
* using production credentials in staging.
* reading environment variables throughout domain code.
* silent fallback to insecure defaults.
* changing encryption keys without a migration plan.
* using feature flags to bypass authorization.
* undocumented manual production configuration.
* exposing secret values in validation errors.

---

## Business Rules

* Configuration is supplied independently from application images.
* Every Environment uses isolated configuration and credentials.
* required configuration is validated before readiness.
* secrets are managed separately from normal configuration.
* domain and tenant configuration belongs in authoritative application storage.
* production rejects insecure development settings.
* configuration changes are reviewed and traceable.
* processes restart when configuration changes require it.
* configuration failure must never weaken security controls.

---

## Related Documents

* `docs/deployment/00-deployment-overview.md`
* `docs/deployment/01-local-development.md`
* `docs/deployment/02-docker-environment.md`
* `docs/deployment/03-ci-cd-pipeline.md`
* `docs/deployment/05-production-topology.md`
* `docs/deployment/06-workers-queues.md`
* `docs/deployment/07-monitoring-observability.md`
* `docs/security/05-secrets-credentials.md`
* `docs/security/06-data-protection.md`
