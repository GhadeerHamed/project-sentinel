# Event Model

**Version:** 0.1  
**Status:** Draft (In Progress)  
**Document:** `architecture/05-event-model.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 31, 2026

---

# Purpose

This document defines the Event Model used throughout Project Sentinel.

It establishes the language, lifecycle, structure, ownership, and responsibilities of Events as they move through the platform.

The Event Model provides a consistent contract for SDKs, Plugins, Internal Components, APIs, Workers, and future integrations.

---

# Goals

The Event Model aims to:

- Provide a consistent representation of operational Events.
- Separate business meaning from transport mechanisms.
- Support versioning and long-term compatibility.
- Enable replay and historical analysis.
- Support asynchronous processing.
- Provide a stable foundation for future runtime integrations.

---

# Guiding Principles

The Event Model follows several core principles.

## Events Are Facts

An Event represents something that has already happened.

Events describe facts.

They never describe intentions.

Examples:

✓ Exception Occurred

✓ Queue Job Failed

✓ Deployment Completed

✗ Restart Application

✗ Send Notification

Commands are not Events.

---

## Events Are Immutable

Once accepted by Sentinel, an Event shall never be modified.

Corrections are represented as new Events.

Immutability enables:

- Replay
- Auditing
- Historical consistency
- Deterministic processing

---

## Events Describe the Past

Events always describe completed actions.

Examples:

- User Logged In
- Queue Job Started
- Queue Job Completed
- Exception Thrown

Events never describe future actions.

---

## Events Have Business Meaning

Events represent meaningful operational facts.

The Event Model intentionally avoids storing transport-specific details as business concepts.

Examples:

Good:

- Database Connection Failed
- Cache Miss
- Queue Timeout

Poor:

- HTTP POST Received
- JSON Payload Received

Transport mechanisms belong to infrastructure.

Operational meaning belongs to the Event Model.

---

# Event Definition

An Event is an immutable representation of a completed operational fact observed by Sentinel.

Events are the foundation of every processing stage within the platform.

Everything that enters Sentinel begins as an Event.

---

# Event Lifecycle

Every Event progresses through a standard lifecycle.

```text
Observed

↓

Received

↓

Validated

↓

Persisted

↓

Normalized

↓

Processed

↓

Archived
```

Each lifecycle transition is observable and traceable.

---

# Event Categories

Events are grouped into logical categories.

Examples include:

| Category | Examples |
|-----------|----------|
| Runtime | Exception, Request, Response |
| Queue | Job Started, Job Failed |
| Scheduler | Task Started, Task Completed |
| Deployment | Deployment Started, Deployment Completed |
| Infrastructure | Database Failure, Redis Failure |
| Authentication | Login Failed, Token Revoked |
| Security | Suspicious Activity, Permission Denied |
| System | Health Check Failed, Service Restarted |

Categories organize Events without affecting processing behavior.

---

# Event Ownership

Every Event belongs to exactly one Organization, one Project, one Application, and one Environment.

Ownership determines:

- Visibility
- Authorization
- Correlation scope
- Investigation scope
- Knowledge ownership

No Event may exist without an owning context.

---

# Design Objective

The Event Model establishes a stable and technology-independent representation of operational facts.

Every processing stage in Sentinel builds upon this foundation.

---

# Event Taxonomy

Not all Events have the same purpose.

Project Sentinel distinguishes between three primary Event types.

Each type represents a different stage in the operational lifecycle.

```text
External World

↓

External Events

↓

Sentinel Processing

↓

Domain Events

↓

Platform Reactions

↓

