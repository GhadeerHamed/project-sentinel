# User Stories

**Version:** 0.1  
**Status:** Draft (In Progress)  
**Document:** `docs/05-user-stories.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 30, 2026

---

# Purpose

This document defines the user stories for Project Sentinel.

The stories describe the expected behavior of the product from the perspective of its users. They serve as the foundation for the Software Requirements Specification (SRS), UI/UX design, and implementation planning.

Stories are grouped by product capability rather than user interface.

---

# Story Format

Every user story follows the format:

> **As a** `<persona>`  
> **I want to** `<goal>`  
> **So that** `<business value>`

Each story also includes acceptance criteria.

---

# Epic 1 — Incident Management

## US-101 — Automatic Incident Creation

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want Sentinel to automatically create an incident when related operational signals indicate a production problem,
> so that I can immediately begin investigating.

### Acceptance Criteria

- Incident is automatically created.
- Initial status is assigned.
- Timeline is initialized.
- Related operational signals are linked.

---

## US-102 — View Incident Details

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want to open an incident,
> so that I can understand what is happening.

### Acceptance Criteria

- Incident summary is displayed.
- Current status is visible.
- Severity is visible.
- Timeline is accessible.

---

## US-103 — Update Incident Status

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want to update the incident status,
> so that the team understands the current investigation stage.

### Acceptance Criteria

- Status updates are recorded.
- Timeline reflects status changes.
- Previous values remain in history.

---

## US-104 — Resolve Incident

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want to resolve an incident,
> so that investigation results become part of our operational history.

### Acceptance Criteria

- Resolution summary is required.
- Resolution time is recorded.
- Investigation history is preserved.

---

# Epic 2 — Operational Context

## US-201 — View Correlated Context

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want Sentinel to present correlated operational information,
> so that I do not need to manually gather evidence.

### Acceptance Criteria

- Related operational signals are displayed.
- Context is grouped logically.
- Context updates as new information arrives.

---

## US-202 — View Incident Timeline

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want a chronological timeline,
> so that I can understand the sequence of events.

### Acceptance Criteria

- Events are ordered chronologically.
- Timeline supports investigation.
- Significant changes are highlighted.

---

## US-203 — Identify Recent Changes

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want to see recent operational changes,
> so that I can quickly determine whether they contributed to the incident.

### Acceptance Criteria

- Recent changes are clearly identified.
- Changes are linked to the incident timeline.

---

# Epic 3 — Investigation

## US-301 — Record Investigation Notes

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want to document my investigation,
> so that others can understand my reasoning.

### Acceptance Criteria

- Notes are timestamped.
- Notes are preserved permanently.
- Notes appear in the investigation timeline.

---

## US-302 — Record Root Cause

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want to record the confirmed root cause,
> so that future investigations benefit from previous knowledge.

### Acceptance Criteria

- Root cause can be recorded.
- Root cause remains editable until resolution.
- Final value becomes part of historical knowledge.

---

## US-303 — Record Resolution

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want to document how the incident was resolved,
> so that future engineers can repeat successful solutions.

### Acceptance Criteria

- Resolution summary is stored.
- Resolution becomes searchable.
- Resolution remains linked to the incident.

---

# Epic 4 — Operational Knowledge

## US-401 — Search Historical Incidents

**Persona:** Platform Engineer

**Story**

> As a platform engineer,
> I want to search previous incidents,
> so that recurring problems can be identified quickly.

### Acceptance Criteria

- Historical incidents are searchable.
- Results include summaries.
- Results link to full investigations.

---

## US-402 — Review Investigation History

**Persona:** Engineering Manager

**Story**

> As an engineering manager,
> I want to review completed investigations,
> so that I can identify improvement opportunities.

### Acceptance Criteria

- Closed incidents remain accessible.
- Investigation timeline is preserved.
- Resolution summary is available.

---

# Epic 5 — Notifications

## US-501 — Receive Incident Notification

**Persona:** On-Call Software Engineer

**Story**

> As an on-call engineer,
> I want to be notified when a new incident is created,
> so that I can begin investigating immediately.

### Acceptance Criteria

- Notification is delivered.
- Notification references the incident.
- Notification includes priority information.

---

## US-502 — Receive Resolution Notification

**Persona:** Engineering Manager

**Story**

> As an engineering manager,
> I want to know when incidents are resolved,
> so that I remain informed about production health.

### Acceptance Criteria

- Resolution notification is generated.
- Resolution includes investigation summary.

---

# Epic 6 — Administration

## US-601 — Manage Organization

**Persona:** Platform Engineer

**Story**

> As a platform engineer,
> I want to configure my organization,
> so that Sentinel reflects our operational structure.

### Acceptance Criteria

- Organization settings are configurable.
- Changes are validated.
- Changes are auditable.

---

## US-602 — Manage Applications

**Persona:** Platform Engineer

**Story**

> As a platform engineer,
> I want to register and manage applications,
> so that operational information is organized correctly.

### Acceptance Criteria

- Applications can be created.
- Applications can be updated.
- Applications can be archived.

---

# Story Traceability

| Epic | Capability | Related Personas |
|------|------------|------------------|
| Incident Management | Incident Lifecycle | On-Call Engineer |
| Operational Context | Context Engine | On-Call Engineer |
| Investigation | Investigation Workspace | On-Call Engineer |
| Operational Knowledge | Knowledge Base | Platform Engineer, Engineering Manager |
| Notifications | Communication | On-Call Engineer, Engineering Manager |
| Administration | Platform Configuration | Platform Engineer |

---

# Definition of Done

A user story is considered complete when:

- Functional requirements are satisfied.
- Acceptance criteria pass.
- Security requirements are met.
- Audit requirements are met.
- Documentation is updated.
- Tests are completed.