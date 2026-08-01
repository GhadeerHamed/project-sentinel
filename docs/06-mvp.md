# Minimum Viable Product (MVP)

**Version:** 0.1  
**Status:** Draft (In Progress)  
**Document:** `docs/06-mvp.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 30, 2026

---

# Purpose

This document defines the scope of the first public release of Project Sentinel.

The objective of the MVP is not to build a complete operations platform.

The objective is to validate the core product hypothesis:

> Engineering teams can investigate and resolve production incidents significantly faster when operational signals are automatically transformed into operational context.

Every feature included in the MVP must directly support this hypothesis.

---

# Success Criteria

The MVP will be considered successful if it demonstrates the following outcomes:

- Engineers can investigate incidents from a single workspace.
- Operational context reduces investigation effort.
- Teams adopt Sentinel alongside their existing tooling.
- Organizations are willing to pay for the product.
- Early customers actively use Sentinel during production incidents.

---

# Core MVP Capabilities

## 1. Incident Management

### Included

- Automatic incident creation
- Incident lifecycle management
- Incident status tracking
- Incident timeline
- Incident resolution
- Incident reopening

### Excluded

- Incident assignment
- SLA management
- Escalation policies
- Incident templates

---

## 2. Operational Context

### Included

- Correlated operational signals
- Deployment context
- Environment context
- Application context
- Historical operational events

### Excluded

- AI-generated context
- Predictive analysis
- Automatic remediation

---

## 3. Investigation Workspace

### Included

- Investigation timeline
- Investigation notes
- Root cause recording
- Resolution recording

### Excluded

- Collaborative editing
- Incident chat
- Team mentions

---

## 4. Operational Knowledge

### Included

- Historical incident archive
- Search previous incidents
- Investigation history
- Resolution history

### Excluded

- AI recommendations
- Runbook generation
- Knowledge scoring

---

## 5. Notifications

### Included

- New incident notifications
- Incident resolved notifications

### Excluded

- Escalation workflows
- Advanced routing
- Notification scheduling

---

## 6. Organization Management

### Included

- Organizations
- Projects
- Applications
- Environments
- User management

### Excluded

- Enterprise administration
- SSO
- SCIM
- Advanced RBAC

---

# MVP Workflow

The MVP supports the following operational workflow:

```text
Operational Signal

↓

Alert

↓

Incident

↓

Operational Context

↓

Investigation

↓

Root Cause

↓

Resolution

↓

Historical Knowledge
```

Every feature included in the MVP supports one or more stages of this workflow.

---

# Explicitly Out of Scope

The following capabilities are intentionally excluded from Version 1.0.

## Operations

- Feature Flags
- Secrets Management
- Remote Commands
- Deployment Management
- Environment Variables
- Scheduler Management
- Queue Management Dashboard

---

## Infrastructure

- Kubernetes Management
- Docker Management
- Cloud Infrastructure
- Server Provisioning

---

## AI

- AI Investigation
- AI Root Cause Analysis
- AI Chat Assistant
- Automatic Incident Resolution

---

## Enterprise

- Multi-region deployments
- Audit exports
- Enterprise SSO
- Custom compliance modules

---

# Release Criteria

Version 1.0 may be released when the following conditions are satisfied.

## Product

- All MVP capabilities are complete.
- All critical user stories are implemented.
- Product documentation is complete.

---

## Engineering

- Critical defects resolved.
- Security review completed.
- Performance validated.
- Backup and recovery verified.

---

## Customer Experience

- Onboarding completed.
- Documentation published.
- Installation documented.
- Initial integrations available.

---

# MVP Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope expansion | High | Strict feature prioritization |
| Weak product differentiation | High | Focus on operational context |
| Complex onboarding | Medium | Minimize required configuration |
| Poor incident correlation | High | Favor accuracy over automation |

---

# Exit Criteria

The MVP phase ends when:

- The core product hypothesis has been validated.
- Initial customers are successfully using Sentinel.
- Feedback has been collected from real production environments.
- The roadmap for Version 2.0 has been prioritized based on customer evidence.

At that point, Sentinel transitions from product validation to product expansion.