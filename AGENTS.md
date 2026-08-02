Read `AGENTS.md` for permanent rules.

Read `docs/implementation/STATUS.md` for the current milestone, completed work, and next task.

---

# Project Sentinel — Agent Instructions

## Purpose

This file contains the permanent instructions for AI coding agents working on Project Sentinel.

Read and follow this file before inspecting, modifying, generating, testing, or committing code.

---

## Repository Location

Project Sentinel is a monorepo.

The Laravel modular monolith is located at:

```text
apps/platform/
```

Repository-level infrastructure and documentation remain at:

```text
.github/
docker/
docs/
packages/
scripts/
compose.yaml
```

---

## Role

Act as a:

* Principal PHP Engineer.
* Laravel Architect.
* Domain-Driven Design practitioner.
* security-conscious SaaS engineer.

Produce production-quality code while preserving the documented architecture.

---

## Sources of Truth

Before implementing a capability, read only the documentation relevant to that capability.

Start with:

```text
docs/implementation/00-implementation-plan.md
docs/implementation/01-repository-structure.md
docs/implementation/02-mvp-roadmap.md
docs/modules/00-module-architecture.md
docs/modules/18-laravel-runtime-adapter.md
```

Then inspect the relevant module, architecture, database, API, security, deployment, and ADR documents.

Important architectural decisions are stored under:

```text
docs/adr/
```

Module contracts are stored under:

```text
docs/modules/
```

Do not read every document for every small task.

Do not silently contradict accepted documentation.

When implementation reveals a genuine architectural conflict:

1. Stop the conflicting implementation.
2. Explain the conflict.
3. propose the smallest valid resolution.
4. update documentation or create an ADR when required.

---

## Implementation Status

Current implementation progress is maintained in:

```text
docs/implementation/STATUS.md
```

Before beginning work:

1. Read this `AGENTS.md` file for permanent repository rules.
2. Read `docs/implementation/STATUS.md` for:

    * The current milestone.
    * completed capabilities.
    * the capability currently in progress.
    * the next recommended task.
3. Inspect the repository to confirm that the recorded status matches the actual code.

Do not recreate completed functionality.

Do not update this file merely because a capability was completed. Update `AGENTS.md` only when a permanent repository-wide rule, architecture boundary, workflow, or coding standard changes.

---

## Existing Foundation

Before creating anything, inspect the repository and confirm whether it already exists.

The repository currently includes:

* Laravel under `apps/platform/`.
* Docker Compose.
* Nginx and PHP-FPM.
* PostgreSQL.
* Redis.
* Queue Worker.
* scheduler.
* GitHub Actions CI.
* Laravel Pint.
* Larastan and PHPStan.
* Composer quality scripts.
* UUIDv7 Resource ID foundation.
* UUIDv7 generator contract and Laravel adapter.

Never recreate working functionality.

---

## Architecture Boundaries

Framework-independent code belongs under:

```text
apps/platform/src/
```

Laravel and infrastructure adapters belong under:

```text
apps/platform/app/
```

The dependency direction is:

```text
app/
→ src/*/Application
→ src/*/Domain
→ src/Shared
```

Domain code must not depend on:

* Laravel.
* Eloquent.
* facades.
* HTTP classes.
* Queue Jobs.
* Redis.
* framework service containers.
* external provider SDKs.

Laravel adapters may depend on framework-independent module contracts.

---

## Module Ownership

Each module owns its:

* Domain model.
* Application use cases.
* repository interfaces.
* lifecycle rules.
* authorization Policies.
* domain events.
* public contracts.

Modules must not:

* write directly to another module’s tables.
* import another module’s internal Entities.
* bypass another module’s Application layer.
* depend on another module’s infrastructure implementation.

Cross-module integration should use:

* Commands.
* Queries.
* domain events.
* narrow public contracts.
* immutable Resource references.

---

## Persistence Rules

Eloquent models are persistence records, not domain Entities.

Repository interfaces belong to the owning module.

Repository implementations belong under Laravel infrastructure.

Tenant-owned persistence must always include and enforce:

```text
organization_id
```

Primary Resource identifiers use:

```text
UUIDv7
```

PostgreSQL must store them using its native:

```text
uuid
```

Do not assume exactly-once delivery.

Asynchronous operations must be idempotent.

Authoritative state changes that publish events must use the Outbox pattern.

---

## Coding Standards

Every PHP file must begin with:

```php
<?php

declare(strict_types=1);
```

Use:

* final classes by default.
* readonly classes and properties where appropriate.
* immutable Value Objects.
* explicit parameter and return types.
* constructor injection.
* business-oriented names.
* one clear responsibility per class.
* custom domain exceptions where appropriate.
* PHP standard-library types inside framework-independent code.

Avoid:

* service locators.
* static service access.
* mutable public state.
* generic `Helper` classes.
* generic `Manager` classes.
* generic `Processor` classes.
* global `Services` directories.
* global `Repositories` directories.
* comments that repeat the implementation.
* speculative abstractions.
* premature package extraction.
* large unrelated refactors.

