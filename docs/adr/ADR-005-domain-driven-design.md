# ADR-005 — Domain-Driven Design

**Status:** Accepted

**Date:** August 1, 2026

**Decision Makers:** Founders

---

# Context

Project Sentinel models complex operational processes, including event processing, incident management, operational knowledge, automation, plugins, agents, and multi-tenant security.

These capabilities contain substantial business rules that should remain independent of frameworks, infrastructure, databases, and user interface technologies.

A traditional layered architecture centered around controllers, services, and repositories would couple business logic to technical concerns and make long-term evolution increasingly difficult.

---

# Decision

Project Sentinel adopts Domain-Driven Design (DDD) as its primary architectural approach.

Business capabilities are modeled as independent Components centered around the business domain.

The Domain Layer represents the authoritative implementation of business rules.

Infrastructure, frameworks, persistence, messaging, and user interfaces support the Domain rather than defining it.

---

# Architectural Structure

Each Component is organized around business responsibilities.

```text
Component

├── Domain
├── Application
├── Infrastructure
└── Presentation
```

Each layer has a clearly defined responsibility.

Dependencies always point toward the Domain.

---

# Domain Layer

The Domain Layer contains business concepts and rules.

Examples include:

- Entities
- Value Objects
- Domain Services
- Domain Events
- Aggregates
- Business Policies
- Domain Exceptions

The Domain Layer has no knowledge of:

- Databases
- Frameworks
- HTTP
- Message Brokers
- User Interfaces
- External APIs

This isolation preserves business integrity.

---

# Application Layer

The Application Layer coordinates business operations.

Responsibilities include:

- Executing use cases
- Managing transactions
- Coordinating Components
- Publishing Domain Events
- Calling infrastructure through interfaces

Application Services orchestrate business workflows.

They do not contain business rules.

---

# Infrastructure Layer

The Infrastructure Layer provides technical implementations.

Examples include:

- Database persistence
- Message Bus
- Search
- Email
- Object Storage
- External integrations
- SDK adapters

Infrastructure depends on Domain abstractions.

The Domain never depends on Infrastructure.

---

# Presentation Layer

The Presentation Layer exposes business capabilities.

Examples include:

- REST APIs
- GraphQL APIs
- CLI commands
- Background workers
- Administrative interfaces

Presentation translates external requests into Application use cases.

Business decisions remain inside the Domain.

---

# Component Boundaries

Business capabilities are implemented as independent Components.

Examples include:

- Organization Management
- Application Management
- Event Processing
- Alert Management
- Incident Management
- Knowledge Management
- Plugin Platform
- Agent Platform

Each Component owns its business model and invariants.

Communication occurs through Application Services and Domain Events.

---

# Dependency Rules

The architecture follows strict dependency rules.

- Domain depends on nothing.
- Application depends on Domain.
- Infrastructure depends on Domain.
- Presentation depends on Application.

Dependencies between Components occur only through published contracts.

Circular dependencies are prohibited.

---

# Rationale

Domain-Driven Design provides:

- Clear business boundaries.
- High maintainability.
- Testable business rules.
- Framework independence.
- Technology independence.
- Long-term architectural stability.
- Independent evolution of Components.

The architecture reflects business concepts rather than implementation technologies.

---

# Consequences

Positive consequences include:

- Business logic remains isolated.
- Framework migration becomes practical.
- Components evolve independently.
- Testing focuses on business behavior.
- Infrastructure can be replaced with minimal impact.

Trade-offs include:

- More architectural structure.
- Additional abstractions.
- Greater initial design effort.

These trade-offs are accepted because they significantly improve long-term maintainability.

---

# Alternatives Considered

## Traditional Layered Architecture

Organize the application around Controllers, Services, and Repositories.

Rejected because technical layers become the primary architectural structure rather than business capabilities.

---

## Framework-Centric Architecture

Adopt the conventions of a specific framework as the application architecture.

Rejected because the framework should implement the architecture, not define it.

Frameworks may change over time.

Business rules should not.

---

## Microservices from the Beginning

Split every business capability into an independent service.

Rejected because deployment boundaries should evolve from operational requirements rather than be imposed prematurely.

The Component Architecture provides sufficient modularity while preserving operational simplicity.

---

# Architectural Principles

The Domain-Driven Design architecture follows these principles.

- Business rules belong in the Domain.
- Components model business capabilities.
- Dependencies point toward the Domain.
- Frameworks implement architecture rather than define it.
- Infrastructure is replaceable.
- Business concepts remain independent of technology.

---

# Related Documents

- `architecture/02-domain-architecture.md`
- `architecture/03-component-architecture.md`
- `architecture/04-data-flow.md`
- `architecture/05-event-model.md`

---

# Related ADRs

- `ADR-002-plugin-architecture.md`
- `ADR-004-operational-processing-model.md`
- `ADR-009-core-is-framework-agnostic.md`
- `ADR-010-component-based-architecture.md`