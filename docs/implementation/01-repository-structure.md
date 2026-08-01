# Repository Structure

## Purpose

This document defines the initial source-code structure for Project Sentinel.

The structure must preserve:

* Module ownership.
* framework independence.
* testability.
* explicit dependency direction.
* separation between repository orchestration and the Laravel application.

---

## Repository Model

Project Sentinel uses a monorepo-style structure.

The Laravel modular monolith is located at:

```text
apps/platform/
```

Repository-level Docker, documentation, scripts, assets, and future reusable packages remain outside the Laravel application.

---

## Top-Level Structure

```text
project-sentinel/
├── .github/
│   ├── ISSUE_TEMPLATE/
│   └── workflows/
├── apps/
│   └── platform/
│       ├── app/
│       ├── bootstrap/
│       ├── config/
│       ├── database/
│       ├── public/
│       ├── resources/
│       ├── routes/
│       ├── src/
│       ├── storage/
│       ├── tests/
│       ├── artisan
│       ├── composer.json
│       ├── package.json
│       └── phpunit.xml
├── assets/
├── docker/
│   ├── nginx/
│   ├── php/
│   │   └── Dockerfile
│   └── postgres/
├── docs/
├── packages/
├── scripts/
├── .env.example
├── .gitignore
├── compose.yaml
└── README.md
```

`apps/platform/src/` contains Sentinel’s framework-independent modules.

`apps/platform/app/` contains Laravel runtime and infrastructure adapters.

The repository root contains orchestration, documentation, and shared project assets.

---

## Laravel Application Root

Unless otherwise stated, paths in this document are relative to:

```text
apps/platform/
```

For example:

```text
src/Incident/
```

means:

```text
apps/platform/src/Incident/
```

---

## Module Structure

```text
src/
├── Shared/
├── IdentityAccess/
├── Organization/
├── Project/
├── Application/
├── Environment/
├── Event/
├── Alert/
├── Incident/
├── Knowledge/
├── Agent/
├── Plugin/
├── Notification/
├── Search/
├── Administration/
├── Observability/
└── Workflow/
```

Each directory represents one documented Sentinel module.

Modules should be created incrementally as implementation reaches them.

---

## Standard Module Layout

```text
src/Incident/
├── Domain/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Event/
│   ├── Exception/
│   ├── Repository/
│   └── Service/
├── Application/
│   ├── Command/
│   ├── Query/
│   ├── Handler/
│   ├── Policy/
│   └── Data/
└── Contract/
```

Not every module requires every directory.

A directory should be created only when it contains real code.

---

## Domain Layer

The Domain layer contains:

* Entities.
* Value Objects.
* lifecycle rules.
* domain services.
* domain events.
* repository interfaces.
* domain exceptions.

Example:

```text
src/Incident/Domain/
├── Entity/
│   └── Incident.php
├── ValueObject/
│   ├── IncidentId.php
│   ├── IncidentStatus.php
│   └── IncidentSeverity.php
├── Event/
│   ├── IncidentCreated.php
│   └── IncidentResolved.php
├── Repository/
│   └── IncidentRepository.php
└── Exception/
    └── InvalidIncidentTransition.php
```

Domain code must not depend on:

* Laravel.
* Eloquent.
* HTTP.
* Redis.
* queues.
* external provider SDKs.
* infrastructure implementations.

---

## Application Layer

The Application layer coordinates domain behavior.

It contains:

* Commands.
* Queries.
* handlers.
* authorization Policies.
* transaction orchestration.
* application DTOs.
* public use-case interfaces.

Example:

```text
src/Incident/Application/
├── Command/
│   ├── CreateIncident.php
│   └── ResolveIncident.php
├── Query/
│   └── GetIncident.php
├── Handler/
│   ├── CreateIncidentHandler.php
│   ├── ResolveIncidentHandler.php
│   └── GetIncidentHandler.php
├── Policy/
│   └── IncidentPolicy.php
└── Data/
    └── IncidentView.php
```

Handlers invoke domain behavior through repository and infrastructure interfaces.

---

## Contracts

`Contract/` contains stable interfaces or data contracts exposed outside the module when necessary.

Example:

```text
src/Incident/Contract/
├── IncidentReader.php
└── IncidentReference.php
```

Internal module classes must not be exposed merely for convenience.

Module integration should normally use:

* Commands.
* Queries.
* domain events.
* narrow public interfaces.
* immutable Resource references.

---

