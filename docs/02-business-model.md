# Business Model

**Version:** 0.1 (Draft)  
**Status:** Draft  
**Document:** `docs/02-business-model.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 30, 2026

---

# Executive Summary

Project Sentinel is an Operational Intelligence Platform that enables engineering teams to identify, understand, and resolve production incidents faster.

Rather than competing directly with traditional observability platforms by providing another logging or monitoring solution, Sentinel focuses on reducing the time between receiving an alert and identifying the root cause.

The long-term vision is to become the operational control center for modern software applications.

---

# Purpose

This document defines the commercial strategy behind Sentinel.

It explains:

- Why the product exists
- Who it serves
- Why customers will pay
- How the company generates revenue
- How the business scales
- How competitive advantages are created
- How the product evolves over time

This document intentionally focuses on business strategy rather than implementation details.

---

# The Problem We Solve

Engineering teams operate increasingly complex production environments.

A single production incident often requires engineers to investigate data across multiple independent systems.

Typical investigation workflow:

- Monitoring dashboards
- Log aggregation
- Exception tracking
- Queue monitoring
- Deployment history
- Infrastructure dashboards
- Cloud providers
- Team communication platforms

The largest cost is rarely infrastructure.

The largest cost is engineering time spent understanding what happened.

Every additional minute increases:

- Customer impact
- Revenue loss
- Operational stress
- Recovery time

---

# Our Core Value Proposition

Sentinel helps engineering teams answer four questions faster than any traditional observability platform.

1. What changed?
2. What failed?
3. Why did it fail?
4. What should I investigate next?

Instead of presenting isolated operational signals, Sentinel provides operational context.

Our value is not collecting telemetry.

Our value is reducing uncertainty.

---

# Why Customers Buy Sentinel

Customers do not purchase Sentinel because they need another dashboard.

Customers purchase Sentinel because they want to reduce operational risk.

Primary motivations include:

- Faster incident response
- Reduced downtime
- Lower operational costs
- Increased deployment confidence
- Better developer productivity
- Simplified production operations

---

# Target Market

## Primary Market

Small and medium-sized software companies with:

- 5–50 engineers
- One or more production environments
- Frequent software deployments
- Limited DevOps resources
- High operational responsibility

These organizations experience operational complexity but often lack dedicated platform engineering teams.

---

## Secondary Market

- Digital agencies
- Managed hosting providers
- Enterprise engineering teams
- DevOps consulting companies

---

# Ideal Customer Profile (ICP)

A company that:

- Builds software as its core business
- Deploys multiple times per week
- Operates production APIs
- Has experienced production incidents
- Values developer productivity
- Prefers modern engineering practices
- Uses Laravel initially, with future expansion to additional runtimes

---

# Economic Buyer

The person approving the purchase may not be the daily user.

Potential buyers include:

- CTO
- VP of Engineering
- Engineering Manager
- Founder
- Technical Lead

They care about:

- Downtime
- Operational efficiency
- Engineering cost
- Customer satisfaction
- Team scalability

---

# Primary Users

Daily users include:

- Backend Engineers
- Platform Engineers
- DevOps Engineers
- Site Reliability Engineers (SRE)
- On-call Engineers

---

# Jobs To Be Done

Customers hire Sentinel to:

- Understand production incidents faster
- Reduce investigation time
- Correlate operational events
- Improve deployment confidence
- Centralize operational knowledge
- Reduce context switching

---

# Existing Alternatives

Today, engineering teams assemble their own operational stack using multiple tools.

Examples include:

- Monitoring platforms
- Log management
- Exception tracking
- Queue dashboards
- Deployment dashboards
- Health monitoring
- Team messaging

Although each tool performs its own function well, engineers remain responsible for correlating information manually.

---

# Why Existing Solutions Are Not Enough

Existing solutions optimize for data visibility.

Sentinel optimizes for operational understanding.

Traditional workflow:

Alert

↓

Open Monitoring

↓

Open Logs

↓

Open Exceptions

↓

Check Deployment

↓

Inspect Queue

↓

Investigate Infrastructure

↓

Guess Root Cause

Sentinel workflow:

Alert

↓

Open Sentinel

↓

Review Incident Timeline

↓

Identify Root Cause

↓

Restore Production

---

# Competitive Advantage

Sentinel's competitive advantage is not another dashboard.

Its advantage is operational intelligence.

Key differentiators:

- Incident-first architecture
- Unified operational timeline
- Automatic signal correlation
- Context-aware investigation
- Operational recommendations
- Extensible plugin ecosystem

---

# Business Strategy

Rather than replacing every operational tool immediately, Sentinel complements existing infrastructure.

Customers should be able to integrate Sentinel into their existing environment with minimal disruption.

Over time, Sentinel becomes the central operational workspace.

This gradual adoption lowers switching costs while increasing long-term customer retention.

---

# Go-To-Market Strategy

## Phase 1

Target Laravel SaaS companies.

Reason:

- Existing expertise
- Faster product development
- Smaller integration surface
- Strong community

---

## Phase 2

Expand runtime support.

Examples:

- Symfony
- Node.js
- Go
- Python
- .NET

---

## Phase 3

Become runtime-agnostic.

Position Sentinel as the operational layer independent of programming language.

---

# Revenue Model

Primary revenue source:

Subscription-based SaaS.

Potential pricing dimensions:

- Number of organizations
- Number of projects
- Active production applications
- Data retention period
- Team size
- Premium operational features

Future revenue opportunities:

- Enterprise licensing
- Self-hosted commercial edition
- Premium plugins
- Marketplace revenue sharing
- Professional services

---

# Customer Expansion Strategy

Initial adoption begins with a single engineering team.

Expansion opportunities include:

- Additional projects
- Additional production environments
- More team members
- Multiple departments
- Multiple business units
- Enterprise-wide deployment

---

# Customer Retention Strategy

Customers remain because Sentinel becomes part of their daily operational workflow.

Retention drivers include:

- Historical incident knowledge
- Operational analytics
- Team workflows
- Custom dashboards
- Plugin ecosystem
- Deep integrations

The higher the operational dependency, the lower the likelihood of churn.

---

# Long-Term Moat

Long-term defensibility will come from:

- Deep operational knowledge
- Incident correlation engine
- Rich historical operational data
- Plugin ecosystem
- Public SDK
- Third-party integrations
- Community contributions
- Strong developer experience

The product becomes increasingly valuable as customers accumulate operational history.

---

# Success Metrics

Business success will be measured using:

## Product Metrics

- Mean Time to Detect (MTTD)
- Mean Time to Resolve (MTTR)
- Daily Active Users
- Weekly Active Organizations
- Incident investigation time

## Business Metrics

- Monthly Recurring Revenue (MRR)
- Annual Recurring Revenue (ARR)
- Customer Acquisition Cost (CAC)
- Lifetime Value (LTV)
- Net Revenue Retention (NRR)
- Churn Rate

---

# Risks

Potential risks include:

- Highly competitive market
- Slow customer adoption
- Expanding scope too early
- Complex integrations
- Large operational data volumes
- Building features before validating demand

These risks will be mitigated through disciplined MVP execution and continuous customer feedback.

---

# Business Principles

Every business decision should satisfy the following principles:

- Solve real operational pain
- Deliver measurable customer value
- Prioritize simplicity
- Earn customer trust
- Build for long-term scalability
- Favor open standards
- Keep switching costs low initially
- Increase customer value over time

---

# Related Documents

- `docs/00-project-charter.md`
- `docs/01-product-positioning.md`
- `docs/03-product-requirements.md`
- `docs/07-roadmap.md`
- `docs/adr/ADR-001-product-positioning.md`

---

# Revision History

| Version | Date | Author | Notes |
|----------|------------|----------|------------------------------|
| 0.1 | 2026-07-30 | Founders | Initial draft |