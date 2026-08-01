# Architecture Decision Record 001

**Document:** `docs/adr/ADR-001-product-positioning.md`

**ADR:** 001  
**Status:** Accepted  
**Date:** July 30, 2026  
**Decision Makers:** Founders

---

# Title

Adopt an Incident Intelligence Architecture Instead of a Traditional Observability Platform

---

# Status

Accepted

---

# Context

Project Sentinel aims to become the operational control center for modern applications.

During the product discovery phase, several possible product directions were evaluated, including:

- Log aggregation
- Monitoring platform
- Metrics platform
- APM
- Queue management
- Laravel operations dashboard

Each of these markets already contains mature competitors and would position Sentinel as another observability product.

Observability products typically expose operational data and rely on engineers to manually correlate information across multiple dashboards before identifying the root cause of an incident.

This workflow creates unnecessary context switching and increases incident response time.

---

# Decision

Project Sentinel will be designed as an **Operational Intelligence Platform** centered around **Incident Intelligence**.

Instead of treating logs, metrics, exceptions, queues, deployments, and health checks as independent products, Sentinel will treat them as operational signals that contribute to a higher-level domain object: the **Incident**.

The Incident becomes the primary entity around which the platform is designed.

---

# Rationale

Engineering teams do not purchase observability tools because they want more dashboards.

They purchase them because they need to restore production systems faster.

The value delivered by Sentinel is not the collection of telemetry.

The value is the automatic transformation of telemetry into operational understanding.

The platform should answer questions such as:

- What changed?
- What failed?
- Why is it failing?
- Which services are affected?
- What should I investigate next?

instead of simply displaying raw data.

---

# Alternatives Considered

## Alternative 1

Traditional Monitoring Platform

Rejected because the market is mature and highly competitive.

Examples include Datadog, Grafana and New Relic.

---

## Alternative 2

Laravel Operations Dashboard

Rejected because the long-term vision includes multiple runtimes.

A Laravel-specific architecture would limit future expansion.

---

## Alternative 3

Centralized Logging Platform

Rejected because logs represent only one operational signal.

Sentinel should correlate multiple signals instead of specializing in one.

---

# Consequences

This decision affects multiple architectural areas.

## Domain Model

The Incident becomes the primary operational entity.

Logs, metrics, exceptions, deployments and health checks become supporting entities.

---

## Event System

All operational events should be normalized into a common event model before correlation.

---

## Dashboard

The primary dashboard will present active incidents rather than isolated telemetry.

---

## Database

The schema will prioritize event correlation and incident aggregation.

Telemetry storage is important but not the primary business model.

---

## APIs

Public APIs should expose incidents and operational context rather than only raw events.

---

## Plugin System

Plugins should contribute operational signals that can participate in incident correlation.

Plugins should not bypass the correlation engine.

---

## Future AI

Future AI capabilities should operate on incidents rather than individual logs or metrics.

This creates a significantly richer context for automated analysis.

---

# Risks

- Incident correlation is technically more complex than simple telemetry collection.
- Incorrect correlations could reduce user trust.
- Correlation algorithms will require continuous improvement as the platform evolves.

These risks are acceptable because they directly support the product's primary value proposition.

---

# Success Criteria

This decision is considered successful if Sentinel enables engineering teams to:

- Reduce Mean Time to Detect (MTTD)
- Reduce Mean Time to Resolve (MTTR)
- Investigate incidents from a single interface
- Minimize context switching during production failures

---

# Related Documents

- `docs/00-project-charter.md`
- `docs/01-product-positioning.md`
- `docs/02-business-model.md`
- `docs/03-product-requirements.md`

---

# Revision History

| Version | Date | Author | Notes |
|----------|------|--------|-------|
| 1.0 | 2026-07-30 | Founders | Initial accepted decision |