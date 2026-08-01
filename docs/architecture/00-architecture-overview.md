# Architecture Overview

**Version:** 0.1  
**Status:** Draft (In Progress)  
**Document:** `architecture/00-architecture-overview.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 31, 2026

---

# Purpose

This document defines the high-level software architecture of Project Sentinel.

It establishes the architectural vision, guiding principles, and major design decisions that govern the implementation and evolution of the platform.

The purpose of this document is not to describe implementation details, but to define the architectural philosophy that all future technical decisions must follow.

Detailed designs for individual components are documented separately.

---

# Architectural Vision

Project Sentinel is designed as an operational intelligence platform capable of evolving from a single-product MVP into a scalable platform supporting thousands of organizations, millions of operational events, and multiple application runtimes.

The architecture prioritizes:

- Simplicity before complexity.
- Evolution over premature optimization.
- Extensibility without modification.
- Operational reliability.
- Clear domain boundaries.
- Long-term maintainability.

The architecture shall allow Sentinel to evolve over multiple years without requiring fundamental redesign.

---

# Architecture Goals

The architecture shall satisfy the following primary goals.

## AG-001 — Modularity

Business capabilities shall be implemented as independent modules with clearly defined responsibilities.

---

## AG-002 — Extensibility

New runtimes, integrations, and operational capabilities shall be added through plugins rather than modifications to the core application.

---

## AG-003 — Maintainability

Business logic shall remain isolated from infrastructure, frameworks, and third-party services.

---

## AG-004 — Scalability

The architecture shall support increasing workloads through horizontal scaling wherever practical.

---

## AG-005 — Security

Security shall be considered a foundational architectural concern rather than an implementation detail.

---

## AG-006 — Observability

The platform shall provide sufficient internal visibility to diagnose operational issues affecting Sentinel itself.

---

## AG-007 — Technology Independence

The domain model shall remain independent of implementation technologies.

Infrastructure may evolve without changing business concepts.

---

# Architectural Principles

The following principles govern all architectural decisions.

---

## Domain First

The business domain defines the architecture.

Frameworks, databases, queues, and infrastructure exist to support the domain—not the other way around.

---

## Clean Architecture

Business rules shall remain independent of:

- Laravel
- PostgreSQL
- Redis
- Docker
- HTTP
- User Interface

Infrastructure components depend on the domain.

The domain never depends on infrastructure.

---

## Modular Monolith First

Sentinel will initially be implemented as a modular monolith.

Business capabilities will be separated into independent modules while remaining within a single deployable application.

This approach minimizes operational complexity while preserving a clear path toward future service decomposition if justified.

---

## Event-Driven Processing

Operational information shall flow through asynchronous processing pipelines whenever appropriate.

Business capabilities communicate through domain events rather than direct coupling whenever practical.

---

## API-First Design

Every major business capability shall be exposed through well-defined APIs.

Internal application layers should follow the same contracts whenever practical.

---

## Plugin-Based Extensibility

Support for new runtimes, integrations, and operational capabilities shall be introduced through plugins.

The core platform shall remain stable while allowing external extensions.

---

## Explicit Dependencies

Dependencies between modules shall be intentional, documented, and minimized.

Circular dependencies are prohibited.

---

## Evolutionary Architecture

Architectural decisions should preserve future flexibility.

The architecture should evolve incrementally rather than through disruptive redesign.

---

# Architectural Drivers

The architecture is primarily influenced by the following drivers.

## Business Drivers

- Fast MVP delivery.
- Sustainable maintenance.
- Low operational cost.
- Commercial scalability.
- Multi-tenancy.
- Cloud and self-hosted support.

---

## Technical Drivers

- High operational reliability.
- Extensible runtime support.
- Event processing performance.
- Plugin ecosystem.
- API stability.
- Testability.

---

## Organizational Drivers

The architecture should support future growth of both the engineering team and the product without requiring major restructuring.

---

# Architectural Constraints

The following constraints guide architectural decisions.

## Technology Constraints

- PHP
- Laravel
- PostgreSQL
- Redis
- Docker

These technologies are implementation choices rather than architectural dependencies.

Equivalent technologies may be adopted in the future without changing the domain architecture.

---

## Infrastructure Constraints

The initial deployment shall prioritize simplicity.

Operational complexity shall increase only when justified by measurable business needs.

---

## Budget Constraints

The architecture shall prioritize open-source technologies.

Managed cloud services should be introduced only when they provide clear operational or commercial advantages.

---

# Architecture Decision Process

Every significant architectural decision shall:

- Identify the problem.
- Evaluate alternatives.
- Document trade-offs.
- Record the final decision.
- Be stored as an Architecture Decision Record (ADR).

Architectural decisions are intended to remain stable over time.

Changes should occur only when justified by measurable improvements or evolving business requirements.

---

# Related Documents

- `docs/07-domain-model.md`
- `docs/08-software-requirements.md`
- `ADR/ADR-001-product-positioning.md`
- `ADR/ADR-004-operational-processing-model.md`
- `ADR/ADR-005-domain-driven-design.md`

---

# High-Level Architecture

Project Sentinel is organized as a collection of logical architectural layers.

Each layer has a clearly defined responsibility and communicates only through well-defined interfaces.

The architecture emphasizes separation of concerns, maintainability, and long-term evolution.

---

## Architectural Layers

```text
                    Users
                      │
                      ▼
              Presentation Layer
                      │
                      ▼
              Application Layer
                      │
                      ▼
                 Domain Layer
                      │
                      ▼
             Infrastructure Layer
                      │
                      ▼
          External Systems & Storage
