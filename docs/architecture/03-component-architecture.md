# Component Architecture

**Version:** 0.1  
**Status:** Draft (In Progress)  
**Document:** `architecture/03-component-architecture.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 31, 2026

---

# Purpose

This document defines the internal component architecture of Project Sentinel.

It identifies the business components that compose the application, their responsibilities, ownership boundaries, dependencies, and communication rules.

The objective is to maintain a modular architecture that supports long-term evolution while avoiding tight coupling between business capabilities.

This document focuses on logical business components rather than implementation details.

---

# Goals

The Component Architecture aims to:

- Define clear ownership boundaries.
- Minimize coupling between modules.
- Maximize cohesion within modules.
- Enable independent evolution of business capabilities.
- Support a modular monolith architecture.
- Preserve a future path toward service extraction if required.

---

# Architectural Principles

The component architecture follows these principles.

## Business Capability First

Components are organized around business capabilities rather than technical layers.

Examples include:

- Incident Management
- Signal Processing
- Knowledge Management

rather than:

- Services
- Controllers
- Models

---

## High Cohesion

Each component owns one primary business responsibility.

Business rules belonging to a capability should remain inside the same component.

---

## Loose Coupling

Components interact only through explicit contracts.

Implementation details remain private to the owning component.

---

## Explicit Ownership

Every business object has exactly one owning component.

No component may assume ownership of another component's business rules.

---

## Stable Public Interfaces

Components expose only the interfaces required by consumers.

Internal implementation details remain hidden.

---

## Independent Evolution

Components should evolve independently whenever practical.

Changes within one component should have minimal impact on others.

---

# Component Overview

The Sentinel application consists of the following business components.

| Component | Primary Responsibility |
|------------|------------------------|
| Identity | Authentication and Authorization |
| Organization | Tenant Management |
| Project | Project Management |
| Application Management | Registered Applications |
| Event Intake | Event Reception |
| Event Processing | Event Normalization |
| Signal Engine | Signal Generation |
| Correlation Engine | Signal Correlation |
| Alert Engine | Alert Generation |
| Incident Engine | Incident Lifecycle |
| Investigation Engine | Incident Investigation |
| Knowledge Engine | Organizational Knowledge |
| Notification Engine | User Notifications |
| Plugin Platform | Runtime Extensions |
| Administration | Platform Administration |

Each component owns its business logic, persistence contracts, and public interfaces.

---

# Component Boundaries

Every component forms a logical boundary within the application.

Each boundary contains:

- Business rules
- Domain model
- Application services
- Public interfaces
- Infrastructure adapters
- Tests

No business logic shall exist outside a component boundary.

---

# Internal Layering

Each component follows the same internal structure.

```text
<Component>

├── Domain
│
├── Application
│
├── Infrastructure
│
├── Presentation
│
└── Tests
```

Responsibilities:

| Layer | Responsibility |
|---------|---------------|
| Domain | Business rules |
| Application | Use cases and orchestration |
| Infrastructure | Technical implementations |
| Presentation | HTTP, CLI, API adapters |
| Tests | Component tests |

This structure is applied consistently across every business component.

---

# Component Ownership

Every business entity belongs to exactly one component.

Examples include:

| Entity | Owning Component |
|---------|------------------|
| Organization | Organization |
| Project | Project |
| Application | Application Management |
| Event | Event Intake |
| Signal | Signal Engine |
| Alert | Alert Engine |
| Incident | Incident Engine |
| Investigation | Investigation Engine |
| Knowledge Entry | Knowledge Engine |

Ownership determines where business rules are implemented.

No component may directly modify another component's business entities.

---

# Component Independence

Business components shall remain independent of:

- Laravel
- PostgreSQL
- Redis
- HTTP
- Docker

Frameworks support components.

Components do not depend on frameworks.

---

# Component Relationships

Components collaborate to implement business workflows while preserving clear ownership boundaries.

A component may depend only on the public contracts of another component.

Direct access to another component's internal implementation is prohibited.

---

# High-Level Dependency Graph

The following diagram illustrates the primary relationships between components.

```text
                  Identity
                      │
                      ▼
               Organization
                      │
                      ▼
                  Project
                      │
                      ▼
          Application Management
                      │
                      ▼
                Event Intake
                      │
                      ▼
             Event Processing
                      │
                      ▼
               Signal Engine
                      │
                      ▼
            Correlation Engine
                      │
                      ▼
                Alert Engine
                      │
                      ▼
              Incident Engine
                      │
                      ▼
           Investigation Engine
                      │
                      ▼
             Knowledge Engine
```

Supporting components are available across the platform.

```text
                 Notification Engine

                         ▲

                    Domain Events

                         ▼

                 Plugin Platform