## Laravel Runtime Structure

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Console/
│   └── Commands/
├── Providers/
└── Infrastructure/
    ├── Persistence/
    ├── Messaging/
    ├── Authentication/
    ├── Authorization/
    ├── Storage/
    ├── Search/
    ├── ExternalProviders/
    └── Observability/
```

Laravel classes adapt framework and infrastructure behavior to module contracts.

They must not own domain rules.

---

## HTTP Organization

HTTP classes should be grouped by module.

```text
app/Http/
├── Controllers/
│   ├── Organization/
│   ├── Application/
│   ├── Event/
│   ├── Alert/
│   └── Incident/
├── Requests/
│   ├── Organization/
│   ├── Application/
│   ├── Event/
│   ├── Alert/
│   └── Incident/
└── Resources/
    ├── Organization/
    ├── Application/
    ├── Event/
    ├── Alert/
    └── Incident/
```

Controllers must remain thin.

They should:

1. Receive validated input.
2. resolve authentication and Tenant Context.
3. construct a command or query.
4. invoke the appropriate handler.
5. map the result to an HTTP response.

Controllers must not contain lifecycle or business rules.

---

## Persistence Adapters

Infrastructure repository implementations should be grouped by owning module.

```text
app/Infrastructure/Persistence/
├── Incident/
│   ├── EloquentIncidentRepository.php
│   ├── IncidentRecord.php
│   └── IncidentMapper.php
├── Alert/
└── Event/
```

Eloquent models are persistence records, not domain Entities.

Repository mappers translate between persistence records and domain objects.

---

## Database Structure

```text
database/
├── migrations/
├── factories/
└── seeders/
```

Actual migrations belong inside:

```text
apps/platform/database/migrations/
```

Documentation-only migration directories must not be used.

Migration filenames should identify the owned Resource clearly.

Example:

```text
2026_08_02_000001_create_organizations_table.php
2026_08_02_000002_create_memberships_table.php
2026_08_02_000003_create_projects_table.php
```

Every tenant-owned table must include:

```text
organization_id
```

Primary Resource identifiers use UUIDv7 and PostgreSQL native `uuid`.

---

## Queue Jobs

Laravel Jobs adapt queue messages to module commands and handlers.

```text
app/Jobs/
├── Outbox/
├── Event/
├── Alert/
├── Incident/
├── Notification/
├── Workflow/
├── Plugin/
└── Agent/
```

Jobs must contain:

* Resource identifiers.
* Organization context.
* acting identity reference.
* correlation identifier.
* causation identifier.
* payload Version.

Jobs must not serialize complete mutable domain Entities.

---

## Shared Kernel

```text
src/Shared/
├── Domain/
│   ├── Identifier/
│   ├── Event/
│   ├── Error/
│   ├── Time/
│   └── Concurrency/
└── Application/
    ├── TenantContext.php
    ├── TransactionManager.php
    └── AuthorizationContext.php
```

The Shared Kernel must remain small and stable.

Feature-specific helpers and business rules do not belong in it.

---

## Tests

```text
tests/
├── Unit/
│   ├── Shared/
│   ├── Organization/
│   ├── Incident/
│   └── Alert/
├── Application/
├── Integration/
│   ├── Persistence/
│   ├── Queue/
│   └── Outbox/
├── Feature/
│   └── Api/
└── Architecture/
```

### Unit Tests

Test domain objects without Laravel or external infrastructure.

### Application Tests

Test commands, queries, handlers, and Policies using fake repositories and adapters.

### Integration Tests

Test:

* PostgreSQL repositories.
* Redis.
* queues.
* Outbox publication.
* transaction behavior.
* external adapters where appropriate.

### Feature Tests

Test:

* HTTP contracts.
* authentication.
* authorization.
* tenant isolation.
* validation.
* API error responses.

### Architecture Tests

Prevent invalid dependencies between modules, layers, and runtime code.

---

## Namespace Convention

Framework-independent Sentinel classes use:

```text
Sentinel\Shared\
Sentinel\Incident\
Sentinel\Alert\
Sentinel\Event\
```

Laravel runtime classes remain under:

```text
App\
```

The Laravel application’s `composer.json` should include:

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Sentinel\\": "src/",
      "Database\\Factories\\": "database/factories/",
      "Database\\Seeders\\": "database/seeders/"
    }
  }
}
```

After changing autoload configuration, run:

```bash
composer dump-autoload
```

---

## Dependency Rules

Allowed dependency direction:

```text
app/
→ src/*/Application
→ src/*/Domain
→ src/Shared
```

Also allowed:

```text
Module Application
→ another module's public Contract
```

Prohibited dependencies:

```text
src/*/Domain
→ app/

Module A Domain
→ Module B Infrastructure

Module A
→ Module B internal Entity

Domain
→ Eloquent

Domain
→ Laravel facades
```

Architecture tests should enforce these rules.

---

## Naming Rules

Use business-oriented names.

Preferred:

```text
AcknowledgeAlert
ResolveIncident
PublishKnowledgeArticle
InstallPlugin
```

Avoid vague names such as:

```text
AlertService
Helper
Manager
Processor
CommonUtils
```

A class name should communicate one clear responsibility.

---

## Commands and Queries

Commands and Queries should be immutable.

Example:

```php
final readonly class ResolveIncident
{
    public function __construct(
        public OrganizationId $organizationId,
        public IncidentId $incidentId,
        public ResourceVersion $expectedVersion,
        public IdentityReference $actor,
    ) {
    }
}
```

Each command or query should have one responsible handler.

Commands express intent to change state.

Queries retrieve information without changing authoritative state.

---

## Configuration

Module-related runtime configuration should live under:

```text
config/sentinel/
```

Example:

```text
config/sentinel/
├── events.php
├── alerts.php
├── notifications.php
├── agents.php
└── retention.php
```

Deployment configuration belongs in environment variables or infrastructure configuration.

Tenant-managed settings belong in authoritative database Resources.

Secrets must use approved secret-management references.

---

## Routes

Routes should be split by API area when useful.

```text
routes/
├── api.php
├── api/
│   ├── organizations.php
│   ├── projects.php
│   ├── applications.php
│   ├── environments.php
│   ├── events.php
│   ├── alerts.php
│   └── incidents.php
└── console.php
```

Route files must not contain business logic.

---

## Root Docker Structure

Docker orchestration belongs at the repository root.

```text
project-sentinel/
├── compose.yaml
└── docker/
    ├── php/
    │   └── Dockerfile
    ├── nginx/
    │   └── default.conf
    └── postgres/
```

Docker services should mount or build from:

```text
./apps/platform
```

The Laravel application must not contain a second unrelated Docker orchestration structure unless a specific need is documented.

---

## Packages Directory

The root `packages/` directory is reserved for future reusable packages.

Possible future packages include:

```text
packages/
├── php-sdk/
├── laravel-sdk/
└── plugin-sdk/
```

The main Sentinel domain modules must remain inside:

```text
apps/platform/src/
```

They should not be extracted into packages prematurely.

---

## Initial Implementation Scope

Do not create every module directory immediately.

Start with:

```text
src/
├── Shared/
├── IdentityAccess/
└── Organization/
```

Add the following when their implementation phase begins:

```text
Project
Application
Environment
Event
Alert
Incident
Notification
```

Advanced modules should be created only when work begins on them.

---

## Prohibited Practices

The following are prohibited:

* Business logic inside controllers.
* Eloquent models used as domain Entities.
* one global `Services` directory.
* one global `Repositories` directory.
* module-specific logic inside the Shared Kernel.
* direct imports from another module’s internal folders.
* Jobs containing serialized mutable domain objects.
* framework dependencies inside domain code.
* duplicate migration directories outside the Laravel application.
* empty abstraction layers without current value.
* creating the entire future directory tree before implementation requires it.
* placing the Laravel application directly in the repository root.

---

## Business Rules

* The Laravel modular monolith lives under `apps/platform/`.
* framework-independent modules live under `apps/platform/src/`.
* Laravel runtime adapters live under `apps/platform/app/`.
* repository-level Docker, documentation, assets, and scripts remain at the root.
* each module owns its Domain and Application layers.
* repository interfaces belong to the owning module.
* repository implementations belong to infrastructure.
* Eloquent records are not domain Entities.
* commands and queries express business intent.
* tests mirror module and layer boundaries.
* architecture tests enforce dependency rules.
* directories are created incrementally as real code is introduced.

---

## Related Documents

* `docs/implementation/00-implementation-plan.md`
* `docs/implementation/02-mvp-roadmap.md`
* `docs/modules/00-module-architecture.md`
* `docs/modules/01-shared-kernel.md`
* `docs/modules/18-laravel-runtime-adapter.md`
* `docs/deployment/01-local-development.md`
* `docs/deployment/02-docker-environment.md`
