# ADR-011 — Canonical Event Model

**Status:** Accepted

**Date:** August 1, 2026

**Decision Makers:** Founders

---

# Context

Project Sentinel receives operational information from a wide variety of technologies.

Examples include:

- Laravel
- Symfony
- Node.js
- Python
- Java
- .NET
- Go
- Kubernetes
- Docker
- Cloud Providers
- CI/CD Platforms
- Monitoring Systems

Each technology represents operational data differently.

Without a common representation, every Component, Plugin, and Agent would need technology-specific logic, increasing complexity and reducing interoperability.

The platform requires a single, technology-independent representation of operational events.

---

# Decision

Project Sentinel adopts a Canonical Event Model.

All incoming operational information is transformed into a standardized internal Event representation before entering the Core platform.

The Canonical Event Model becomes the authoritative operational language used throughout the platform.

Plugins and SDKs perform translation.

The Core consumes only Canonical Events.

---

# Event Normalization

Incoming operational information follows the same processing pipeline.

```text
External Source

↓

SDK / Plugin

↓

Normalization

↓

Canonical Event

↓

Core Platform
```

Normalization removes technology-specific differences while preserving operational meaning.

---

# Canonical Principles

The Canonical Event Model follows these principles.

- Technology-independent.
- Business-oriented.
- Immutable.
- Versioned.
- Traceable.
- Extensible.

Every Event represents an operational fact rather than a transport-specific message.

---

# Canonical Event Structure

Every Canonical Event contains common metadata.

Examples include:

- Event Identifier
- Event Type
- Event Version
- Organization Identifier
- Project Identifier
- Application Identifier
- Environment Identifier
- Source Identifier
- Timestamp
- Severity
- Payload

Additional metadata may be introduced without breaking existing contracts.

---

# Translation Responsibility

Translation into the Canonical Event Model occurs at the platform boundary.

Responsibilities include:

- Mapping external formats
- Validating required fields
- Enriching contextual metadata
- Assigning Event types
- Preserving source references

Business Components never perform technology-specific translation.

---

# Source Traceability

Every Canonical Event maintains a reference to its originating source.

Traceability supports:

- Operational investigations
- Auditability
- Debugging
- Replay
- Evidence collection

Normalization never destroys the relationship between an Event and its source.

---

# Semantic Consistency

Different technologies may describe the same operational situation using different terminology.

The Canonical Event Model maps these differences into a shared operational vocabulary.

For example:

- Framework exceptions become **Application Error** Events.
- Infrastructure outages become **Availability** Events.
- Deployment notifications become **Deployment Completed** Events.

This semantic consistency enables Components, Plugins, and Agents to reason about operational concepts instead of vendor-specific implementations.

---

# Event Evolution

The Canonical Event Model evolves through versioning.

New Event versions shall preserve backward compatibility whenever practical.

Breaking changes require explicit migration strategies.

Historical Events remain valid regardless of future schema evolution.

---

# Rationale

The Canonical Event Model provides:

- Technology independence.
- Consistent business processing.
- Simplified Component logic.
- Unified Agent reasoning.
- Stable Plugin contracts.
- Easier SDK development.
- Improved interoperability.

The platform reasons about operational meaning rather than implementation details.

---

# Consequences

Positive consequences include:

- Every Component processes the same Event format.
- SDKs isolate technology-specific concerns.
- Plugins become easier to implement.
- Agents analyze a consistent operational language.
- Future integrations require translation rather than architectural changes.

Trade-offs include:

- Initial normalization cost.
- Canonical schema governance.
- Event version management.

These trade-offs are accepted because they significantly reduce long-term architectural complexity.

---

# Alternatives Considered

## Native Event Processing

Allow every Component to process technology-specific Event formats.

Rejected because Components become tightly coupled to individual technologies.

---

## Multiple Internal Event Models

Allow each integration to define its own internal Event representation.

Rejected because interoperability and Agent reasoning become increasingly difficult.

---

## Translation Inside Components

Perform translation after Events enter the Core.

Rejected because translation is a platform boundary responsibility rather than a business responsibility.

The Core should operate exclusively on Canonical Events.

---

# Architectural Principles

The Canonical Event Model follows these principles.

- One operational language.
- Translation occurs at platform boundaries.
- Events remain immutable.
- Business meaning is preserved.
- Source traceability is mandatory.
- Event evolution occurs through versioning.

---

# Related Documents

- `architecture/04-data-flow.md`
- `architecture/05-event-model.md`
- `architecture/06-plugin-system.md`
- `architecture/07-agent-architecture.md`

---

# Related ADRs

- `ADR-002-plugin-architecture.md`
- `ADR-004-operational-processing-model.md`
- `ADR-007-event-driven-architecture.md`
- `ADR-010-component-based-architecture.md`
- `ADR-012-evidence-based-operational-intelligence.md`