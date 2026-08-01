# Runtime Architecture

**Version:** 0.1  
**Status:** Draft (In Progress)  
**Document:** `architecture/02-runtime-architecture.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 31, 2026

---

# Purpose

This document defines the runtime architecture of Project Sentinel.

It describes the executable runtime components that compose the platform, their responsibilities, communication patterns, and deployment topology.

The runtime architecture translates the logical architecture into a deployable production system.

---

# Goals

The runtime architecture is designed to achieve the following objectives:

- Keep the MVP operationally simple.
- Separate interactive workloads from background processing.
- Support horizontal scaling.
- Minimize infrastructure complexity.
- Enable future architectural evolution.
- Maintain high operational reliability.

---

# Architectural Principles

The runtime architecture follows these principles.

## Modular Monolith

Sentinel is deployed as a single application while remaining internally modular.

Modules share one deployment artifact but maintain strict architectural boundaries.

---

## Stateless Request Processing

Interactive requests should remain stateless whenever practical.

Application state should be stored in external infrastructure.

---

## Background Processing

Long-running operations must execute asynchronously.

User requests should complete as quickly as possible.

---

## Horizontal Scalability

Runtime components should be independently scalable.

Scaling decisions should be based on operational metrics rather than assumptions.

---

## Operational Simplicity

Infrastructure complexity should grow only when justified by measurable requirements.

---

# Runtime Topology

The initial production deployment consists of the following runtime components.

```text
                   Users
                     │
                     ▼
          Web Browser / API Client
                     │
                     ▼
             Sentinel Web Application
                     │
        ┌────────────┴────────────┐
        ▼                         ▼
   PostgreSQL                  Redis
                                    │
                                    ▼
                           Background Workers
                                    │
                                    ▼
                           Scheduled Tasks
```

The MVP intentionally minimizes the number of independently deployed processes.

---

# Runtime Components

The Sentinel runtime consists of five primary components.

| ID | Component | Responsibility |
|----|-----------|----------------|
| RT-01 | Web Application | User interactions and API requests |
| RT-02 | Background Workers | Asynchronous business processing |
| RT-03 | Scheduler | Scheduled platform jobs |
| RT-04 | PostgreSQL | Persistent business data |
| RT-05 | Redis | Operational infrastructure |

---

# RT-01 — Web Application

## Responsibilities

The Web Application is responsible for:

- Dashboard
- REST API
- Authentication
- Authorization
- Request validation
- Executing application use cases
- Publishing domain events

The Web Application should never execute computationally expensive operations directly.

---

## Does Not Perform

The Web Application does not perform:

- Signal correlation
- Notification delivery
- Knowledge indexing
- Plugin execution
- Long-running imports
- Background maintenance

These responsibilities belong to asynchronous workers.

---

# RT-02 — Background Workers

## Responsibilities

Workers execute asynchronous business processes.

Typical workloads include:

- Event normalization
- Signal creation
- Correlation
- Alert generation
- Incident enrichment
- Notification delivery
- Knowledge indexing
- Plugin execution

Workers consume jobs from Redis-backed queues.

---

## Worker Design

Workers are stateless.

Any worker instance may execute any compatible job.

This allows horizontal scaling without architectural changes.

---

# RT-03 — Scheduler

The Scheduler is responsible for initiating recurring platform operations.

Examples include:

- Data retention
- Cleanup
- Metrics aggregation
- Health verification
- Scheduled notifications
- Maintenance operations

The Scheduler delegates long-running work to the Worker runtime.

---

# RT-04 — PostgreSQL

PostgreSQL is the authoritative source of persistent business data.

All business entities are stored in PostgreSQL.

Redis shall never become the source of truth.

---

# RT-05 — Redis

Redis provides transient operational capabilities.

Responsibilities include:

- Queue backend
- Distributed locks
- Cache
- Rate limiting
- Temporary operational state

Redis data should be considered disposable.

Business correctness shall never depend solely on Redis.

---

# Runtime Communication

The runtime components communicate through well-defined interfaces.

The architecture distinguishes between synchronous request processing and asynchronous background processing.

The following communication paths are permitted.

| Source | Destination | Communication |
|----------|-------------|---------------|
| Client | Application Runtime | HTTPS |
| Application Runtime | PostgreSQL | SQL |
| Application Runtime | Redis | Cache / Queue |
| Worker Runtime | PostgreSQL | SQL |
| Worker Runtime | Redis | Queue / Cache |
| Scheduler Runtime | Redis | Queue |
| Scheduler Runtime | PostgreSQL | SQL |

Runtime components shall not communicate through undocumented interfaces.

---

# Request Lifecycle

Interactive requests follow the sequence below.

```text
Client

↓

Application Runtime

↓

Application Layer

↓

Domain Layer

↓

Persistence

↓

Response
```

Interactive requests should complete quickly.

Expensive operations shall be delegated to background workers whenever possible.

---

# Background Processing Lifecycle

Long-running operations follow the asynchronous processing pipeline.

```text
Application Runtime

↓

Queue

↓

Worker Runtime

↓

Business Processing

↓

Persistence

↓

Domain Events

↓

Follow-up Jobs
```

This architecture keeps user-facing latency independent from computational workload.

---

# Scheduled Processing Lifecycle

Recurring platform operations follow the sequence below.

```text
Scheduler Runtime

↓

Scheduled Command

↓

Queue

↓

Worker Runtime

↓

