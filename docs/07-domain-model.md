# Domain Model

**Version:** 0.1  
**Status:** Approved 
**Document:** `docs/07-domain-model.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 30, 2026

---

# Purpose

This document defines the business domain of Project Sentinel.

It establishes the ubiquitous language shared across product, engineering, architecture, APIs, database design, and documentation.

The purpose of this document is to define *what* each business concept represents, independent of implementation details.

This is **not** a database schema, API specification, or software design document.

---

# Domain Philosophy

Sentinel is an **Operational Context Platform**.

Traditional observability platforms focus on collecting telemetry.

Sentinel focuses on transforming operational telemetry into actionable operational context.

The platform organizes operational information around incidents rather than around individual logs, metrics, or events.

The Incident is therefore the central domain concept of Sentinel.

---

# Domain Overview

The Sentinel domain consists of four major business areas.

```text
Organization

↓

Operational Landscape

↓

Operational Intelligence

↓

Organizational Knowledge
```

Each area represents a bounded business concept that collaborates with the others while maintaining clear responsibilities.

---

# Domain Areas

## 1. Organization

Represents the customer using Sentinel.

The Organization owns all operational resources and defines the security and administrative boundary of the platform.

Primary concepts include:

- Organization
- Member
- Team
- Project

---

## 2. Operational Landscape

Represents the applications being monitored.

This area models where operational activity occurs.

Primary concepts include:

- Application
- Environment
- Service
- Deployment

---

## 3. Operational Processing Model

Represents what is happening inside the operational landscape.

This area transforms operational activity into incidents and investigations.

Primary concepts include:

- Signal
- Alert
- Incident
- Investigation
- Finding
- Resolution

---

## 4. Organizational Knowledge

Represents the long-term knowledge generated through operational experience.

Primary concepts include:

- Incident History
- Investigation History
- Resolution History
- Knowledge Base

---

# Core Domain Concepts

The following concepts form the ubiquitous language of Sentinel.

---

# Organization

## Definition

An Organization represents a customer account within Sentinel.

It defines the highest level of ownership, security, billing, and administration.

Every business object within Sentinel belongs to exactly one Organization.

---

## Responsibilities

- Own projects
- Own members
- Own operational resources
- Define organizational settings
- Isolate customer data

---

## Does Not Represent

- A development team
- A department
- A cloud account

---

# Project

## Definition

A Project represents a logical grouping of related applications.

Projects provide organizational structure within an Organization.

They exist to simplify ownership, permissions, navigation, and reporting.

---

## Responsibilities

- Group applications
- Organize operational data
- Support reporting boundaries

---

## Does Not Represent

- A Git repository
- A deployment pipeline
- A software package

---

# Application

## Definition

An Application is a deployable software system that produces operational activity.

Examples include:

- Laravel application
- Symfony application
- Node.js service
- Go service
- Python application

Applications are primary producers of operational signals.

---

## Responsibilities

- Produce operational signals
- Generate deployments
- Own operational history

---

## Does Not Represent

- A server
- A container
- Infrastructure

---

# Environment

## Definition

An Environment represents an isolated runtime of an application.

Examples include:

- Development
- Testing
- Staging
- Production

Operational behavior is evaluated independently for each environment.

---

## Responsibilities

- Separate operational activity
- Define deployment boundaries
- Scope operational investigations

---

## Does Not Represent

- A physical machine
- A Kubernetes cluster
- A cloud provider

---

# Service

## Definition

A Service represents a logical runtime component within an application.

Examples include:

- API
- Worker
- Scheduler
- Queue Consumer
- Web Application

Services enable Sentinel to understand operational relationships inside an application.

---

## Responsibilities

- Produce operational signals
- Participate in incidents
- Support dependency mapping

---

## Does Not Represent

- A Docker container
- A VM
- Infrastructure resources

---

# Deployment

## Definition

A Deployment represents a change introduced into an environment.

Deployments provide essential operational context during incident investigations.

A deployment is considered an operational event rather than an infrastructure object.

---

## Responsibilities

- Record application changes
- Support investigation timelines
- Enable change correlation

---

## Does Not Represent

- CI/CD pipelines
- Build systems
- Source control commits

---

# Operational Hierarchy

The organizational structure of Sentinel follows this hierarchy.

```text
Organization
    └── Project
            └── Application
                    └── Environment
                            └── Service
