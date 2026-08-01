# Module Architecture

## Purpose

This document defines the rules for organizing Project Sentinel into cohesive application modules.

Modules establish ownership boundaries around business capabilities while remaining deployable initially as one modular monolith.

---

## Architectural Model

Sentinel uses:

```text
Modular Monolith
+
Domain-Driven Module Boundaries
+
Event-Driven Integration
+
Framework-Independent Core
```

Modules are independently designed but initially run in the same application and database deployment.

---

## Module Goals

Each module must provide:

* Clear business responsibility.
* explicit Resource ownership.
* stable public contracts.
* controlled dependencies.
* isolated domain rules.
* independent tests.
* a path to future service extraction where justified.

A module must not exist only to group technical classes.

---

## Module Categories

Sentinel modules fall into four categories.

### Business Modules

Own authoritative operational capabilities:

* Organization.
* Project.
* Application.
* Environment.
* Event.
* Alert.
* Incident.
* Knowledge.
* Agent.
* Plugin.
* Notification.
* Workflow.

### Platform Modules

Provide governed platform capabilities:

* Identity and Access.
* Search.
* Administration.
* Observability.

### Shared Kernel

Contains only small, stable concepts shared across modules.

### Runtime Adapter

Connects the framework-independent application to Laravel and infrastructure services.

---

## Initial Modules

```text
Shared Kernel
Organization
Project
Application
Environment
Event
Alert
Incident
Knowledge
Agent
Plugin
Notification
Search
Identity and Access
Administration
Observability
Workflow
Laravel Runtime Adapter
```

The physical code structure should reflect these module boundaries.

---

## Module Ownership

Each authoritative Resource has exactly one owning module.

The owning module controls:

* Creation.
* mutation.
* lifecycle transitions.
* validation.
* persistence contract.
* domain events.
* authorization requirements.
* invariants.

Other modules may reference the Resource but must not modify its state directly.

---

## Authoritative State

Only the owning module may write authoritative state for its Resources.

Example:

```text
Alert Module owns Alert state.
Incident Module may reference an Alert.
Incident Module must not update Alert tables directly.
```

Cross-module updates occur through public application contracts or domain events.

---

## Public Module Contract

A module may expose:

```text
Commands
Queries
Domain Events
Application Events
Repository Interfaces
Policy Requirements
Read Models
```

Internal entities, services, tables, and implementation details are not public contracts.

---

## Commands

Commands request a state-changing operation.

Examples:

```text
CreateIncident
AcknowledgeAlert
PublishKnowledgeArticle
InstallPlugin
StartAgentExecution
```

Commands must:

* Express business intent.
* identify Organization context.
* validate authorization.
* preserve idempotency where required.
* execute through the owning module.

---

## Queries

Queries retrieve data without changing authoritative state.

Examples:

```text
GetIncident
ListAlerts
GetApplicationHealth
SearchKnowledge
```

Queries may use optimized read models but must preserve:

* Tenant isolation.
* authorization.
* field-level access.
* consistency expectations.

---

## Domain Events

Domain Events represent completed business facts.

Examples:

```text
AlertCreated
IncidentResolved
KnowledgePublished
PluginInstalled
WorkflowCompleted
```

Events must:

* Be immutable.
* identify the owning Organization.
* include relevant Resource identifiers.
* preserve correlation and causation.
* use stable names and Versions.

---

## Event Consumption

A module may consume events from another module to:

* update a Projection.
* trigger its own command.
* create a related Resource.
* schedule asynchronous work.
* notify an external system.

Consumers must not reinterpret another module’s authoritative state without referencing the source event or Resource.

---

## Direct Module Calls

Synchronous module calls are permitted when:

* Immediate consistency is required.
* the dependency direction is valid.
* the called contract is stable.
* failure must be returned to the caller.

Asynchronous events are preferred when immediate completion is not required.

---

## Dependency Rules

Module dependencies must be explicit and directional.

Allowed:

```text
Application Layer
→ another module's public application contract
```

Prohibited:

```text
Module A
→ Module B internal entity

Module A
→ Module B database table

Module A
→ Module B infrastructure repository implementation
```

Circular dependencies are prohibited.

---

## Dependency Direction

Preferred dependency direction:

```text
Runtime and Infrastructure
↓
Application Contracts
↓
Domain
↓
Shared Kernel
```

Domain code must not depend on:

* Laravel.
* HTTP.
* Queue drivers.
* database ORM details.
* Redis.
* external SDKs.
* provider-specific clients.

---

## Shared Kernel

The Shared Kernel may contain only stable cross-module concepts such as:

* Resource identifiers.
* Organization identifier.
* clock abstraction.
* domain event interface.
* common result or error primitives.
* pagination primitives.
* optimistic concurrency Version.
* correlation and causation identifiers.

The Shared Kernel must not become a general utilities directory.

---

## Cross-Module References

A module may reference another module’s Resource by identifier.

Example:

```text
Incident
├── organization_id
├── incident_id
└── source_alert_id
```

The referencing module must not duplicate the complete authoritative Resource.

Cross-module references must preserve Organization ownership.