Business Processing
```

The Scheduler shall initiate work only.

Business execution belongs to the Worker Runtime.

---

# Runtime Isolation

Each runtime has clearly defined responsibilities.

## Application Runtime

Optimized for:

- Low latency
- High concurrency
- User interactions
- API requests

---

## Worker Runtime

Optimized for:

- Long-running jobs
- Batch processing
- Event processing
- Operational intelligence

---

## Scheduler Runtime

Optimized for:

- Periodic orchestration
- Maintenance scheduling
- Triggering background jobs

---

## Infrastructure Components

Infrastructure components provide shared technical capabilities without containing business logic.

Examples include:

- PostgreSQL
- Redis
- Object Storage (future)

---

# Failure Isolation

Failures shall remain isolated whenever possible.

Examples include:

- A failed notification shall not block Incident creation.
- A failed plugin shall not stop Event processing.
- A failed scheduled task shall not affect user requests.
- A failed Worker shall not terminate the Application Runtime.

Runtime failures should degrade gracefully.

---

# Runtime Scaling

Each runtime may scale independently.

```text
               Load Balancer
                      │
        ┌─────────────┼─────────────┐
        ▼             ▼             ▼
 Application    Application    Application
   Runtime        Runtime        Runtime

                      │
                  PostgreSQL
                      │
                    Redis
                      │
        ┌─────────────┼─────────────┐
        ▼             ▼             ▼
   Worker        Worker        Worker
   Runtime       Runtime       Runtime
```

Scaling decisions shall be driven by measurable operational metrics.

---

# Runtime Health

Every runtime shall expose health information.

Health checks should verify:

- Process availability
- Database connectivity
- Queue connectivity
- Cache availability
- Dependency status

Health information shall support automated monitoring systems.

---

# Runtime Logging

Each runtime shall generate structured logs.

Logs should include:

- Timestamp
- Runtime identifier
- Correlation identifier
- Severity
- Message
- Context

Logs shall support centralized aggregation in future deployments.

---

# Runtime Metrics

Each runtime should expose operational metrics.

Examples include:

- Request latency
- Queue depth
- Worker throughput
- Job execution time
- Database latency
- Cache hit ratio
- Error rate

Metrics support capacity planning and operational diagnostics.

---

# Runtime Design Decisions

The following design decisions define the runtime architecture.

---

## RD-001 — Modular Monolith Deployment

Sentinel shall be deployed as a modular monolith.

Business modules remain logically isolated while sharing a single deployment artifact.

### Rationale

- Lower operational complexity.
- Faster development.
- Easier testing.
- Simplified deployments.
- Clear migration path toward future service extraction.

---

## RD-002 — Stateless Application Runtime

The Application Runtime shall remain stateless whenever practical.

Persistent application state shall be stored in external infrastructure.

### Rationale

Stateless services are easier to scale, replace, and recover.

---

## RD-003 — Queue-Based Background Processing

Long-running operations shall execute through asynchronous job queues.

### Rationale

Separating interactive requests from computational work improves responsiveness and resilience.

---

## RD-004 — PostgreSQL as Source of Truth

Persistent business data shall be stored in PostgreSQL.

Redis shall never become the authoritative source for business data.

### Rationale

Clear ownership of persistent state simplifies consistency and recovery.

---

## RD-005 — Redis for Operational Infrastructure

Redis shall provide transient operational capabilities only.

Examples include:

- Queues
- Cache
- Locks
- Rate limiting

### Rationale

Redis offers excellent performance for transient workloads while keeping business data safely persisted elsewhere.

---

## RD-006 — Push-Based Processing

External systems should push operational events to Sentinel.

Polling should be used only when push integration is not possible.

### Rationale

Push-based integrations reduce latency, infrastructure cost, and synchronization complexity.

---

# Runtime Trade-offs

Every architectural decision involves trade-offs.

The following trade-offs are accepted for the MVP.

| Decision | Benefit | Trade-off |
|----------|---------|-----------|
| Modular Monolith | Simpler operations | Single deployment artifact |
| PostgreSQL | Strong consistency | Vertical scaling limits before partitioning |
| Redis Queues | Simple deployment | Fewer advanced messaging features than dedicated brokers |
| Background Workers | Better responsiveness | Eventual consistency |
| Push Integrations | Lower latency | Requires integration support from external systems |

These trade-offs will be reviewed as Sentinel evolves.

---

# Future Evolution

The runtime architecture is expected to evolve incrementally.

Potential future enhancements include:

- Dedicated event broker (NATS, RabbitMQ, or Kafka)
- Separate worker pools by workload type
- Dedicated search infrastructure
- Independent API gateway
- Multi-region deployments
- Dedicated plugin execution runtime
- Distributed event processing

Such changes shall be driven by measurable operational needs rather than anticipated scale.

---

# Architecture Decision Records

The runtime architecture is supported by the following ADRs.

- ADR-002 — Plugin Architecture
- ADR-003 — Multi-Tenancy
- ADR-004 — Operational Processing Model
- ADR-005 — Domain-Driven Design

This document is supported by the Architecture Decision Records (ADRs) in the `/ADR` directory.

Additional ADRs will be added as architectural decisions are formalized.

---

# Related Documents

- `architecture/00-architecture-overview.md`
- `architecture/01-system-context.md`
- `architecture/03-component-architecture.md`
- `architecture/04-data-flow.md`
- `architecture/05-event-model.md`
- `deployment/00-deployment-overview.md`
- `docs/07-domain-model.md`
- `docs/08-software-requirements.md`

---

# Revision History

| Version | Date | Author | Notes |
|----------|------------|---------|--------------------------------------|
| 0.1 | July 31, 2026 | Founders | Initial runtime architecture |