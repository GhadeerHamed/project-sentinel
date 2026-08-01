# ADR-009 — Core is Framework Agnostic

**Status:** Accepted

**Date:** August 1, 2026

**Decision Makers:** Founders

---

# Context

Modern application frameworks provide valuable capabilities, including:

- Dependency Injection
- Routing
- HTTP handling
- Database access
- Background jobs
- Authentication
- Configuration
- Testing support

While these capabilities accelerate development, they should not determine the platform architecture.

Frameworks evolve, become deprecated, or are replaced over time.

Business capabilities typically outlive the technology used to implement them.

Project Sentinel requires an architecture whose Core business model remains stable regardless of implementation framework.

---

# Decision

Project Sentinel adopts a framework-agnostic Core Architecture.

Business concepts, domain rules, application services, and architectural boundaries shall not depend on any application framework.

Frameworks are treated as implementation technologies that host and support the Core.

The architecture defines how the system is organized.

Frameworks provide runtime capabilities.

---

# Core Responsibilities

The Core owns:

- Domain Model
- Application Services
- Business Rules
- Domain Events
- Component Contracts
- Plugin Contracts
- Agent Contracts
- Security Model

These responsibilities remain independent of framework-specific APIs and conventions.

---

# Framework Responsibilities

Application frameworks provide technical capabilities, including:

- HTTP request handling
- Routing
- Dependency Injection
- Configuration
- Database integration
- Command execution
- Scheduling
- Background processing

Framework responsibilities are implementation concerns.

They do not define business architecture.

---

# Dependency Direction

Dependencies always point toward the Core.

```text
Presentation

↓

Application

↓

Domain

↑

Infrastructure
```

Framework-specific code belongs to the Presentation and Infrastructure layers.

The Domain Layer has no knowledge of the framework.

---

# Port and Adapter Model

External technologies communicate with the Core through well-defined interfaces.

Examples include:

- REST APIs
- CLI Commands
- Message Consumers
- Database Repositories
- Object Storage
- External Integrations

The Core depends on contracts rather than concrete implementations.

---

# Framework Independence

The following implementation technologies may change without affecting the Core architecture:

- Laravel
- Symfony
- ASP.NET
- Spring Boot
- NestJS
- Go
- Rust
- Future technologies

Framework replacement should affect implementation rather than business behavior.

---

# Rationale

Framework independence provides:

- Long-term architectural stability.
- Easier technology migration.
- Improved testability.
- Better separation of concerns.
- Reduced vendor lock-in.
- Clear ownership of business logic.

Business capabilities evolve independently of implementation technologies.

---

# Consequences

Positive consequences include:

- Framework upgrades have limited architectural impact.
- Business rules remain portable.
- Components become easier to test.
- Infrastructure implementations remain replaceable.
- Technology decisions remain flexible.

Trade-offs include:

- Additional abstraction layers.
- More interface definitions.
- Slightly increased implementation effort.

These trade-offs are accepted because they protect the longevity of the platform.

---

# Alternatives Considered

## Framework-Centric Architecture

Organize the application according to framework conventions.

Rejected because business architecture becomes tightly coupled to implementation technology.

---

## Direct Framework Dependencies

Allow business logic to depend directly on framework services.

Rejected because replacing the framework would require extensive changes to business logic.

---

## Multiple Framework Implementations

Maintain separate implementations for multiple frameworks.

Rejected because the architecture should define a single business model implemented consistently regardless of framework.

---

# Architectural Principles

The Framework-Agnostic Core follows these principles.

- The architecture owns the business model.
- Frameworks implement, but do not define, the architecture.
- Business logic depends on abstractions.
- Infrastructure remains replaceable.
- Technology choices are implementation decisions.
- The Core remains portable.

---

# Related Documents

- `architecture/02-domain-architecture.md`
- `architecture/03-component-architecture.md`
- `architecture/08-security-architecture.md`

---

# Related ADRs

- `ADR-005-domain-driven-design.md`
- `ADR-007-event-driven-architecture.md`
- `ADR-010-component-based-architecture.md`