Integration Events
```

Each Event type has different ownership, consumers, and lifecycle rules.

---

# External Events

External Events originate outside of Sentinel.

They represent observations received from monitored applications, infrastructure, SDKs, or third-party integrations.

Examples include:

- ExceptionThrown
- HttpRequestCompleted
- QueueJobFailed
- CacheConnectionLost
- DeploymentCompleted
- SchedulerTaskFailed

External Events are immutable records of observed operational activity.

Their primary purpose is to provide input to Sentinel's Operational Intelligence Pipeline.

---

# Domain Events

Domain Events originate within Sentinel.

They represent meaningful business state changes inside the platform.

Examples include:

- SignalCreated
- AlertCreated
- IncidentCreated
- IncidentAssigned
- InvestigationStarted
- InvestigationResolved
- KnowledgeEntryCreated

Domain Events coordinate communication between Sentinel components while preserving loose coupling.

---

# Integration Events

Integration Events expose selected Domain Events to external consumers.

Examples include:

- Webhook notifications
- Plugin callbacks
- Future public event streams
- Third-party automation platforms

Not every Domain Event becomes an Integration Event.

Only explicitly published Events leave the platform.

---

# Event Relationships

The three Event types form a progression.

```text
External Event

↓

Signal Created

↓

Alert Created

↓

Incident Created

↓

Knowledge Entry Created

↓

Webhook Published
```

Each stage increases operational value.

---

# Event Ownership

| Event Type | Owner |
|-------------|-------|
| External Event | Event Intake |
| Domain Event | Owning Business Component |
| Integration Event | Integration Layer |

Ownership determines publication rules, versioning, and lifecycle management.

---

# Event Visibility

Different Event types have different visibility scopes.

| Event Type | Visible Outside Sentinel |
|-------------|--------------------------|
| External Event | No |
| Domain Event | No |
| Integration Event | Yes |

Internal processing details remain encapsulated within the platform.

---

# Event Consumers

Different consumers subscribe to different Event types.

| Consumer | Event Type |
|-----------|------------|
| Event Processing | External Events |
| Signal Engine | External Events |
| Alert Engine | Domain Events |
| Incident Engine | Domain Events |
| Knowledge Engine | Domain Events |
| Plugins | Integration Events (and selected Domain Events through approved extension points) |
| External Systems | Integration Events |

Each consumer processes only the Event types relevant to its responsibilities.

---

# Event Publication Rules

The following publication rules apply throughout the platform.

## ER-001 — External Events

External Events are accepted only through approved ingestion mechanisms.

Examples include:

- SDKs
- REST API
- Webhooks
- Future streaming connectors

---

## ER-002 — Domain Events

Domain Events are published only by the component that owns the business capability.

No component may publish another component's Domain Events.

---

## ER-003 — Integration Events

Integration Events are derived from Domain Events.

External systems shall never receive internal Domain Events directly.

The Integration Layer determines which information may leave the platform.

---

# Design Objective

The Event Taxonomy separates telemetry ingestion, business processing, and external communication into distinct concerns.

This separation enables Sentinel to evolve its internal architecture while maintaining stable external integrations.

---

# Canonical Event Structure

Every Event processed by Sentinel follows a canonical structure.

Individual Event types may extend this structure with additional fields, but the core metadata remains consistent across the platform.

The canonical structure enables consistent storage, processing, correlation, replay, and observability.

---

# Event Identity

Every Event shall have a globally unique identifier.

Required properties include:

| Property | Description |
|----------|-------------|
| Event ID | Globally unique identifier |
| Event Type | Canonical Event name |
| Event Version | Schema version |
| Event Category | Runtime, Queue, Deployment, etc. |
| Source | Originating system |
| Timestamp | Time the Event occurred |

The Event ID remains immutable throughout the Event lifecycle.

---

# Ownership Metadata

Every Event belongs to a specific operational context.

Required ownership information includes:

| Property | Description |
|----------|-------------|
| Organization ID | Owning organization |
| Project ID | Owning project |
| Application ID | Originating application |
| Environment | Production, Staging, Development, etc. |
| Service | Optional service or module identifier |

Ownership metadata defines authorization and correlation boundaries.

---

# Runtime Metadata

Runtime metadata describes the execution environment in which the Event occurred.

Examples include:

- Runtime
- Framework
- Framework Version
- Language
- Language Version
- Hostname
- Container ID
- Instance ID
- Region
- Operating System

Runtime metadata provides operational context without altering the Event's business meaning.

---

# Correlation Metadata

Correlation metadata links related Events.

Examples include:

| Property | Description |
|----------|-------------|
| Correlation ID | Groups related operations |
| Trace ID | Distributed tracing identifier |
| Parent Event ID | Previous Event in a chain |
| Session ID | User or execution session |
| Request ID | HTTP request identifier |

Correlation metadata supports investigation across distributed systems.

---

# Severity Metadata

Not every Event has the same operational importance.

Severity metadata may include:

- Critical
- High
- Medium
- Low
- Informational

Severity represents the significance of the observed Event itself.

It does not determine whether an Alert or Incident will be created.

---

# Payload

The Event Payload contains information specific to the Event type.

Examples include:

- Exception details
- HTTP request information
- Queue job metadata
- Deployment metadata
- Scheduler execution details

Payload schemas are defined by the Event Type and Version.

The payload remains immutable after persistence.

---

# Event Envelope

Conceptually, every Event consists of two parts.

```text
Event