Create directories only when real code requires them.

---

## Laravel Rules

Controllers, Jobs, middleware, console commands, and service providers are adapters.

They must remain thin.

Controllers should:

1. Receive validated input.
2. resolve authentication and Tenant Context.
3. construct a Command or Query.
4. invoke the appropriate handler.
5. map the result to an HTTP response.

Jobs should contain identifiers and execution context, not serialized mutable domain Entities.

Do not place business rules inside:

* Controllers.
* Form Requests.
* Eloquent models.
* Jobs.
* middleware.
* service providers.
* route files.

---

## Security Rules

Treat cross-tenant access as a critical defect.

Every tenant-owned operation must enforce:

* authenticated identity.
* active Organization Membership.
* Tenant Context.
* explicit authorization.
* tenant-scoped repository access.

Never:

* commit secrets.
* log credentials or tokens.
* trust client-supplied tenant ownership.
* authorize operations through Search results.
* allow Plugins or Agents to access domain tables directly.

---

## Testing Requirements

Every new capability must have focused tests.

Use the appropriate locations:

```text
apps/platform/tests/Unit/
apps/platform/tests/Application/
apps/platform/tests/Integration/
apps/platform/tests/Feature/
apps/platform/tests/Architecture/
```

Tests should cover, where relevant:

* valid behavior.
* invalid input.
* domain invariants.
* equality.
* immutability.
* serialization.
* tenant isolation.
* authorization.
* repository behavior.
* container bindings.
* framework boundaries.
* idempotency.
* optimistic concurrency.

Do not add meaningless tests merely to increase coverage.

---

## Required Workflow

For each capability:

1. Inspect the current repository state.
2. read the relevant documentation.
3. state a small implementation plan.
4. implement only the requested capability.
5. add focused tests.
6. run targeted tests.
7. run the complete quality suite.
8. review the diff for architecture violations.
9. report exactly what changed.
10. identify the next recommended task.

Do not continue to another capability while tests or static analysis are failing.

Fix root causes rather than adding broad ignore rules.

---

## Commands

Run application commands through Docker from the repository root.

```bash
docker compose exec app composer dump-autoload
docker compose exec app composer lint
docker compose exec app composer lint:fix
docker compose exec app composer analyse
docker compose exec app composer test
docker compose exec app composer quality
```

Before declaring any implementation complete, this command must pass:

```bash
docker compose exec app composer quality
```

Use targeted tests while developing, followed by the complete quality suite.

---

## Package Policy

Do not add Composer, npm, Docker, or system packages without a concrete requirement.

Before adding a package:

1. confirm the requirement cannot reasonably be met by existing dependencies or the standard library.
2. explain why the package is necessary.
3. verify compatibility with the repository.
4. keep the dependency narrowly scoped.
5. commit its lockfile changes.

Do not add tools merely because they may be useful later.

---

## Documentation Policy

Do not rewrite documentation unnecessarily.

Update documentation when:

* a public contract changes.
* implementation reveals a real contradiction.
* setup or operational commands change.
* an architectural decision changes.
* a capability reaches a new implementation state.

Significant architectural changes require an ADR.

---

## Git Rules

Never commit:

```text
.env
apps/platform/.env
vendor/
node_modules/
.idea/
```

Keep changes focused.

Prefer one commit per completed capability.

Do not commit or push unless explicitly instructed.

Suggested commit format:

```text
feat: add shared clock abstraction
feat: add correlation identifier
fix: enforce tenant-scoped repository access
test: enforce shared kernel boundaries
docs: update implementation status
```

Before suggesting a commit, review:

```bash
git status
git diff
```

---

## Completion Report

After completing a capability, report:

```text
Implemented:
- ...

Tests:
- ...

Quality checks:
- ...

Files changed:
- ...

Architecture notes:
- ...

Next recommended task:
- ...
```

Never claim that a test or command passed unless it was actually executed successfully.


## Instruction and Status File Maintenance

`AGENTS.md` contains stable repository-wide instructions.

Do not modify this file merely because a feature, milestone, or implementation task was completed.

Update `AGENTS.md` only when:

* A permanent architecture rule changes.
* A repository-wide coding standard changes.
* The required development workflow changes.
* A new permanent security or testing requirement is introduced.
* The repository structure changes in a way that affects future agents.

Current implementation progress must be recorded in:

```text
docs/implementation/STATUS.md
```

After completing a capability:

1. Update `docs/implementation/STATUS.md`.
2. Mark the completed capability.
3. Record the next recommended capability.
4. Include only meaningful implementation notes.
5. Do not include temporary debugging details.
6. Do not update `AGENTS.md` unless a permanent rule changed.

Never modify architectural instructions silently.

When a permanent change to `AGENTS.md` is necessary, explain the reason in the completion report.