---

## Database Boundaries

The initial deployment may use one PostgreSQL schema.

Logical ownership still applies:

* Every table belongs to one module.
* only the owning module writes its tables.
* cross-module foreign keys may be used when they preserve clear ownership.
* direct cross-module queries should be avoided.
* read Projections may combine data from multiple modules.

Shared database deployment does not imply shared data ownership.

---

## Repository Boundaries

Repository interfaces belong to the module whose Resources they persist.

Example:

```text
IncidentRepository
→ Incident Module
```

Other modules must not inject and use `IncidentRepository` to change Incident state.

They should invoke an Incident command instead.

---

## Authorization

Every protected module operation declares its required permission.

The owning module evaluates:

* Tenant Context.
* identity.
* permission.
* Resource scope.
* Resource state.
* authentication strength where required.

Modules must not assume authorization was already handled by controllers or callers.

---

## Tenant Isolation

Every tenant-owned command, query, event, job, and repository operation requires Organization context.

Modules must reject:

* Missing Organization context.
* cross-Organization Resource references.
* conflicting parent ownership.
* unscoped database access.
* cross-tenant cache or Search access.

---

## Transactions

A module owns the transaction for its authoritative state change.

The transaction may include:

* Resource changes.
* history records.
* Audit metadata where required.
* Outbox Records.

A module should not hold a transaction open while waiting for another module or external provider.

---

## Outbox Integration

State-changing operations that publish events must write Outbox Records in the same transaction as the authoritative change.

This ensures:

```text
State committed
⇔
Event eligible for publication
```

Consumers must tolerate duplicate event delivery.

---

## Module Services

A module may use:

### Domain Services

For business logic that does not naturally belong to one Entity or Value Object.

### Application Services

For orchestration of commands, repositories, Policies, transactions, and event publication.

### Infrastructure Services

For technical adapters such as persistence, providers, Queues, and storage.

These responsibilities must remain separate.

---

## Module Internal Structure

A typical module may contain:

```text
ModuleName/
├── Domain/
├── Application/
├── Infrastructure/
└── Contracts/
```

Suggested responsibilities:

```text
Domain
- Entities
- Value Objects
- Domain Services
- Domain Events
- Repository Interfaces

Application
- Commands
- Queries
- Handlers
- Policies
- DTOs

Infrastructure
- Repository Implementations
- Provider Adapters
- Projection Writers
- Framework Integration

Contracts
- Explicit public contracts when needed
```

Exact folders may evolve, but dependency direction must remain intact.

---

## Framework Independence

Domain and application rules should remain independent from Laravel.

Laravel is responsible for:

* HTTP routing.
* request validation adapters.
* authentication integration.
* Queue execution.
* scheduler integration.
* dependency injection.
* infrastructure bindings.
* serialization.
* framework lifecycle.

The Laravel Runtime Adapter connects these concerns to module contracts.

---

## Module Extraction

A module may become a separate service only when justified by:

* Distinct scaling requirements.
* independent deployment needs.
* strong data ownership.
* stable integration contracts.
* operational ownership.
* failure-isolation value.

Service extraction must not be used to fix unclear module boundaries.

---

## Testing Requirements

Each module should include tests for:

* Domain invariants.
* command behavior.
* query authorization.
* Tenant Context.
* optimistic concurrency.
* event publication.
* idempotency.
* cross-module contract behavior.
* prohibited dependency rules.

Architecture tests should prevent invalid module imports.

---

## Module Documentation Contract

Each module document should define only:

* Purpose.
* owned Resources.
* responsibilities.
* commands and queries.
* events published and consumed.
* dependencies.
* authorization boundary.
* prohibited responsibilities.
* related documents.

Detailed schemas, endpoints, security controls, and deployment behavior remain in their existing documentation.

---

## Prohibited Practices

The following are prohibited:

* A shared global Services directory for business logic.
* direct writes to another module’s tables.
* importing another module’s internal Entities.
* circular module dependencies.
* domain code depending on Laravel.
* using database relationships as the only module contract.
* duplicating authoritative Resources across modules.
* bypassing module commands for convenience.
* placing unstable business concepts in the Shared Kernel.
* creating microservices before module ownership is clear.

---

## Business Rules

* Every authoritative Resource has one owning module.
* only the owning module may mutate its Resource state.
* module integration uses explicit contracts or events.
* module dependencies are directional and non-circular.
* domain code remains framework-independent.
* shared database deployment does not weaken logical ownership.
* Tenant Context and authorization apply inside every module.
* state changes and Outbox Records are committed atomically.
* module documents remain concise and reference existing detailed documentation.
* service extraction occurs only after strong module boundaries exist.

---

## Related Documents

* `docs/modules/01-shared-kernel.md`
* `docs/modules/14-identity-access-module.md`
* `docs/modules/18-laravel-runtime-adapter.md`
* `docs/architecture/02-domain-architecture.md`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/05-event-model.md`
* `docs/adr/ADR-005-domain-driven-design.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-009-core-is-framework-agnostic.md`
* `docs/adr/ADR-010-component-based-architecture.md`