├── Metadata
│
└── Payload
```

Metadata enables processing.

Payload describes the observed fact.

Processing components should rely on metadata whenever possible and interpret payloads only when necessary.

---

# Event Versioning

Every Event type shall define a schema version.

Versioning enables:

- Backward compatibility
- Replay of historical Events
- Incremental schema evolution
- SDK compatibility
- Plugin compatibility

Breaking changes require a new Event Version.

Existing versions remain supported according to the platform compatibility policy.

---

# Event Validation

Every incoming Event shall be validated before acceptance.

Validation includes:

- Required metadata
- Ownership information
- Schema compatibility
- Timestamp validity
- Payload integrity
- Supported Event Version

Events failing validation shall be rejected or quarantined.

---

# Design Principles

The canonical Event structure follows these principles.

## Consistent

Every Event shares the same structural foundation.

---

## Extensible

New Event types extend the model without modifying existing contracts.

---

## Immutable

Neither metadata nor payload may be modified after persistence.

---

## Versioned

Every Event remains independently versioned throughout its lifecycle.

---

## Self-Describing

An Event contains sufficient information to be processed without relying on external state wherever practical.

---

# Design Objective

The canonical Event structure provides a stable contract for every component, SDK, plugin, and integration within Project Sentinel.

Consistency at the Event level enables consistency throughout the platform.

---

# Event Publication

Events move through the platform according to clearly defined publication rules.

Each Event has one publisher and may have multiple consumers.

Publishers remain unaware of their consumers.

This preserves loose coupling throughout the platform.

---

# Event Publishers

Only the owning component may publish its Domain Events.

Examples include:

| Component | Published Events |
|-----------|------------------|
| Event Intake | External Events |
| Signal Engine | SignalCreated |
| Correlation Engine | SignalsCorrelated |
| Alert Engine | AlertCreated |
| Incident Engine | IncidentCreated, IncidentResolved |
| Investigation Engine | InvestigationStarted, InvestigationCompleted |
| Knowledge Engine | KnowledgeEntryCreated |

Ownership of publication is never shared.

---

# Event Consumers

Components subscribe only to Events relevant to their responsibilities.

Examples include:

| Component | Consumed Events |
|-----------|-----------------|
| Signal Engine | External Events |
| Correlation Engine | SignalCreated |
| Alert Engine | SignalsCorrelated |
| Incident Engine | AlertCreated |
| Investigation Engine | IncidentCreated |
| Knowledge Engine | InvestigationCompleted |
| Notification Engine | Selected Domain Events |
| Plugin Platform | Approved Integration Events and extension hooks |

Consumers remain independent from publishers.

---

# Event Bus

Internal communication occurs through the Internal Event Bus.

The Event Bus is responsible for:

- Event routing
- Subscriber discovery
- Delivery
- Retry handling
- Observability

The Event Bus transports Events.

It does not contain business logic.

---

# Event Processing

Event processing follows a publish-subscribe model.

```text
Publisher

↓

Internal Event Bus

↓

Subscriber A

Subscriber B

