# ADR-012 — Evidence-Based Operational Intelligence

**Status:** Accepted

**Date:** August 1, 2026

**Decision Makers:** Founders

---

# Context

Traditional observability platforms present operational data and rely on engineers to interpret it.

Many modern AI-powered tools generate recommendations directly from prompts or isolated operational information.

Both approaches have limitations.

Raw telemetry lacks operational context.

Prompt-driven reasoning often produces recommendations that are difficult to verify, explain, or reproduce.

Project Sentinel aims to produce operational intelligence that is explainable, evidence-based, and traceable.

Operational recommendations should be derived from observed facts, contextual knowledge, and explicit reasoning rather than isolated prompts.

---

# Decision

Project Sentinel adopts an Evidence-Based Operational Intelligence model.

Every recommendation produced by the platform shall originate from observable operational evidence.

Evidence is enriched with organizational and operational context before any reasoning occurs.

Reasoning produces findings that are transformed into recommendations accompanied by explanations and supporting evidence.

---

# Operational Intelligence Pipeline

Operational reasoning follows a consistent pipeline.

```text
Operational Evidence

↓

Operational Context

↓

Analysis

↓

Findings

↓

Confidence Assessment

↓

Recommendation

↓

Explanation
```

Every stage contributes additional understanding while preserving traceability.

---

# Evidence

Evidence represents observable operational facts.

Examples include:

- Events
- Alerts
- Incidents
- Metrics
- Logs
- Traces
- Deployment history
- Configuration
- Audit records
- Historical operational knowledge

Evidence is objective.

Evidence is never inferred.

---

# Context

Context gives meaning to evidence.

Examples include:

- Organization policies
- Application architecture
- Environment
- Service dependencies
- Historical incidents
- Previous resolutions
- Operational schedules
- Deployment history
- Team ownership

Context enables accurate interpretation of evidence.

---

# Analysis

Analysis evaluates evidence within its operational context.

Analysis may use:

- Deterministic rules
- Statistical methods
- Pattern recognition
- Machine learning
- Large language models
- Hybrid reasoning

The reasoning technique is an implementation decision.

The architectural process remains consistent.

---

# Findings

Findings are conclusions supported by evidence.

Examples include:

- Probable root cause
- Service degradation pattern
- Deployment correlation
- Infrastructure dependency
- Repeating operational behavior

Every finding should reference the evidence supporting it.

---

# Confidence Assessment

Recommendations include an explicit confidence assessment.

Confidence may consider:

- Evidence quality
- Evidence completeness
- Historical similarity
- Model confidence
- Rule certainty
- Context completeness

Confidence supports informed engineering decisions.

---

# Recommendations

Recommendations propose operational actions.

Examples include:

- Restart a service
- Roll back a deployment
- Scale infrastructure
- Investigate a dependency
- Suppress duplicate alerts
- Escalate an Incident

Recommendations assist engineering teams.

They do not replace operational ownership.

---

# Explainability

Every recommendation shall include an explanation.

Explanations describe:

- Supporting evidence
- Relevant context
- Reasoning summary
- Confidence assessment
- Assumptions
- Known limitations

Recommendations that cannot be explained shall not be presented as authoritative operational guidance.

---

# Knowledge Accumulation

Resolved Incidents contribute to organizational operational knowledge.

Knowledge continuously improves future reasoning.

Operational experience becomes a reusable organizational asset rather than remaining individual engineer knowledge.

The platform becomes more valuable as organizational knowledge grows.

---

# Continuous Learning

Operational Intelligence continuously improves through accumulated organizational experience.

As new Incidents are resolved and additional Knowledge is captured:

- Similar operational situations become easier to recognize.
- Recommendations become more precise.
- Confidence assessments become more reliable.
- Duplicate investigations are reduced.

The platform learns from operational history without altering historical evidence.

Historical observations remain immutable.

Knowledge evolves through the addition of new evidence and understanding rather than modification of past events.

---

# Human Oversight

Engineers remain responsible for operational decisions.

The platform recommends.

Engineers decide.

Automation may execute recommendations only after authorization and Policy Evaluation.

---

# Rationale

Evidence-Based Operational Intelligence provides:

- Explainable recommendations.
- Traceable operational reasoning.
- Technology independence.
- Organizational learning.
- Consistent engineering support.
- Responsible AI adoption.
- Improved operational confidence.

The platform reasons from evidence rather than prompts.

---

# Consequences

Positive consequences include:

- Engineers can verify recommendations.
- AI providers become interchangeable.
- Deterministic and AI reasoning coexist.
- Organizational knowledge compounds over time.
- Trust increases through explainability.
- Recommendations improve as operational history grows.

Trade-offs include:

- More sophisticated reasoning infrastructure.
- Evidence collection and correlation requirements.
- Knowledge management complexity.
- Additional computational cost.

These trade-offs are accepted because they create a trustworthy and sustainable operational intelligence platform.

---

# Alternatives Considered

## Prompt-Based Assistance

Generate recommendations directly from prompts and current telemetry.

Rejected because recommendations become difficult to verify, reproduce, and explain.

---

## Rule-Based Automation Only

Rely exclusively on deterministic operational rules.

Rejected because many operational situations require contextual reasoning beyond predefined rules.

---

## Black-Box AI

Delegate operational reasoning entirely to external AI services.

Rejected because recommendations require transparency, governance, and explainability.

Operational reasoning remains a Core platform responsibility regardless of the underlying implementation technology.

---

# Architectural Principles

Evidence-Based Operational Intelligence follows these principles.

- Evidence precedes conclusions.
- Context informs interpretation.
- Findings require supporting evidence.
- Recommendations remain explainable.
- Confidence is explicit.
- Engineers retain operational authority.
- Organizational knowledge compounds over time.
- Reasoning technology is replaceable.

---

# Related Documents

- `architecture/02-domain-architecture.md`
- `architecture/05-event-model.md`
- `architecture/07-agent-architecture.md`
- `architecture/08-security-architecture.md`

---

# Related ADRs

- `ADR-004-operational-processing-model.md`
- `ADR-006-agent-platform.md`
- `ADR-007-event-driven-architecture.md`
- `ADR-008-security-policy-layer.md`
- `ADR-011-canonical-event-model.md`