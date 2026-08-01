# ADR-004 — Operational Processing Model

**Status:** Accepted

**Date:** August 1, 2026

**Decision Makers:** Founders

---

# Context

Modern software systems generate large volumes of operational telemetry.

This telemetry includes application logs, exceptions, metrics, traces, deployment events, infrastructure notifications, and runtime signals.

Raw telemetry provides valuable information but rarely offers actionable operational insight.

Engineers must manually correlate multiple data sources before identifying operational problems and determining appropriate responses.

Project Sentinel aims to transform operational data into structured operational knowledge rather than acting solely as a telemetry storage platform.

---

# Decision

Project Sentinel adopts a structured Operational Processing Model.

Operational information progresses through a series of increasingly valuable business concepts.

```text
Telemetry

↓

Signals

↓

Events

↓

Alerts

↓

Incidents

↓

Knowledge
```

Each stage enriches, organizes, and contextualizes operational information.

Higher-level concepts never replace lower-level data.

Instead, they build upon it.

---

# Processing Pipeline

The Operational Processing Model consists of six stages.

| Stage | Responsibility |
|--------|----------------|
| Telemetry | Raw operational observations |
| Signals | Normalized runtime information |
| Events | Business-relevant operational occurrences |
| Alerts | Conditions requiring attention |
| Incidents | Managed operational problems |
| Knowledge | Organizational operational experience |

Each stage introduces additional meaning while preserving traceability.

---

# Telemetry

Telemetry represents raw information received from applications, infrastructure, and integrations.

Examples include:

- Exceptions
- Logs
- Metrics
- Traces
- Deployment notifications
- Runtime diagnostics

Telemetry is immutable.

It represents observed facts.

---

# Signals

Signals normalize telemetry into a consistent internal representation.

Signals remove technology-specific differences while preserving operational meaning.

Examples include:

- Error Signal
- Performance Signal
- Deployment Signal
- Availability Signal
- Infrastructure Signal

Signals create a common operational language across supported technologies.

---

# Events

Events represent significant operational occurrences within the platform.

Events are business concepts rather than raw technical observations.

Examples include:

- Deployment Completed
- Error Threshold Exceeded
- Service Degraded
- Database Connection Lost
- Queue Processing Delayed

Events are immutable and form the foundation of the platform's Event Model.

---

# Alerts

Alerts identify operational situations that require attention.

Alerts are created by evaluating Events against business rules, thresholds, and organizational policies.

Alerts represent operational awareness rather than confirmed operational problems.

---

# Incidents

Incidents represent managed operational problems.

Incidents aggregate related Alerts, Events, evidence, investigations, and engineering actions.

Incidents remain active until resolved through the Incident Management process.

---

# Knowledge

Resolved Incidents contribute to organizational operational knowledge.

Knowledge captures:

- Root causes
- Resolutions
- Operational patterns
- Lessons learned
- Best practices

Knowledge improves future operational investigations and Agent recommendations.

---

# Rationale

The Operational Processing Model separates observation from interpretation.

Each stage has a single responsibility.

This separation improves:

- Explainability
- Traceability
- Reusability
- Automation
- Operational consistency

The model transforms operational data into actionable engineering knowledge.

---

# Consequences

Positive consequences include:

- Clear separation of responsibilities.
- Improved operational visibility.
- Consistent Event Model.
- Better Incident Management.
- Strong foundation for Agent reasoning.
- Knowledge accumulation over time.
- Technology-independent operational processing.

Trade-offs include:

- Additional processing stages.
- Increased architectural complexity.
- More business concepts to model and maintain.

These trade-offs are accepted because they produce a significantly more valuable operational platform.

---

# Alternatives Considered

## Store Raw Telemetry Only

Persist telemetry without additional processing.

Rejected because engineers remain responsible for manual correlation and interpretation.

---

## Generate Alerts Directly from Telemetry

Create Alerts immediately from incoming telemetry.

Rejected because raw telemetry lacks sufficient business context and normalization.

---

## Treat Events and Alerts as the Same Concept

Use a single model for Events and Alerts.

Rejected because Events represent operational facts, while Alerts represent operational decisions requiring attention.

Separating the concepts improves clarity and flexibility.

---

# Architectural Principles

The Operational Processing Model follows these principles.

- Raw observations remain immutable.
- Every processing stage adds meaning without destroying information.
- Business concepts are separated by responsibility.
- Operational knowledge is accumulated rather than discarded.
- Every higher-level concept remains traceable to its originating telemetry.

---

# Related Documents

- `architecture/02-domain-architecture.md`
- `architecture/04-data-flow.md`
- `architecture/05-event-model.md`
- `architecture/07-agent-architecture.md`

---

# Related ADRs

- `ADR-001-product-positioning.md`
- `ADR-003-multi-tenancy.md`
- `ADR-005-domain-driven-design.md`
- `ADR-011-canonical-event-model.md`