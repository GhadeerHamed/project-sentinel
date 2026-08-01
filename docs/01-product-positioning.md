# Product Positioning

**Version:** 0.1 (Draft)  
**Status:** Draft  
**Document:** `01-product-positioning.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 30, 2026

---

# Purpose

This document defines the strategic positioning of Project Sentinel.

It answers the fundamental business questions before product requirements and software architecture are designed.

A clear positioning ensures that every future product decision, architectural decision, and roadmap item supports a single business objective.

---

# Vision Statement

> **Project Sentinel is the Operational Intelligence Platform that helps engineering teams understand and resolve production incidents in minutes instead of hours.**

Sentinel is not another monitoring dashboard.

Sentinel is not another logging platform.

Sentinel is not another metrics system.

Sentinel transforms operational signals into operational understanding.

---

# Product Category

Sentinel belongs to a category we define as:

> **Operational Intelligence Platform**

An Operational Intelligence Platform collects operational data from multiple sources, correlates those signals into meaningful context, identifies likely causes, and guides engineering teams toward faster incident resolution.

Unlike traditional observability tools, the platform focuses on **understanding** rather than simply **displaying data**.

---

# Positioning Statement

> **For engineering teams operating production applications, Sentinel is an Operational Intelligence Platform that reduces the time from alert to root cause by automatically correlating operational events into actionable incidents. Unlike traditional monitoring and observability tools that present isolated signals, Sentinel provides operational context, likely causes, and guided investigation from a single interface.**

---

# Product Promise

Our primary promise to customers is simple:

> **From alert to root cause in under five minutes.**

Every feature included in Sentinel should contribute directly or indirectly to achieving this promise.

If a feature does not improve incident detection, investigation, understanding, or resolution, it should not be included in the MVP.

---

# The Core Problem

Modern production environments are fragmented.

When production fails, engineers often switch between multiple tools to answer basic questions such as:

- What changed?
- What is failing?
- When did it start?
- Which deployment introduced the issue?
- Which services are affected?
- Which users are impacted?
- What should we investigate next?

This fragmented workflow increases:

- Mean Time to Detect (MTTD)
- Mean Time to Resolve (MTTR)
- Engineering stress during incidents
- Operational costs
- Customer downtime

---

# The Enemy

The primary competitor is **not** another software product.

The enemy is:

> **Context switching during production incidents.**

Engineers commonly jump between:

- Log management systems
- Metrics dashboards
- Queue monitoring
- Deployment history
- Error tracking
- Infrastructure dashboards
- SSH sessions
- Team chat

Much of the incident response time is spent collecting information rather than solving the problem.

Sentinel aims to eliminate that fragmentation.

---

# Target Customer

## Primary Customer

Small to medium engineering teams that:

- Build SaaS products
- Deploy frequently
- Operate Laravel applications
- Have between 5 and 50 developers
- Have limited DevOps resources
- Maintain one or more production environments

These teams experience operational pain but often lack a dedicated platform engineering team.

---

## Secondary Customers

- Digital agencies managing multiple applications
- Enterprise internal engineering teams
- Managed hosting providers
- DevOps consulting companies

---

# Trigger Event

Customers purchase Sentinel after experiencing operational pain.

Typical trigger events include:

- A production outage
- Failed deployments
- Slow incident investigations
- Repeated production failures
- Increasing operational complexity
- Growing engineering teams
- Multiple applications becoming difficult to manage

---

# Unique Value Proposition

Sentinel does not simply collect telemetry.

Sentinel explains production incidents.

Instead of presenting isolated dashboards, Sentinel builds operational context by correlating:

- Exceptions
- Logs
- Metrics
- Queue activity
- Scheduler execution
- Health checks
- Deployments
- Infrastructure events

The result is a unified operational timeline that accelerates investigation.

---

# Product Philosophy

Sentinel follows four operational stages.

## 1. Observe

Collect operational signals.

Examples:

- Logs
- Metrics
- Exceptions
- Queues
- Scheduler
- Health checks

---

## 2. Understand

Transform raw signals into operational context.

Examples:

- Timeline reconstruction
- Event correlation
- Dependency mapping
- Service relationships
- Incident grouping

---

## 3. Diagnose

Identify the most likely cause of an incident.

Examples:

- Deployment correlation
- Queue bottlenecks
- Database issues
- Infrastructure failures
- Service degradation

---

## 4. Act

Help engineers restore production quickly.

Examples:

- Operational runbooks
- Restart workers
- Execute safe remote actions
- Send notifications
- Trigger recovery workflows

---

# First-Year Scope

The first year of Sentinel focuses on becoming the fastest way to answer four questions:

1. What changed?
2. What failed?
3. Why is it failing?
4. What should I investigate next?

Sentinel will intentionally avoid becoming a general-purpose DevOps platform during the early stages.

---

# Long-Term Vision

Once the platform consistently solves incident investigation, Sentinel can expand into adjacent operational capabilities, including:

- Deployment management
- Feature flags
- Environment management
- Secrets management
- Operational automation
- Incident workflows
- Plugin marketplace
- Public APIs
- SDKs
- Multi-runtime support
- AI-assisted operational intelligence

These capabilities will extend the platform without changing its core mission.

---

# Success Metrics

Sentinel should measurably improve:

- Mean Time to Detect (MTTD)
- Mean Time to Resolve (MTTR)
- Incident investigation time
- Operational visibility
- Deployment confidence
- Developer productivity

---

# Product Principles

Every feature should satisfy the following principles:

- Customer value first
- Developer experience first
- Security by default
- API-first architecture
- Extensible plugin ecosystem
- Cloud and self-hosted support
- Operational simplicity
- Scalability by design

---

# Features Explicitly Excluded from the MVP

The MVP will **not** include:

- Kubernetes management
- Infrastructure provisioning
- CI/CD orchestration
- Distributed tracing
- AI-generated root cause analysis
- Enterprise compliance features
- Marketplace
- Billing platform
- Multi-cloud orchestration

These features may be introduced after achieving product-market fit.

---

# Decision Rationale

The product is intentionally positioned around **incident intelligence** rather than **observability**.

Observability tools provide information.

Sentinel provides understanding.

This positioning differentiates the platform from traditional monitoring solutions while creating a natural path toward future operational automation.

---

# Related Documents

- `docs/00-project-charter.md`
- `03-product-requirements.md`
- `02-business-model.md`
- `ADR/ADR-001-product-positioning.md`