```

This hierarchy establishes ownership and operational boundaries throughout the platform.

---

# Design Principles

The organizational model follows these principles.

## Clear Ownership

Every domain object must have a single owner.

Ownership simplifies authorization, auditing, and lifecycle management.

---

## Separation of Concerns

Business concepts should remain independent of infrastructure implementation.

For example, an Application exists regardless of whether it runs on Docker, Kubernetes, or a virtual machine.

---

## Technology Agnostic

The domain model must support multiple runtimes and deployment models without requiring structural changes.

---

## Stable Vocabulary

Domain terminology should remain stable over time.

Implementation details may evolve, but the meaning of core business concepts should remain consistent across all versions of Sentinel.

---

# Operational Processing Model

Operational Intelligence is the core business domain of Sentinel.

Its purpose is to transform isolated operational activity into actionable operational context.

Rather than treating logs, exceptions, deployments, or metrics as independent sources of truth, Sentinel organizes them into a coherent investigation model centered around incidents.

This domain consists of the following concepts:

```text
Event
    ↓
Signal
    ↓
Alert
    ↓
Incident
    ↓
Investigation
    ↓
Finding
    ↓
Resolution
    ↓
Knowledge
```

Each concept represents a distinct stage in the operational lifecycle.

---
# Event

## Definition

An Event represents a raw operational occurrence received by Sentinel.

Events are immutable facts produced by applications, infrastructure, integrations, or plugins.

Events are the lowest-level operational information processed by the platform.

Sentinel does not reason directly about Events.

Instead, Events are normalized into Signals before correlation begins.
---

# Signal

## Definition

A Signal is a normalized operational observation derived from one or more Events.

Signals represent operational information that has been standardized by Sentinel into a consistent internal model.

Unlike Events, which are raw operational facts, Signals provide a canonical representation that can be correlated, analyzed, and organized into higher-level operational concepts.

Signals are immutable once created.

---

## Examples

Examples of Signals include:

- An exception was thrown.
- A deployment completed.
- A scheduled task failed.
- A queue job exceeded its retry limit.
- An HTTP endpoint returned an abnormal response.
- An application health check failed.
- A configuration value changed.
- A service became unavailable.

---

## Characteristics

A Signal:

- Is derived from one or more Events.
- Represents a normalized operational observation.
- Always belongs to one application and one environment.
- Has a timestamp.
- Has a source.
- May contain metadata.
- May participate in one or more alerts.
- May exist without ever becoming important.

---

## Does Not Represent

A Signal is **not**:

- An incident.
- A notification.
- A user action.
- A deployment pipeline.
- A monitoring dashboard.

---

# Alert

## Definition

An Alert represents an operational condition that requires attention.

Alerts are derived from one or more Signals.

Unlike Signals, Alerts express **concern**, not merely **observation**.

An Alert indicates that the operational state may require human investigation.

---

## Characteristics

An Alert:

- References one or more Signals.
- Has a severity.
- Has a lifecycle.
- May be acknowledged.
- May be resolved.
- May contribute to an Incident.
- May never result in an Incident.

---

## Examples

- Five identical exceptions within one minute.
- CPU utilization exceeds a defined threshold.
- Application health check continuously fails.
- Error rate increases unexpectedly.
- Queue backlog exceeds acceptable limits.

---

## Business Purpose

Alerts reduce operational noise by filtering large volumes of Signals into meaningful operational events.

---

# Incident

## Definition

An Incident represents a confirmed operational problem requiring investigation.

It is the central business object of Sentinel.

Every investigation, timeline, finding, and resolution exists because of an Incident.

Sentinel is fundamentally organized around Incidents rather than around telemetry.

---

## Characteristics

An Incident:

- Belongs to exactly one Organization.
- References one or more Alerts.
- References one or more Signals.
- Owns exactly one Investigation.
- Maintains a lifecycle.
- Preserves historical context.
- Produces organizational knowledge.

---

## Responsibilities

An Incident exists to organize:

- Operational context.
- Investigation activities.
- Timeline.
- Findings.
- Resolution.
- Historical knowledge.

---

## Does Not Represent

An Incident is **not**:

- A log entry.
- An exception.
- A notification.
- A deployment.
- A monitoring alert.

---

# Investigation

## Definition

An Investigation represents the structured process of understanding and resolving an Incident.

Investigations organize evidence rather than generate it.

They provide engineers with a dedicated workspace for reasoning about operational failures.

---

## Characteristics

An Investigation:

- Belongs to exactly one Incident.
- Contains findings.
- Contains investigation notes.
- Maintains a timeline.
- Records hypotheses.
- Records the confirmed root cause.
- Ends with a Resolution.

---

## Business Purpose

Reduce investigation time by organizing evidence into a coherent workflow.

---

# Finding

## Definition

A Finding is a meaningful conclusion reached during an Investigation.

Findings describe observed relationships, hypotheses, or verified facts.

They gradually build the understanding required to identify the root cause.

---

## Examples

- Errors began immediately after Deployment 247.
- All failures originate from the payment service.
- Queue workers stopped processing after a configuration change.
- Database response time increased significantly.

---

## Characteristics

Findings:

- Belong to one Investigation.
- May reference multiple Signals.
- May reference Alerts.
- May be revised until the Investigation is complete.

---

# Resolution

## Definition

A Resolution records how an Incident was successfully resolved.

It captures both the corrective action and the operational outcome.

A Resolution closes the Investigation lifecycle.

---

## Characteristics

A Resolution includes:

- Root cause.
- Corrective actions.
- Resolution summary.
- Resolution timestamp.
- Responsible engineer.
- Lessons learned.

---

## Business Purpose

Ensure future engineers can understand how similar incidents were resolved.

---

# Knowledge

## Definition

Knowledge represents the long-term operational experience accumulated by an Organization.

Knowledge is generated from completed Investigations and successful Resolutions.

Unlike telemetry, Knowledge becomes increasingly valuable over time.

---

## Characteristics

Knowledge:

- Is searchable.
- Is reusable.
- Supports future investigations.
- Preserves organizational memory.
- Improves operational maturity.

---

# Operational Lifecycle

The complete operational lifecycle within Sentinel is shown below.

```text
Operational Activity
        │
        ▼
      Event
        │
        ▼
     Signal
        │
        ▼
      Alert
        │
        ▼
    Incident
        │
        ▼
 Investigation
        │
        ▼
     Findings
        │
        ▼
    Resolution
        │
        ▼
    Knowledge