Subscriber C
```

Subscribers execute independently.

Failure in one subscriber shall not prevent delivery to others unless explicitly configured.

---

# Event Ordering

Ordering guarantees are intentionally limited.

The platform guarantees ordering only within a logical processing stream where required.

Examples include:

- Events for the same Incident
- Events for the same Investigation
- Events for the same Application

Global ordering across the platform is neither required nor guaranteed.

---

# Event Delivery

The Event Bus targets the following delivery semantics.

| Property | Target |
|----------|--------|
| Delivery | At-least-once |
| Duplicate Delivery | Possible |
| Idempotent Consumers | Required |
| Retry | Automatic |
| Replay | Supported |
| Dead Letter Queue | Supported |

Consumers must be designed to safely process duplicate Events.

---

# Event Naming

Event names shall describe completed facts.

Examples:

✓ ApplicationRegistered

✓ DeploymentCompleted

✓ SignalCreated

✓ AlertCreated

✓ IncidentResolved

Avoid imperative or command-style names such as:

✗ CreateIncident

✗ SendNotification

✗ RetryJob

Commands request work.

Events record completed work.

---

# Event Contracts

Every Event contract shall define:

- Event name
- Event type
- Event version
- Metadata schema
- Payload schema
- Publisher
- Expected consumers
- Compatibility policy

Event contracts form part of Sentinel's public architecture.

---

# Compatibility Policy

Published Event contracts should remain backward compatible whenever practical.

Breaking changes require:

- A new Event Version
- Migration documentation
- Compatibility strategy
- Deprecation timeline

Historical Events remain valid regardless of newer versions.

---

# Processing Rules

The following rules apply throughout the platform.

## EM-001 — One Publisher

Every Domain Event has exactly one publisher.

---

## EM-002 — Multiple Consumers

Any number of consumers may subscribe to an Event.

---

## EM-003 — No Consumer Awareness

Publishers shall not know who consumes their Events.

---

## EM-004 — Idempotent Processing

Consumers shall safely process duplicate deliveries.

---

## EM-005 — Immutable Contracts

Published Event contracts are immutable once released.

Future evolution occurs through versioning rather than modification.

---

## EM-006 — Observable Processing

Publication, delivery, consumption, retries, and failures shall be observable.

Operational visibility applies to the Event Bus itself.

---

# Design Objective

The Event Model provides a stable communication backbone for Project Sentinel.

By separating publishers from consumers and enforcing immutable, versioned contracts, the platform remains extensible, observable, and resilient as new capabilities are added.

---

# Future Evolution

The Event Model is designed to evolve without disrupting existing integrations or internal processing.

Future enhancements may include:

- Additional Event categories
- New runtime integrations
- AI-generated enrichment Events
- Streaming ingestion
- Event partitioning
- Cross-region Event replication
- Event archival policies
- Event retention strategies
- Event schema registry

Future capabilities shall extend the Event Model without violating existing Event contracts.

---

# Event Model Summary

The Event Model establishes a common language for operational information throughout Project Sentinel.

Every Event is:

- A completed operational fact.
- Immutable after persistence.
- Versioned.
- Traceable.
- Observable.
- Independently processable.

The Event Model separates:

- External Events
- Domain Events
- Integration Events

This separation enables Sentinel to evolve its internal architecture while maintaining stable contracts with SDKs, plugins, and external systems.

Operational intelligence is built upon Events, but Events themselves remain neutral records of observed facts.

---

# Architectural Decisions

The Event Model is guided by the following architectural principles.

- Events represent facts, not commands.
- Events are immutable.
- Business meaning is independent of transport mechanisms.
- Event processing is asynchronous whenever practical.
- Components communicate through published Events rather than direct dependencies.
- Event contracts are versioned and backward compatible whenever possible.
- Publishers remain unaware of consumers.
- Every Event contributes to explainable operational intelligence.

---

# Related Documents

- `architecture/02-runtime-architecture.md`
- `architecture/03-component-architecture.md`
- `architecture/04-data-flow.md`
- `architecture/06-plugin-system.md`
- `architecture/07-agent-architecture.md`
- `docs/07-domain-model.md`
- `docs/08-software-requirements.md`

---

# Revision History

| Version | Date | Author | Notes |
|----------|------------|---------|----------------------------------------|
| 0.1 | July 31, 2026 | Founders | Initial Event Model |