```

Each layer depends only on layers below it.

Business rules never depend on infrastructure.

---

# Layer Responsibilities

## Presentation Layer

The Presentation Layer exposes Sentinel to users and external systems.

Responsibilities include:

- Web Dashboard
- REST API
- WebSocket API
- CLI Commands
- Plugin Endpoints

This layer contains no business logic.

Its responsibility is translating requests into application use cases.

---

## Application Layer

The Application Layer coordinates business use cases.

Responsibilities include:

- Executing use cases
- Transaction management
- Authorization
- Validation
- Orchestrating workflows

The Application Layer contains application-specific behavior but no business rules.

---

## Domain Layer

The Domain Layer is the heart of Sentinel.

It contains:

- Entities
- Value Objects
- Aggregates
- Domain Services
- Domain Events
- Business Rules
- Repository Contracts

The Domain Layer is completely independent of Laravel and any external technology.

---

## Infrastructure Layer

The Infrastructure Layer provides technical capabilities required by the application.

Examples include:

- PostgreSQL
- Redis
- Queue Processing
- Email Delivery
- Plugin Loading
- File Storage
- HTTP Clients
- Authentication Providers

Infrastructure implements interfaces defined by the Domain and Application layers.

---

# Core Architectural Modules

The system is organized into logical modules.

```text
Organization

Project

Application

Event Intake

Signal Engine

Correlation Engine

Alert Management

Incident Management

Investigation Workspace

Knowledge Repository

Notification Service

Plugin Platform

Administration
```

Each module owns its own business logic and exposes only the interfaces required by other modules.

---

# Module Communication

Modules should communicate using one of the following mechanisms.

## Direct Invocation

Used when an immediate response is required.

Examples:

- Creating an Organization
- Registering an Application
- Updating Investigation Notes

---

## Domain Events

Used when business activities trigger additional processing.

Examples:

```text
IncidentCreated

↓

Notification Service

↓

Knowledge Service

↓

Audit Logging
```

Modules publish events without knowledge of who consumes them.

---

## Asynchronous Processing

Long-running or resource-intensive operations should execute asynchronously.

Examples include:

- Event normalization
- Signal correlation
- Notification delivery
- Plugin execution
- Knowledge indexing

---

# Dependency Rules

The following dependency rules apply throughout the system.

## Allowed

```text
Presentation

↓

Application

↓

Domain
```

```text
Infrastructure

implements

Domain Contracts
```

---

## Prohibited

- Domain depending on Laravel.
- Domain depending on PostgreSQL.
- Domain depending on Redis.
- Modules directly accessing each other's persistence layer.
- Circular dependencies.
- Business logic inside controllers.
- Business logic inside infrastructure components.

---

# Cross-Cutting Concerns

Certain capabilities apply across all modules.

These include:

- Authentication
- Authorization
- Logging
- Auditing
- Validation
- Caching
- Monitoring
- Error Handling
- Metrics
- Tracing

These concerns should be implemented consistently without leaking into business logic.

---

# Architectural Quality Attributes

The architecture is designed to maximize the following qualities.

| Attribute | Goal |
|------------|------|
| Maintainability | Easy to evolve |
| Scalability | Support increasing workloads |
| Reliability | Stable under failure |
| Extensibility | Plugin-based growth |
| Security | Secure by design |
| Testability | Independent testing of modules |
| Performance | Efficient event processing |
| Portability | Cloud and self-hosted support |

---

# Evolution Strategy

The architecture shall evolve incrementally.

The expected evolution path is:

```text
Modular Monolith