```

Each stage enriches the previous one.

Events become normalized Signals.

Signals are evaluated to produce Alerts.

Alerts are correlated into Incidents.

Investigations transform Incidents into organizational Knowledge.

The objective of Sentinel is not to collect more operational data.

The objective is to continuously increase the amount of operational understanding derived from that data.

---

# Domain Principles

## Observation Before Interpretation

Signals describe observations.

They do not imply failure.

---

## Context Before Action

Operational decisions should be based on correlated context rather than isolated observations.

---

## Incident-Centric Design

All operational workflows revolve around Incidents.

Signals, Alerts, Findings, and Resolutions exist to support Incident management.

---

## Knowledge Accumulation

Every resolved Incident increases the operational knowledge of the Organization.

Operational experience should never be lost.

---

# Domain Relationships

This section defines the relationships between the core domain concepts.

These relationships describe business ownership and behavior rather than database implementation.

---

## Organizational Hierarchy

```text
Organization
    └── Project
            └── Application
                    └── Environment
                            └── Service
```

### Relationship Rules

- An Organization owns one or more Projects.
- A Project belongs to exactly one Organization.
- A Project contains one or more Applications.
- An Application belongs to exactly one Project.
- An Application contains one or more Environments.
- An Environment belongs to exactly one Application.
- An Environment contains zero or more Services.
- A Service belongs to exactly one Environment.

---

## Operational Processing Flow

```text
Event
    ↓
Signal
    ↓
Alert
    ↓
Incident
    ↓
Investigation
    ↓
Finding
    ↓
Resolution
    ↓
