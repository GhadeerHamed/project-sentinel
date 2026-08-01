# Repository Structure

## Purpose

This document defines the initial source-code structure for Project Sentinel.

The structure must preserve module ownership, framework independence, testability, and clear dependency direction.

---

## Top-Level Structure

```text
project-sentinel/
├── apps/
│   └── platform/
│       ├── app/
│       ├── bootstrap/
│       ├── config/
│       ├── database/
│       ├── docs/
│       └── public/
│       └── resources/
|       ├── routes/
|       ├── src/
|       ├── storage/
|       ├── tests/
|       ├── composer.json
├── compose.yaml
└── Dockerfile
```

`apps/platform/src/` contains Sentinel’s framework-independent modules.

`apps/platform/app/` contains Laravel runtime adapters.

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

Each directory represents one documented module.

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

Directories should be created only when they contain real code.

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

Domain code must not depend on Laravel or infrastructure classes.

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

Handlers invoke domain behavior through repository and service interfaces.

---

## Contracts

`Contract/` contains stable interfaces exposed outside the module when necessary.

Examples:

```text
src/Incident/Contract/
├── IncidentReader.php
├── IncidentCommandBus.php
└── IncidentReference.php
```

Internal classes should not be exposed merely for convenience.

Most module integration should use:

* Commands.
  * Queries.
  * domain events.
  * narrow public interfaces.

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
    ├── Providers/
    └── Observability/
```

Laravel classes adapt framework behavior to module contracts.

---

## HTTP Organization

HTTP classes should be grouped by module.

```text
app/Http/
├── Controllers/
│   ├── Incident/
│   ├── Alert/
│   └── Event/
├── Requests/
│   ├── Incident/
│   ├── Alert/
│   └── Event/
└── Resources/
    ├── Incident/
    ├── Alert/
    └── Event/
```

Controllers must remain thin.

They should:

1. Receive validated input.
   2. resolve Tenant Context.
   3. create a command or query.
   4. invoke its handler.
   5. return the mapped response.

---

## Persistence Adapters

Infrastructure repository implementations should be grouped by module.

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

Repository mappers translate between persistence and domain representations.

---

## Database Migrations

```text
database/
├── migrations/
├── factories/
└── seeders/
```

Migration filenames should identify the owning module where practical.

Example:

```text
2026_08_02_000001_create_organizations_table.php
2026_08_02_000002_create_memberships_table.php
2026_08_02_000003_create_projects_table.php
```

Every tenant-owned table must include `organization_id`.

---

## Queue Jobs

Laravel Jobs adapt Queue messages to application commands.

```text
app/Jobs/
├── Outbox/
├── Event/
├── Alert/
├── Notification/
├── Workflow/
├── Plugin/
└── Agent/
```

Jobs must contain identifiers and execution context, not serialized domain Entities.

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

Feature-specific helpers do not belong here.

---

## Tests

```text
tests/
├── Unit/
│   ├── Shared/
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

Test handlers using fake repositories and adapters.

### Integration Tests

Test PostgreSQL, Redis, repositories, Outbox, and Queue behavior.

### Feature Tests

Test HTTP contracts, authentication, authorization, and tenant isolation.

### Architecture Tests

Prevent invalid dependencies between modules and layers.

---

## Namespace Convention

Suggested namespace mapping:

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

Composer should map:

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Sentinel\\": "src/"
    }
  }
}
```

---

## Dependency Rules

Allowed:

```text
app/
→ src/*/Application
→ src/*/Domain
→ src/Shared
```

Prohibited:

```text
src/Domain
→ app/

Module A Domain
→ Module B Infrastructure

Module A
→ Module B internal Entity

Domain
→ Eloquent or Laravel facades
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

A class name should communicate one responsibility.

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

One handler should own each command or query.

---

## Configuration

Module configuration should live under:

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

Tenant-managed configuration belongs in authoritative database Resources, not deployment files.

---

## Routes

Routes should be split by API area where useful.

```text
routes/
├── api.php
├── api/
│   ├── organizations.php
│   ├── applications.php
│   ├── events.php
│   ├── alerts.php
│   └── incidents.php
└── console.php
```

Route files must not contain business logic.

---

## Initial Implementation Scope

Do not create every empty module directory immediately.

Start with:

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
└── Notification/
```

Add later modules when their implementation phase begins.

---

## Prohibited Practices

The following are prohibited:

* Business logic inside controllers.
  * Eloquent models used as domain Entities.
  * one global `Services` directory.
  * one global `Repositories` directory.
  * module-specific logic inside the Shared Kernel.
  * direct imports from another module’s internal folders.
  * jobs containing serialized mutable domain objects.
  * framework dependencies inside domain code.
  * empty abstraction layers without current value.
  * creating the entire future directory tree before implementation requires it.

---

## Business Rules

* Framework-independent modules live under `apps/platform/src/`.
  * Laravel runtime adapters live under `apps/platform/app/`.
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