↓

Modular Monolith + Background Workers

↓

Distributed Processing

↓

Optional Service Extraction

↓

Platform Ecosystem
```

Each transition shall be driven by measurable operational requirements rather than assumptions.

Premature architectural complexity shall be avoided.

---

# Core Components

The Sentinel platform is composed of a set of logical components.

Each component has a clearly defined responsibility, owns its business logic, and communicates through explicit interfaces.

Components are implementation-independent and may evolve without affecting the overall architecture.

---

# Component Overview

```text
                    Presentation Layer
                           │
                           ▼
                    API Gateway / UI
                           │
                           ▼
                 Application Services
                           │
                           ▼
────────────────────────────────────────────────────────────

Identity

Organization

Project

Application Management

Event Intake

Event Processing

Signal Engine

Correlation Engine

Alert Engine

Incident Engine

Investigation Engine

Knowledge Engine

Notification Engine

Plugin Platform

Administration

────────────────────────────────────────────────────────────

Infrastructure Services

Persistence

Queues

Cache

External Integrations
```

---

# Component Responsibilities

## Identity

Responsible for authentication, authorization, users, roles, permissions, and organization membership.

Responsibilities:

- Authentication
- Authorization
- Session management
- API tokens
- Role management
- Permission evaluation

Owns:

- Users
- Roles
- Permissions
- Memberships

---

## Organization

Responsible for tenant management.

Responsibilities:

- Organization lifecycle
- Organization settings
- Billing ownership
- Tenant isolation

Owns:

- Organizations

---

## Project

Responsible for organizing applications.

Responsibilities:

- Project lifecycle
- Project metadata

Owns:

- Projects

---

## Application Management

Responsible for registering monitored software.

Responsibilities:

- Applications
- Environments
- Services
- Runtime information

Owns:

- Applications
- Environments
- Services

---

## Event Intake

Responsible for receiving operational events.

Responsibilities:

- Event ingestion
- Event validation
- Authentication of integrations
- Raw event persistence

Owns:

- Events

---

## Event Processing

Responsible for transforming Events into normalized Signals.

Responsibilities:

- Event normalization
- Metadata enrichment
- Event transformation
- Event validation pipeline

Owns:

- Processing pipeline

---

## Signal Engine

Responsible for creating operational Signals.

Responsibilities:

- Signal creation
- Signal enrichment
- Signal classification

Owns:

- Signals

---

## Correlation Engine

Responsible for identifying operational relationships.

Responsibilities:

- Signal correlation
- Pattern detection
- Duplicate detection
- Context generation

Owns:

- Correlation Rules

---

## Alert Engine

Responsible for identifying operational conditions requiring attention.

Responsibilities:

- Alert creation
- Alert lifecycle
- Alert severity
- Alert suppression

Owns:

- Alerts

---

## Incident Engine

Responsible for operational incident management.

Responsibilities:

- Incident creation
- Timeline
- Incident lifecycle
- Context aggregation

Owns:

- Incidents

---

## Investigation Engine

Responsible for investigation workflows.

Responsibilities:

- Findings
- Notes
- Root Cause
- Resolution

Owns:

- Investigations

---

## Knowledge Engine

Responsible for organizational memory.

Responsibilities:

- Knowledge creation
- Knowledge indexing
- Search
- Historical preservation

Owns:

- Knowledge Base

---

## Notification Engine

Responsible for outbound communication.

Responsibilities:

- Email
- Slack
- Teams
- Discord
- Webhooks

Owns:

- Notification Queue

---

## Plugin Platform

Responsible for extending Sentinel.

Responsibilities:

- Plugin discovery
- Plugin lifecycle
- Runtime integrations
- Extension APIs

Owns:

- Plugins

---

## Administration

Responsible for platform configuration.

Responsibilities:

- Global settings
- Audit logs
- Platform metrics
- Configuration

Owns:

- Administrative configuration

---

# Component Interaction Principles

All components shall follow the following principles.

## Single Responsibility

Each component owns one business capability.

---

## Explicit Interfaces

Components communicate only through documented interfaces.

---

## No Shared Business Logic

Business rules shall not be duplicated across components.

---

## Event-Based Communication

Whenever possible, components communicate through domain events instead of direct dependencies.

---

## Independent Evolution

Internal implementation of a component may change without affecting other components, provided its public contracts remain stable.

---

# Component Ownership Matrix

| Component | Primary Domain Objects |
|-----------|------------------------|
| Identity | User, Role, Permission |
| Organization | Organization |
| Project | Project |
| Application Management | Application, Environment, Service |
| Event Intake | Event |
| Event Processing | Event |
| Signal Engine | Signal |
| Correlation Engine | Correlation Rules |
| Alert Engine | Alert |
| Incident Engine | Incident |
| Investigation Engine | Investigation, Finding, Resolution |
| Knowledge Engine | Knowledge |
| Notification Engine | Notification |
| Plugin Platform | Plugin |
| Administration | Audit, Settings |

---

# Design Principles

Every component shall:

- Own its data.
- Own its business rules.
- Expose well-defined contracts.
- Hide implementation details.
- Avoid direct coupling to unrelated components.
- Remain independently testable.
- Support future extraction if operationally justified.

---

# Component Communication

Project Sentinel follows a layered communication model.

Components interact through clearly defined contracts while minimizing direct dependencies.

The architecture distinguishes between synchronous communication, asynchronous communication, and infrastructure communication.

---

# Communication Types

## Synchronous Communication

Synchronous communication is used when an immediate response is required.

Examples include:

- User authentication
- Creating an Organization
- Registering an Application
- Viewing an Incident
- Updating Investigation Notes

Characteristics:

- Request / Response
- Immediate validation
- Transactional
- User initiated

---

## Asynchronous Communication

Asynchronous communication is used for long-running or background processing.

Examples include:

- Event normalization
- Signal creation
- Correlation
- Notification delivery
- Search indexing
- Plugin execution

Characteristics:

- Queue based
- Retry capable
- Eventually consistent
- Failure isolated

---

## Domain Event Communication

Components communicate through Domain Events whenever business state changes.

A component publishes an event without knowledge of which components consume it.

This minimizes coupling while improving extensibility.

---

# Component Interaction

The following diagram illustrates the primary communication flow.

```text
Client

