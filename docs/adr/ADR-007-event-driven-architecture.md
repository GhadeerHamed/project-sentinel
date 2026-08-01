# ADR-007 — Event-Driven Architecture

**Status:** Accepted

**Date:** August 1, 2026

**Decision Makers:** Founders

---

# Context

Project Sentinel consists of multiple business capabilities, including Event Processing, Alert Management, Incident Management, Knowledge Management, Plugins, Agents, Notifications, and future platform extensions.

These capabilities must collaborate while remaining independently evolvable.

Direct synchronous communication between Components creates tight coupling, reduces scalability, complicates testing, and limits extensibility.

The platform requires a communication model that enables Components to react to business events without creating unnecessary dependencies.

---

# Decision

Project Sentinel adopts Event-Driven Architecture (EDA) as its primary communication model between business Components.

Business state changes are represented as immutable Domain Events.

Components publish Domain Events after successfully completing business operations.

Other Components subscribe to the Events they require without creating direct dependencies on the publishing Component.

---

# Event Lifecycle

Every Domain Event follows the same lifecycle.

```text
Business Action

↓

Business Rule Validation

↓

State Change

↓

Domain Event Created

↓

Domain Event Published

↓

Subscribers Process Event
```

Events represent facts that have already occurred.

They never represent commands or requests.

---

# Event Ownership

Each Domain Event has exactly one publishing Component.

Only the owning Component may publish Events describing changes to its own business concepts.

Examples include:

| Component | Example Domain Events |
|-----------|-----------------------|
| Application Management | Application Registered, Application Updated |
| Event Processing | Event Recorded, Event Classified |
| Alert Management | Alert Created, Alert Acknowledged |
| Incident Management | Incident Opened, Incident Resolved |
| Knowledge Management | Knowledge Captured, Knowledge Updated |

Ownership prevents conflicting interpretations of business state.

---

# Immutable Events

Domain Events are immutable.

After publication:

- Event data cannot be modified.
- Event identity remains permanent.
- Historical records remain trustworthy.

Corrections are represented by publishing new Domain Events rather than modifying existing ones.

---

# Event Semantics

Domain Events describe business facts, not technical implementation details.

Event names should express business meaning rather than infrastructure behavior.

Examples:

✓ Incident Opened
✓ Deployment Completed
✓ Alert Acknowledged

Instead of:

✗ Database Row Inserted
✗ HTTP Request Received
✗ Queue Message Processed

Business-oriented Events create a stable contract that remains valid even as implementation technologies evolve.

---

# Loose Coupling

Components communicate through Events instead of direct implementation dependencies.

```text
Application Management

↓

Application Registered

↓

Plugin Platform
Notification Platform
Knowledge Management
Audit Logging
```

Publishers remain unaware of subscribers.

Subscribers remain independent of one another.

---

# Event Processing

Subscribers process Events independently.

Processing may occur:

- Immediately
- Asynchronously
- In parallel
- Through retries
- Through replay

The Event Model remains consistent regardless of processing strategy.

---

# Event Contracts

Domain Events are public contracts between Components.

Event definitions include:

- Event type
- Event identifier
- Organization context
- Timestamp
- Business payload
- Event version

Published Event contracts are versioned and remain backward compatible whenever practical.

---

# Failure Handling

Event processing failures are isolated.

A failure in one subscriber shall not prevent:

- Event publication
- Other subscribers from processing
- Future event delivery

Failed processing may be retried according to platform policy.

---

# Rationale

Event-Driven Architecture provides:

- Loose coupling
- Independent Component evolution
- Horizontal scalability
- Better extensibility
- Improved resilience
- Observable business workflows
- Natural integration with Plugins and Agents

The Event Model becomes the backbone of platform collaboration.

---

# Consequences

Positive consequences include:

- Components evolve independently.
- New capabilities subscribe to existing Events.
- Plugins integrate without modifying Core Components.
- Agents observe business activity naturally.
- Auditability improves through immutable business Events.
- Platform scalability increases through asynchronous processing.

Trade-offs include:

- More complex debugging across asynchronous workflows.
- Event versioning requires governance.
- Event contracts become long-term architectural commitments.

These trade-offs are accepted because they significantly improve platform flexibility and maintainability.

---

# Alternatives Considered

## Direct Service Calls

Allow Components to invoke one another directly.

Rejected because direct dependencies reduce modularity and make future evolution more difficult.

---

## Shared Database Communication

Allow Components to communicate by reading each other's database tables.

Rejected because database schemas are implementation details rather than architectural contracts.

This approach creates hidden dependencies and weakens Component ownership.

---

## Central Orchestrator

Route every business interaction through a central orchestration service.

Rejected because it concentrates business knowledge in a single Component, reducing autonomy and increasing coupling.

---

# Architectural Principles

The Event-Driven Architecture follows these principles.

- Events represent completed business facts.
- Every Event has a single owner.
- Events are immutable.
- Components communicate through published contracts.
- Publishers remain unaware of subscribers.
- Subscribers process Events independently.
- Event contracts evolve through versioning rather than modification.

---

# Related Documents

- `architecture/03-component-architecture.md`
- `architecture/04-data-flow.md`
- `architecture/05-event-model.md`
- `architecture/09-scaling-strategy.md`

---

# Related ADRs

- `ADR-002-plugin-architecture.md`
- `ADR-004-operational-processing-model.md`
- `ADR-005-domain-driven-design.md`
- `ADR-006-agent-platform.md`
- `ADR-011-canonical-event-model.md`