```

---

# Dependency Rules

The following dependency rules apply throughout the platform.

## DR-001 — Explicit Dependencies

A component may depend only on another component's published interfaces.

Internal classes are private implementation details.

---

## DR-002 — No Circular Dependencies

Circular dependencies are prohibited.

Every dependency graph shall remain acyclic.

---

## DR-003 — Ownership Enforcement

Only the owning component may modify its business entities.

Other components must communicate through:

- Application services
- Public interfaces
- Domain events

---

## DR-004 — Shared Kernel

Only generic cross-cutting functionality belongs in the Shared Kernel.

Examples include:

- Identifiers
- Base domain abstractions
- Common exceptions
- Time abstractions
- Value object interfaces

Business rules must never migrate into the Shared Kernel.

---

## DR-005 — Infrastructure Isolation

Infrastructure implementations remain private to the owning component.

Consumers depend only on contracts.

---

## DR-006 — Event-Based Collaboration

Whenever business state changes may affect multiple components, communication should occur through domain events.

Example:

```text
Incident Created

↓

Domain Event

↓

Notification Engine

Knowledge Engine

Audit Logging

Future Automation
```

The Incident Engine remains unaware of its consumers.

---

# Component Visibility

Every component exposes two types of interfaces.

## Public Interfaces

Accessible to other components.

Examples:

- Application services
- Query services
- Event contracts

---

## Internal Implementation

Accessible only within the owning component.

Examples:

- Domain entities
- Repositories
- Infrastructure adapters
- Internal policies
- Persistence models

Implementation details shall not leak across component boundaries.

---

# Allowed Communication Patterns

The following interactions are permitted.

| Pattern | Allowed |
|----------|---------|
| Component → Public Interface | ✅ |
| Component → Domain Event | ✅ |
| Component → Shared Kernel | ✅ |
| Component → Internal Classes of Another Component | ❌ |
| Component → Another Component's Database Tables | ❌ |
| Component → Another Component's Infrastructure | ❌ |

---

# Component Lifecycle

Every business request follows the same lifecycle.

```text
Presentation

↓

Application Service

↓

Domain Logic

↓

Repository

↓

Persistence

↓

Domain Events
```

This lifecycle shall remain consistent across all components.

---

# Design Objective

The Component Architecture is designed so that business capabilities can evolve independently while preserving a cohesive, maintainable, and testable codebase.

Architectural consistency takes precedence over implementation convenience.

---

# Internal Component Structure

Every business component follows the same internal architecture.

Consistent internal organization improves discoverability, maintainability, testing, and future extraction into independent packages or services.

---

# Standard Component Layout

```text
Component/

├── Domain/
│   ├── Entities/
│   ├── ValueObjects/
│   ├── Aggregates/
│   ├── Events/
│   ├── Policies/
│   ├── Specifications/
│   ├── Repositories/
│   └── Exceptions/
│
├── Application/
│   ├── Commands/
│   ├── Queries/
│   ├── Handlers/
│   ├── DTOs/
│   ├── Services/
│   └── Contracts/
│
├── Infrastructure/
│   ├── Persistence/
│   ├── Queue/
│   ├── Cache/
│   ├── HTTP/
│   ├── Events/
│   └── Providers/
│
├── Presentation/
│   ├── API/
│   ├── Web/
│   ├── CLI/
│   └── Requests/
│
└── Tests/
    ├── Unit/
    ├── Integration/
    └── Feature/
```

---

# Domain Layer

The Domain layer contains the business model.

Responsibilities include:

- Business entities
- Aggregate roots
- Value objects
- Domain services
- Business policies
- Specifications
- Domain events
- Repository contracts

The Domain layer contains no framework-specific code.

---

# Application Layer

The Application layer orchestrates business use cases.

Responsibilities include:

- Commands
- Queries
- Use case handlers
- Transactions
- DTOs
- Application services
- Authorization orchestration

The Application layer coordinates work but should contain very little business logic.

---

# Infrastructure Layer

The Infrastructure layer implements technical concerns.

Examples include:

- Database repositories
- Redis adapters
- Queue implementations
- External APIs
- Storage
- Email
- Logging

Infrastructure depends on the Domain.

The Domain never depends on Infrastructure.

---

# Presentation Layer

The Presentation layer exposes the component to external consumers.

Examples include:

- REST API endpoints
- Web controllers
- CLI commands
- WebSocket endpoints
- Request validation

Presentation translates external requests into application use cases.

---

# Tests

Each component owns its own test suite.

Tests should remain close to the code they verify.

The minimum test categories are:

- Unit tests
- Integration tests
- Feature tests

Component tests should not depend on unrelated business components.

---

# Layer Dependencies

Dependencies always point inward.

```text
Presentation

↓

Application

↓

Domain