Knowledge
```

Each stage enriches the previous stage with additional meaning.

Not every object progresses through every stage.

---

# Domain Relationships

## Event → Signal

### Cardinality

- One Event may produce one Signal.
- Multiple Events may be combined into one Signal.
- One Event cannot belong to multiple Signals.

### Business Rules

- Events are immutable.
- Events are never modified after ingestion.
- Events are processed exactly once.
- Every Signal must reference at least one Event.

---

## Signal → Alert

### Cardinality

- One Signal may produce zero or one Alert.
- Multiple Signals may contribute to one Alert.

### Business Rules

- Most Signals never become Alerts.
- Alerts exist only when operational attention is required.
- Alert creation is determined by correlation rules.

---

## Alert → Incident

### Cardinality

- One Alert may belong to zero or one Incident.
- One Incident may contain many Alerts.

### Business Rules

- Alerts may be acknowledged without creating an Incident.
- Multiple related Alerts should be grouped into a single Incident whenever appropriate.
- Incident creation should minimize duplicate investigations.

---

## Incident → Investigation

### Cardinality

- Every Incident owns exactly one Investigation.
- Every Investigation belongs to exactly one Incident.

### Business Rules

- An Investigation is created automatically when an Incident is created.
- An Investigation cannot exist independently.
- An Investigation remains available after the Incident is resolved.

---

## Investigation → Finding

### Cardinality

- One Investigation contains zero or more Findings.

### Business Rules

- Findings are created throughout the investigation.
- Findings may evolve as new evidence becomes available.
- Findings are preserved after resolution.

---

## Investigation → Resolution

### Cardinality

- One Investigation produces zero or one Resolution.

### Business Rules

- A Resolution closes the Investigation.
- A Resolution records the confirmed root cause.
- A Resolution cannot exist without an Investigation.

---

## Resolution → Knowledge

### Cardinality

- Every Resolution contributes to the Knowledge Base.

### Business Rules

- Knowledge is generated automatically.
- Historical knowledge is never deleted.
- Knowledge remains searchable.

---

# Aggregate Boundaries

The following aggregates define transactional consistency boundaries within the domain.

| Aggregate | Root Entity |
|------------|-------------|
| Organization | Organization |
| Project | Project |
| Application | Application |
| Incident | Incident |
| Investigation | Investigation |

All modifications to entities within an aggregate must occur through its Aggregate Root.

---

# Invariants

The following business rules must always be true.

## Organization

- Every domain object belongs to exactly one Organization.

---

## Project

- A Project cannot exist without an Organization.

---

## Application

- An Application cannot exist without a Project.

---

## Environment

- An Environment cannot exist without an Application.

---

## Event

- Events are immutable.
- Events cannot be edited.
- Events cannot be reassigned.

---

## Signal

- Every Signal references at least one Event.
- Signals are immutable.

---

## Alert

- Every Alert references at least one Signal.

---

## Incident

- Every Incident owns exactly one Investigation.
- An Incident must belong to exactly one Organization.
- An Incident must contain at least one Alert.

---

## Investigation

- An Investigation cannot exist independently.
- Investigation history is immutable after resolution.

---

## Resolution

- A Resolution requires a confirmed root cause.
- A Resolution permanently closes the Investigation unless the Incident is reopened.

---

# Lifecycle Rules

The operational lifecycle follows these principles.

- Events are ingested.
- Signals are normalized.
- Alerts are correlated.
- Incidents organize operational context.
- Investigations organize evidence.
- Findings capture understanding.
- Resolutions preserve outcomes.
- Knowledge preserves organizational memory.

---

# Design Constraints

The domain model follows these constraints.

- Domain concepts must remain independent of infrastructure.
- Business rules must not depend on specific technologies.
- The domain must support multiple runtimes.
- Plugins extend behavior without modifying the core domain.
- All operational history must remain auditable.
- Historical knowledge must outlive individual incidents.

---

# Aggregate Model

Sentinel follows Domain-Driven Design (DDD) principles where appropriate.

Aggregates define transactional consistency boundaries.

Each Aggregate has a single Aggregate Root responsible for protecting business invariants.

Cross-aggregate communication should occur through domain events rather than direct state modification.

---

# Organization Aggregate

## Aggregate Root

Organization

## Contains

- Organization
- Members
- Teams
- Organization Settings

## Responsibilities

- Organizational ownership
- Tenant isolation
- Billing ownership
- Global configuration

## Invariants

- Every resource belongs to exactly one Organization.
- Members cannot belong to resources outside their Organization.

---

# Project Aggregate

## Aggregate Root

Project

## Contains

- Project
- Project Settings

## Responsibilities

- Organize applications
- Reporting boundary
- Operational ownership

## Invariants

- Every Project belongs to one Organization.
- Applications cannot move between Organizations.

---

# Application Aggregate

## Aggregate Root

Application

## Contains

- Application
- Environments
- Services

## Responsibilities

- Define monitored software
- Own operational metadata
- Define runtime boundaries

## Invariants

- Every Environment belongs to one Application.
- Every Service belongs to one Environment.

---

# Incident Aggregate

## Aggregate Root

Incident

## Contains

- Incident
- Timeline
- Linked Alerts
- Linked Signals

## Responsibilities

- Represent an operational problem.
- Maintain operational context.
- Preserve lifecycle history.

## Invariants

- Every Incident owns exactly one Investigation.
- An Incident cannot exist without at least one Alert.

---

# Investigation Aggregate

## Aggregate Root

Investigation

## Contains

- Investigation
- Findings
- Notes
- Resolution

## Responsibilities

- Organize investigation activities.
- Preserve evidence.
- Record conclusions.

## Invariants

- Every Investigation belongs to one Incident.
- Every Resolution belongs to one Investigation.

---

# Knowledge Aggregate

## Aggregate Root

Knowledge Entry

## Contains

- Knowledge Entry
- References
- Tags
- Historical Links

## Responsibilities

- Preserve operational experience.
- Support future investigations.
- Build organizational memory.

## Invariants

- Knowledge entries are immutable after publication.
- Historical references cannot be removed.

---

# Aggregate Relationships

```text
Organization
        │
        ├──────────────┐
        │              │
        ▼              ▼
    Project       Knowledge
        │
        ▼
 Application
        │
        ▼