↓

REST API / Web UI

↓

Application Layer

↓

Business Component

↓

Domain Events

↓

Interested Components

↓

Infrastructure Services
```

Business components never communicate through shared database access.

---

# Event Flow

Operational processing follows the pipeline below.

```text
Plugin

↓

Raw Event

↓

Event Intake

↓

Event Processing

↓

Signal Engine

↓

Correlation Engine

↓

Alert Engine

↓

Incident Engine

↓

Investigation Engine

↓

Knowledge Engine
```

Each processing stage enriches operational information before passing it to the next stage.

---

# Request Flow

Interactive user requests follow the sequence below.

```text
User

↓

Presentation Layer

↓

Application Service

↓

Domain

↓

Repository

↓

Database
```

The Presentation Layer never communicates directly with persistence.

---

# Dependency Direction

Dependency flow shall always point toward the Domain.

```text
Presentation

↓

Application

↓

Domain

↑

Infrastructure
```

Infrastructure depends on the Domain.

The Domain never depends on Infrastructure.

---

# Data Ownership

Every component owns its own business data.

Examples include:

| Component | Owns |
|------------|------|
| Organization | Organizations |
| Project | Projects |
| Application Management | Applications, Environments |
| Event Intake | Events |
| Signal Engine | Signals |
| Alert Engine | Alerts |
| Incident Engine | Incidents |
| Investigation Engine | Investigations |
| Knowledge Engine | Knowledge |

Components shall not modify another component's internal state directly.

All changes shall occur through public interfaces or published domain events.

---

# Transaction Boundaries

Transactions shall remain as small as possible.

Guidelines:

- One Aggregate per transaction whenever practical.
- Avoid distributed transactions.
- Long-running workflows shall use asynchronous processing.
- Domain Events shall be published only after successful transaction completion.

---

# Failure Handling

Component failures shall remain isolated.

The architecture shall support:

- Automatic retries
- Dead-letter queues
- Error logging
- Partial failure recovery
- Graceful degradation

Failure of one background component shall not prevent unrelated platform functionality.

---

# Communication Principles

The following principles govern component interaction.

## Loose Coupling

Components should know as little as possible about one another.

---

## High Cohesion

Each component should focus on a single business capability.

---

## Explicit Contracts

Every interaction shall occur through documented contracts.

---

## No Shared Persistence

Components communicate through APIs or events rather than direct database manipulation.

---

## Eventual Consistency

Where immediate consistency is unnecessary, asynchronous communication should be preferred.

---

## Observability

All cross-component communication should be traceable for operational diagnostics.