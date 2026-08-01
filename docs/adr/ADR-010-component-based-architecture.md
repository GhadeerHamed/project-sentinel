# ADR-010 — Component-Based Architecture

**Status:** Accepted

**Date:** August 1, 2026

**Decision Makers:** Founders

---

# Context

Project Sentinel implements multiple business capabilities, including Application Management, Event Processing, Alert Management, Incident Management, Knowledge Management, Plugins, Agents, Security, and future platform extensions.

As the platform grows, organizing code by technical layers becomes increasingly difficult to maintain.

Changes to a single business capability become scattered across multiple directories, making ownership unclear and increasing coupling.

The platform requires an architectural structure that aligns implementation with business responsibilities.

---

# Decision

Project Sentinel adopts a Component-Based Architecture.

Each Component represents a single business capability.

A Component owns its domain model, application services, infrastructure adapters, and presentation interfaces.

Components collaborate through published contracts and Domain Events rather than direct implementation dependencies.

---

# Component Structure

Each Component follows a consistent internal structure.

```text
Component

├── Domain
├── Application
├── Infrastructure
└── Presentation
```

This structure is applied consistently across the platform.

---

# Component Ownership

Each Component owns its business concepts, invariants, and lifecycle.

Typical Components include:

- Organization Management
- Project Management
- Application Management
- Environment Management
- Event Processing
- Alert Management
- Incident Management
- Knowledge Management
- Notification Platform
- Plugin Platform
- Agent Platform
- Identity and Access Management

Ownership is explicit and exclusive.

---

# Responsibilities

A Component is responsible for:

- Business rules
- Domain model
- Use cases
- Domain Events
- Persistence contracts
- External interfaces
- Internal consistency

A Component does not own the responsibilities of another Component.

---

# Component Communication

Components communicate using well-defined architectural mechanisms.

Preferred communication methods include:

- Application Services
- Domain Events
- Published contracts

Direct access to another Component's internal implementation is prohibited.

---

# Shared Kernel

A small Shared Kernel may exist for concepts used consistently across multiple Components.

Examples include:

- Organization identifiers
- User identifiers
- Time abstractions
- Domain Event interfaces
- Result types
- Common value objects

The Shared Kernel shall remain intentionally small.

Business logic shall not migrate into the Shared Kernel.

If a shared package begins accumulating business responsibilities, it should be refactored into a dedicated Component.

---

# Component Independence

Components should evolve independently whenever practical.

Changes inside one Component should have minimal impact on other Components.

New Components should integrate through existing architectural contracts rather than modifying unrelated Components.

---

# Internal Consistency

Within a Component:

- Business terminology is consistent.
- Business rules remain localized.
- Domain objects remain cohesive.
- Dependencies point toward the Domain.

This improves maintainability and reduces cognitive overhead.

---

# Scalability

Component boundaries remain stable regardless of deployment topology.

A Component may execute:

- Within a modular monolith
- As an independently scalable workload
- As a future distributed service

Deployment decisions do not redefine business ownership.

---

# Rationale

Component-Based Architecture provides:

- Clear ownership.
- High cohesion.
- Low coupling.
- Easier maintenance.
- Independent evolution.
- Better scalability.
- Improved developer experience.

The architecture reflects business capabilities rather than technical implementation.

---

# Consequences

Positive consequences include:

- Business capabilities remain easy to locate.
- Teams can work on Components independently.
- Components become natural testing boundaries.
- Future service extraction becomes practical if required.
- New capabilities integrate predictably.

Trade-offs include:

- More architectural discipline.
- Clear ownership boundaries must be maintained.
- Shared utilities require careful governance.

These trade-offs are accepted because they improve long-term maintainability and architectural clarity.

---

# Alternatives Considered

## Layer-Based Architecture

Organize the project into global Controllers, Services, Repositories, and Models.

Rejected because business responsibilities become fragmented across technical layers.

---

## Package-by-Technology

Group code according to implementation technology.

Rejected because implementation concerns become more visible than business capabilities.

---

## Microservices from the Beginning

Implement every Component as an independent service.

Rejected because deployment boundaries should evolve based on operational needs rather than being fixed at the start of the project.

The Component Architecture supports future distribution without introducing unnecessary complexity.

---

# Architectural Principles

The Component-Based Architecture follows these principles.

- Components own business capabilities.
- Components communicate through contracts.
- Components remain internally cohesive.
- Components evolve independently.
- Business ownership is explicit.
- Deployment topology does not define Component boundaries.

---

# Related Documents

- `architecture/02-domain-architecture.md`
- `architecture/03-component-architecture.md`
- `architecture/09-scaling-strategy.md`

---

# Related ADRs

- `ADR-005-domain-driven-design.md`
- `ADR-007-event-driven-architecture.md`
- `ADR-009-core-is-framework-agnostic.md`