Environment
        │
        ▼
 Service

Events
    │
    ▼
Signals
    │
    ▼
Alerts
    │
    ▼
Incident
    │
    ▼
Investigation
    │
    ▼
Resolution
```

Each Aggregate protects its own consistency.

Communication between Aggregates should occur through domain events whenever possible.

---

# Domain Events

The following business events represent significant changes within the domain.

## Organization Events

- OrganizationCreated
- OrganizationUpdated
- OrganizationArchived

---

## Application Events

- ApplicationRegistered
- ApplicationUpdated
- ApplicationArchived

---

## Deployment Events

- DeploymentStarted
- DeploymentCompleted
- DeploymentFailed

---

## Operational Events

- EventReceived
- SignalCreated
- AlertRaised
- AlertResolved

---

## Incident Events

- IncidentCreated
- IncidentUpdated
- IncidentResolved
- IncidentReopened

---

## Investigation Events

- InvestigationStarted
- FindingRecorded
- RootCauseConfirmed
- ResolutionRecorded

---

## Knowledge Events

- KnowledgeCreated
- KnowledgeUpdated
- KnowledgePublished

---

# Future Evolution

The domain model is intentionally technology-independent.

Future versions of Sentinel may introduce additional concepts including:

- Plugin
- Integration
- Automation
- Runbook
- Feature Flag
- Secret
- Deployment Pipeline
- AI Assistant
- Workflow
- Policy
- Approval
- Action

These concepts will extend the domain without changing its foundational vocabulary.

---

# Related Documents

- `docs/00-project-charter.md`
- `docs/01-product-positioning.md`
- `docs/02-business-model.md`
- `docs/03-product-requirements.md`
- `docs/04-user-personas.md`
- `docs/05-user-stories.md`
- `docs/06-mvp.md`
- `docs/08-software-requirements.md`
- `docs/architecture/`
- `docs/database/`
- `docs/api/`

---

# Revision History

| Version | Date | Author | Notes |
|----------|------------|----------|-----------------------------------------|
| 0.1 | 2026-07-30 | Founders | Initial Domain Model draft |