↑

Infrastructure
```

The Domain layer is the architectural center of every component.

---

# Design Rules

The following rules apply to every component.

## CR-001 — Domain Independence

The Domain layer shall remain independent of frameworks and infrastructure.

---

## CR-002 — Infrastructure Implements Contracts

Infrastructure implements interfaces defined by the Domain or Application layers.

---

## CR-003 — Presentation Is Thin

Presentation layers should perform translation, validation, and authorization only.

Business rules belong elsewhere.

---

## CR-004 — Explicit Use Cases

Every externally visible business action should be represented as an explicit application use case.

Examples include:

- Create Incident
- Resolve Incident
- Register Application
- Create Alert

---

## CR-005 — Local Testing

Each component should be testable in isolation.

Mocking should occur only at component boundaries.

---

# Design Objective

A developer joining the project should immediately understand where new functionality belongs.

Consistency is considered a core architectural feature of Project Sentinel.

---

# Component Contracts

Components expose functionality through stable public contracts.

A contract defines what a component offers to the rest of the platform without exposing its internal implementation.

Public contracts remain stable even when internal implementations evolve.

---

# Types of Contracts

Every component may expose one or more of the following contract types.

## Application Services

Expose business use cases.

Examples include:

- RegisterApplication
- CreateIncident
- ResolveIncident
- CreateAlert

Application Services represent the primary entry point into a component.

---

## Query Services

Expose read-only operations.

Examples include:

- FindIncident
- ListApplications
- SearchKnowledge

Query Services shall not modify business state.

---

## Domain Events

Components publish Domain Events whenever significant business state changes occur.

Examples include:

- ApplicationRegistered
- EventReceived
- SignalCreated
- AlertCreated
- IncidentCreated
- InvestigationStarted
- InvestigationResolved

Domain Events enable loose coupling between components.

---

## Extension Points

Some components intentionally expose extension points for plugins.

Examples include:

- Event processors
- Notification channels
- Authentication providers
- Runtime integrations
- Alert enrichers
- Investigation enrichers

Extension points shall be implemented through well-defined interfaces.

---

# Component Contracts

Every public contract shall satisfy the following principles.

## Stable

Contracts should remain stable across releases whenever practical.

---

## Explicit

Contracts shall clearly define:

- Inputs
- Outputs
- Expected behavior
- Failure conditions

---

## Versioned

Breaking changes require contract versioning.

---

## Technology Independent

Contracts describe business behavior rather than implementation details.

---

# Component Evolution

Components are expected to evolve independently.

Evolution should preserve compatibility whenever possible.

Examples include:

- Internal refactoring
- Performance improvements
- Infrastructure changes
- Database optimizations

These changes should not affect consumers if public contracts remain unchanged.

---

# Future Service Extraction

Although Sentinel is initially deployed as a Modular Monolith, component boundaries are designed to support future extraction into independent services.

A component is considered a candidate for extraction only when justified by measurable operational requirements.

Possible candidates include:

- Notification Engine
- Event Processing
- Knowledge Engine
- Plugin Platform

Service extraction shall preserve existing public contracts whenever possible.

---

# Architectural Constraints

The following constraints apply to all components.

## AC-001 — One Owner

Every business capability has exactly one owning component.

---

## AC-002 — Public Contracts Only

Components interact exclusively through published contracts or Domain Events.

---

## AC-003 — No Shared Business Logic

Business rules shall never be duplicated across multiple components.

---

## AC-004 — Backward Compatibility

Public contracts should remain backward compatible whenever practical.

Breaking changes shall be intentional, documented, and versioned.

---

## AC-005 — Framework Independence

Business logic shall remain independent of framework-specific implementations.

Frameworks may change without requiring changes to business rules.

---

# Architecture Quality Attributes

The Component Architecture is designed to maximize:

- Maintainability
- Modularity
- Testability
- Extensibility
- Scalability
- Evolvability
- Observability
- Developer Experience

These quality attributes guide all future architectural decisions.

---

# Summary

The Component Architecture establishes the internal organization of Project Sentinel.

Each component owns a distinct business capability, communicates through explicit contracts, and evolves independently within a Modular Monolith.

This architecture enables rapid development today while preserving a clear path toward future service extraction if operational requirements demand it.

---

# Related Documents

- `architecture/00-architecture-overview.md`
- `architecture/01-system-context.md`
- `architecture/02-runtime-architecture.md`
- `architecture/04-data-flow.md`
- `architecture/05-event-model.md`
- `architecture/06-plugin-system.md`
- `docs/07-domain-model.md`

---

# Revision History

| Version | Date | Author | Notes |
|----------|------------|---------|-------------------------------------------|
| 0.1 | July 31, 2026 | Founders | Initial Component Architecture |