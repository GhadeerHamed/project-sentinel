# Project Sentinel MVP Scope

**Status:** Accepted
**Last Updated:** 2026-08-01

## 1. MVP Goal

Build the smallest useful version of Sentinel that helps a Laravel team answer:

> **What likely caused this production failure?**

Sentinel will connect application errors with recent deployments and present a clear, evidence-backed incident summary.

---

## 2. Target User

The initial user is:

* a Laravel backend developer;
* a technical lead;
* a small SaaS engineering team;
* a team without a dedicated SRE platform.

---

## 3. MVP Workflow

```text
Laravel application reports errors
        ↓
Sentinel receives and normalizes them
        ↓
Related errors are grouped
        ↓
Recent deployments are attached
        ↓
Sentinel creates an incident summary
        ↓
The engineer confirms the cause and resolution
```

---

## 4. Required MVP Features

### Application Management

* Create an application.
* Define production environments.
* Generate secure ingestion credentials.

### Laravel Integration

* Install a Laravel package.
* Send exceptions and failed-job events.
* Include application, environment, release, route, job, and stack-trace context.

### Event Ingestion

* Receive signed events.
* Validate and normalize payloads.
* Prevent duplicate processing.
* Preserve the original payload.

### Error Grouping

Group repeated errors using:

* exception class;
* normalized message;
* stack-trace fingerprint;
* application;
* environment;
* route or job class.

### Deployment Tracking

Support deployment data through:

* Laravel package events;
* generic deployment webhook;
* GitHub integration later if required.

Store:

* release identifier;
* commit hash;
* branch;
* environment;
* deployment time.

### Incident Creation

Create an incident when related errors exceed a configurable threshold.

An incident should show:

* title;
* severity;
* start time;
* affected application;
* grouped errors;
* event count;
* recent deployments;
* evidence timeline;
* current status.

### Investigation Summary

Generate a summary containing:

* what failed;
* when it started;
* where it failed;
* recent relevant deployment;
* supporting evidence;
* possible cause;
* confidence level;
* recommended investigation steps.

The summary may initially use deterministic rules. AI is optional.

### Human Feedback

The engineer can:

* confirm the cause;
* reject the proposed cause;
* enter the actual cause;
* record the resolution;
* resolve the incident.

### Notifications

Support one initial notification channel:

* Slack webhook.

---

## 5. Explicitly Excluded

The MVP will not include:

* metrics, logs, or trace storage;
* on-call scheduling;
* SMS or phone paging;
* status pages;
* autonomous remediation;
* plugin marketplace;
* multiple AI agents;
* microservices;
* Kubernetes;
* advanced enterprise governance;
* support for frameworks other than Laravel;
* large numbers of external integrations.

---

## 6. Initial Technical Shape

Use:

* Laravel;
* PHP;
* PostgreSQL or MySQL;
* Redis;
* Laravel queues;
* Laravel scheduler;
* Blade or a lightweight frontend;
* Docker Compose for local installation.

Build it as a modular monolith.

---

## 7. Core MVP Modules

```text
Identity and Tenancy
Application Management
Event Ingestion
Error Grouping
Deployment Tracking
Incident Management
Investigation
Notifications
```

---

## 8. MVP Success Criteria

The MVP is successful when it can:

1. Receive real Laravel production errors.
2. Group repeated errors into one operational issue.
3. Attach a relevant deployment.
4. Create an understandable incident timeline.
5. Produce a useful investigation summary.
6. Let an engineer confirm the cause and resolution.
7. Reduce the number of tools needed during investigation.
8. Help investigate at least one real production incident faster.

---

## 9. Strategic Product Sequence

This sequence describes product-value delivery. It does not define repository implementation milestones; those are canonical in `docs/implementation/00-implementation-plan.md`, where Milestone 4 is Project and Application Registry.

### Stage 1 — Foundation

* Laravel project setup;
* authentication;
* tenant support;
* application management;
* environment management.

### Stage 2 — Ingestion

* event API;
* signed requests;
* Laravel package;
* exception ingestion;
* failed-job ingestion.

### Stage 3 — Grouping

* fingerprints;
* duplicate handling;
* error groups;
* occurrence counters.

### Stage 4 — Deployments

* deployment model;
* deployment webhook;
* release association;
* incident deployment evidence.

### Stage 5 — Incidents

* incident creation;
* timeline;
* status lifecycle;
* evidence display;
* cause and resolution recording.

### Stage 6 — Investigation

* deterministic investigation rules;
* probable-cause summary;
* confidence;
* recommended next steps.

### Stage 7 — Validation

* install Sentinel on a real Laravel application;
* process real errors;
* investigate real incidents;
* record missing features;
* remove unnecessary complexity.

---

## 10. MVP Principle

> **Build one complete incident-investigation workflow before expanding the platform.**
