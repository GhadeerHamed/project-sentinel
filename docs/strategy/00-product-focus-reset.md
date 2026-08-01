# Project Sentinel Product Focus Reset

**Document:** `docs/strategy/00-product-focus-reset.md`
**Status:** Accepted
**Decision Type:** Product Strategy
**Audience:** Product, Engineering, Architecture, Design, and Future Contributors
**Last Updated:** 2026-08-01

---

## 1. Purpose

This document formally narrows the initial scope of Project Sentinel.

Project Sentinel was originally designed as a broad enterprise operational-intelligence platform covering:

* event ingestion;
* alert management;
* incident management;
* operational knowledge;
* plugins;
* artificial-intelligence agents;
* automation;
* operational workflows;
* multi-tenancy;
* enterprise governance;
* self-hosted deployment;
* cloud SaaS deployment.

That long-term vision remains valid.

However, attempting to implement the entire platform before validating a focused customer problem would create excessive cost, complexity, and execution risk.

Project Sentinel will therefore begin with a deliberately narrow product:

> **A self-hosted incident-intelligence tool that helps Laravel and SaaS engineering teams understand why production started failing.**

The initial product will connect operational alerts, application errors, deployments, and existing knowledge to produce an evidence-backed incident investigation.

---

## 2. Decision

Project Sentinel will use a **focused-entry, platform-later strategy**.

The project will not initially attempt to become a complete replacement for:

* observability platforms;
* paging and on-call platforms;
* IT service-management platforms;
* log-management platforms;
* application-performance monitoring platforms;
* status-page products;
* enterprise configuration-management databases;
* workflow-automation platforms.

Instead, Sentinel will operate as an intelligence layer above the tools a team already uses.

The initial release will focus on one central outcome:

> **Reduce the time required to understand the probable cause of a Laravel production incident.**

---

## 3. Product Thesis

Modern SaaS teams already collect large amounts of operational information through tools such as:

* application logs;
* Laravel exception handlers;
* Sentry;
* Grafana;
* Prometheus;
* GitHub;
* GitLab;
* deployment pipelines;
* queue monitoring;
* infrastructure monitoring;
* Slack;
* ticketing systems.

The main problem is not always the absence of data.

The problem is that the information is fragmented.

During an incident, an engineer may need to manually determine:

* what changed;
* when the failure started;
* which service or component is affected;
* whether a recent deployment is related;
* whether the same issue happened previously;
* which customers are affected;
* which queue, database, cache, API, or dependency is involved;
* what investigation step should happen next.

Sentinel will collect and connect this evidence into a single operational investigation.

---

## 4. Initial Product Definition

The first usable version of Sentinel will:

1. Receive operational signals from Laravel applications and external systems.
2. Normalize those signals into a consistent internal event model.
3. group duplicate or related signals into alerts.
4. Correlate related alerts into an incident candidate.
5. Attach relevant deployment, application, service, and historical context.
6. Produce an evidence-backed incident summary.
7. Recommend investigation steps.
8. Notify the engineering team.
9. Allow a human operator to confirm or reject the proposed cause.
10. Store the confirmed cause and resolution as reusable operational knowledge.

The initial product is not intended to autonomously operate production systems.

It is intended to help engineers understand incidents faster and make better decisions.

---

## 5. Initial Target Customer

The initial target customer is:

> **A small or medium SaaS engineering team operating one or more Laravel applications without a dedicated enterprise Site Reliability Engineering platform.**

Typical characteristics include:

* 2–30 engineers;
* Laravel as a significant part of the backend stack;
* production deployments several times per week;
* use of GitHub or GitLab;
* use of common monitoring, logging, or error-tracking tools;
* production issues investigated manually;
* operational knowledge distributed across people, tickets, chat messages, and documents;
* limited budget for enterprise AIOps products;
* preference for self-hosting or infrastructure control;
* no dedicated 24-hour operations center.

The initial user is likely to be:

* a backend engineer;
* a technical lead;
* a DevOps engineer;
* a platform engineer;
* an engineering manager;
* a founder responsible for production operations.

---

## 6. Primary User Problem

The primary problem is:

> When production begins failing, Laravel and SaaS teams spend too much time manually connecting errors, deployments, logs, alerts, and historical knowledge before they can identify the probable cause.

The first version of Sentinel must solve this problem better than manually opening several dashboards and communication tools.

---

## 7. Primary Product Promise

The initial product promise is:

> **Understand the probable cause of a Laravel production incident in five minutes or less.**

This promise does not mean Sentinel must always determine the definitive root cause automatically.

It means Sentinel should produce a useful and traceable investigation package containing:

* the incident start time;
* the affected application or component;
* grouped errors and alerts;
* recent deployments;
* relevant code changes when available;
* affected queues, jobs, endpoints, or dependencies;
* similar previous incidents;
* evidence supporting each hypothesis;
* uncertainty and confidence information;
* recommended next investigation steps.

---

## 8. Example User Experience

A Laravel application begins producing checkout failures shortly after a deployment.

Sentinel receives:

* repeated application exceptions;
* an increase in HTTP 500 responses;
* failed payment queue jobs;
* the latest deployment metadata;
* the associated Git commit;
* service and environment information.

Sentinel correlates the signals and produces:

```text
Incident: Checkout failures increased

Status:
Investigating

Started:
2026-08-01 14:08 UTC

Affected component:
PaymentService

Probable cause:
Deployment 8f31a2 introduced an incompatible payment payload change.

Evidence:
- Checkout errors began four minutes after deployment 8f31a2.
- 93% of the failures originate from PaymentService.
- Failed queue jobs contain the same serialization exception.
- Database latency and infrastructure health remain normal.
- A similar error occurred in incident INC-0042.

Confidence:
82%

Recommended next steps:
1. Review the payment serialization changes in deployment 8f31a2.
2. Compare the current payload with the payment provider schema.
3. Consider rolling back deployment 8f31a2.
```

An engineer can then:

* confirm the probable cause;
* reject the hypothesis;
* add investigation evidence;
* record the resolution;
* link a runbook;
* mark the incident as resolved.

The confirmed information becomes operational knowledge for future incidents.

---

## 9. Initial Capability Scope

### 9.1 Signal Ingestion

The initial product must support a limited number of high-value ingestion methods:

* generic signed webhooks;
* Laravel SDK or package integration;
* Sentry webhook ingestion;
* GitHub deployment and commit information;
* GitLab deployment and commit information;
* manual incident creation.

Additional integrations should be added only when demanded by real use cases.

### 9.2 Event Normalization

Incoming signals must be converted into a standard event structure containing:

* tenant;
* source;
* environment;
* application;
* service or component;
* event type;
* severity;
* timestamp;
* fingerprint;
* attributes;
* external reference;
* raw source payload;
* ingestion metadata.

### 9.3 Alert Grouping

Sentinel must reduce repeated operational noise by grouping equivalent or closely related events.

Initial grouping may use:

* source fingerprints;
* exception class;
* error message;
* stack trace;
* application;
* environment;
* endpoint;
* queue;
* job type;
* time window.

The system must preserve the original events even when they are grouped.

### 9.4 Incident Correlation

Sentinel must identify when multiple alerts probably represent one operational incident.

Initial correlation should remain deterministic and explainable.

Correlation signals may include:

* shared application or service;
* shared environment;
* temporal proximity;
* related exception signatures;
* shared deployment;
* shared endpoint;
* shared queue;
* shared infrastructure dependency;
* previously confirmed relationships.

Artificial intelligence may assist correlation, but the core system must not depend entirely on opaque model output.

### 9.5 Deployment Intelligence

Deployment information is a critical differentiator for the initial product.

Sentinel should be able to associate incidents with:

* deployment time;
* application;
* environment;
* Git commit;
* branch;
* author;
* pull request or merge request;
* changed files;
* release identifier;
* rollback information.

Sentinel must never claim that a deployment caused an incident solely because the deployment occurred recently.

Temporal proximity is evidence, not proof.

### 9.6 Evidence-Backed Investigation

Each proposed explanation must include:

* the hypothesis;
* supporting evidence;
* contradicting evidence when available;
* confidence;
* evidence sources;
* relevant timestamps;
* recommended investigation actions.

Users must be able to inspect why Sentinel reached a conclusion.

### 9.7 Human Confirmation

A human operator must be able to:

* confirm a probable cause;
* reject a probable cause;
* modify the cause;
* add evidence;
* record an action;
* record the resolution;
* provide feedback about a recommendation.

Human confirmation is part of the product intelligence loop.

### 9.8 Operational Knowledge

Confirmed incidents should create reusable knowledge containing:

* symptoms;
* affected components;
* confirmed cause;
* resolution;
* useful investigation steps;
* related deployments;
* linked runbooks;
* incident tags;
* time to detection;
* time to understanding;
* time to resolution.

Future incidents should retrieve this knowledge when relevant.

### 9.9 Notification

The initial product should support a limited notification surface:

* Slack;
* email;
* in-application notifications;
* generic outbound webhook.

Sentinel does not need to provide full paging and on-call scheduling during the initial phase.

---

## 10. Explicitly Deferred Capabilities

The following capabilities remain part of the long-term architecture but are not initial product requirements.

### 10.1 Full Observability

Sentinel will not initially provide:

* native distributed tracing;
* large-scale metric storage;
* complete log aggregation;
* infrastructure monitoring;
* real-user monitoring;
* synthetic monitoring;
* application-performance monitoring.

Sentinel may consume information from systems that provide these capabilities.

### 10.2 On-Call Management

Sentinel will not initially implement:

* on-call schedules;
* rotations;
* escalation policies;
* phone calls;
* SMS paging;
* acknowledgement deadlines;
* paging-provider replacement.

Existing tools may be integrated later.

### 10.3 Public Status Pages

Public and customer-facing status pages are deferred.

Internal incident status may still be represented in the system.

### 10.4 Enterprise CMDB

Sentinel will not initially provide a complete configuration-management database.

A lightweight application and service catalog may be implemented only where necessary for incident context.

### 10.5 Autonomous Remediation

Sentinel will not initially execute high-risk production changes without human approval.

Deferred autonomous actions include:

* production rollback;
* infrastructure scaling;
* container restarts;
* database failover;
* feature-flag changes;
* destructive queue operations;
* deployment cancellation.

The architecture may support approved actions later, but initial recommendations remain advisory.

### 10.6 Plugin Marketplace

The initial release does not require:

* a public plugin marketplace;
* third-party plugin billing;
* plugin ratings;
* plugin discovery;
* marketplace governance;
* commercial plugin distribution.

A controlled internal integration mechanism may still be used.

### 10.7 Complex Multi-Agent Orchestration

The initial release will not require a network of autonomous AI agents.

AI capabilities should begin with focused operations such as:

* incident summarization;
* evidence classification;
* similar-incident retrieval;
* hypothesis generation;
* recommended investigation steps.

### 10.8 Microservices

The initial implementation will be a modular monolith.

Microservices may be introduced only when demonstrated operational or organizational requirements justify them.

### 10.9 Full Enterprise Governance

Advanced enterprise capabilities are deferred, including:

* complex data residency;
* cross-region active-active operation;
* regulatory policy packs;
* customer-managed encryption keys;
* advanced legal holds;
* highly granular delegated administration;
* enterprise identity federation beyond initial requirements.

Security, tenant isolation, authorization, and auditability remain mandatory from the beginning.

---

## 11. Architecture Implications

The strategic focus does not invalidate the existing enterprise architecture.

It changes implementation priority.

The architecture should continue to preserve:

* domain boundaries;
* framework-independent business logic where practical;
* tenant isolation;
* auditability;
* event-driven internal communication;
* asynchronous processing;
* secure integration boundaries;
* idempotent ingestion;
* traceable automation;
* extensibility;
* replaceable infrastructure adapters.

However, architecture must not force the initial release to implement every possible enterprise capability.

The guiding rule is:

> **Design boundaries for future growth, but implement only the capabilities required to validate the initial product.**

---

## 12. Recommended Initial Bounded Contexts

The first implementation should prioritize the following bounded contexts.

### 12.1 Identity and Tenancy

Responsible for:

* tenants;
* users;
* memberships;
* roles;
* authentication;
* authorization;
* tenant-level settings.

### 12.2 Integration Management

Responsible for:

* integration configuration;
* credentials;
* webhook endpoints;
* connection status;
* source mapping;
* integration health.

### 12.3 Event Intelligence

Responsible for:

* event ingestion;
* normalization;
* validation;
* enrichment;
* fingerprinting;
* deduplication;
* retention.

### 12.4 Alert Management

Responsible for:

* alert creation;
* event grouping;
* severity;
* lifecycle;
* ownership;
* acknowledgement;
* suppression;
* alert evidence.

### 12.5 Incident Intelligence

Responsible for:

* incident candidates;
* alert correlation;
* incident lifecycle;
* investigation;
* hypotheses;
* evidence;
* confidence;
* human confirmation;
* resolution.

### 12.6 Deployment Intelligence

Responsible for:

* deployments;
* commits;
* releases;
* code changes;
* environment associations;
* deployment-to-incident evidence.

### 12.7 Operational Knowledge

Responsible for:

* previous incidents;
* confirmed causes;
* resolutions;
* runbooks;
* known failure patterns;
* knowledge retrieval.

### 12.8 Notification

Responsible for:

* notification policies;
* Slack messages;
* email;
* outbound webhooks;
* delivery status.

---

## 13. Initial Technical Direction

The initial implementation should use:

* Laravel;
* PHP;
* PostgreSQL or MySQL;
* Redis;
* Laravel queues;
* scheduled workers;
* object storage when raw payload retention requires it;
* a modular-monolith architecture;
* REST APIs;
* signed webhooks;
* a web-based management interface.

The exact infrastructure choices must remain proportional to the initial workload.

The project should avoid premature introduction of:

* Kubernetes;
* Kafka;
* service meshes;
* distributed databases;
* dedicated vector databases;
* multiple programming languages;
* independently deployed domain services.

Such technologies may be introduced only when actual requirements justify them.

---

## 14. AI Strategy

AI is an assisting capability, not the product foundation.

The system must remain useful when:

* an AI provider is unavailable;
* the tenant disables AI;
* an AI request times out;
* a model produces invalid output;
* a model has insufficient context;
* AI operating costs become unacceptable.

Deterministic systems should handle:

* ingestion;
* validation;
* deduplication;
* lifecycle transitions;
* permissions;
* audit logging;
* notification delivery;
* explicit correlation rules;
* evidence storage.

AI may assist with:

* summarizing incident evidence;
* comparing an incident with historical incidents;
* generating investigation hypotheses;
* identifying potentially relevant code changes;
* explaining technical evidence;
* recommending investigation steps.

All AI-generated conclusions must be visibly identified and traceable to their supporting evidence.

---

## 15. Product Differentiation

Sentinel should not claim that event correlation, incident management, or AI-assisted operations are unique categories.

Its initial differentiation should be the combination of:

### 15.1 Laravel-First Operational Context

Sentinel should understand Laravel-specific concepts such as:

* exceptions;
* queue jobs;
* failed jobs;
* scheduled commands;
* Artisan commands;
* HTTP requests;
* middleware;
* Eloquent queries;
* database connections;
* cache stores;
* Redis;
* Horizon;
* application environments;
* release identifiers.

### 15.2 Evidence-First Explanations

Sentinel must distinguish between:

* fact;
* correlation;
* hypothesis;
* recommendation;
* confirmed cause.

### 15.3 Vendor Neutrality

Sentinel should complement existing tools rather than require customers to replace them.

### 15.4 Self-Hosting

Teams should be able to run Sentinel within their own infrastructure.

### 15.5 Accessible Pricing

The product should target teams that cannot justify large enterprise AIOps contracts.

### 15.6 Operational Knowledge Retention

Every resolved incident should improve future investigations.

---

## 16. Validation Requirements

Before expanding the platform, the project must validate that real users receive measurable value.

The initial validation questions are:

1. Can Sentinel ingest useful operational signals with minimal setup?
2. Can it group repeated errors more effectively than the source system alone?
3. Can it associate failures with relevant deployments?
4. Can it produce a useful incident explanation?
5. Do engineers trust the displayed evidence?
6. Does Sentinel reduce investigation time?
7. Do users confirm or correct its hypotheses?
8. Does historical incident knowledge improve later investigations?
9. Will teams self-host the product?
10. Will teams pay for managed or advanced capabilities?

---

## 17. Success Metrics

Initial success should be evaluated using:

### Product Metrics

* time from first event to incident creation;
* time from incident creation to first useful hypothesis;
* percentage of incidents with relevant deployment context;
* percentage of hypotheses confirmed by humans;
* percentage of incidents linked to similar historical incidents;
* average number of duplicated events grouped;
* notification delivery success rate.

### User Outcome Metrics

* mean time to understand;
* mean time to resolution;
* number of tools opened during investigation;
* number of incidents resolved using retrieved knowledge;
* user-reported confidence in Sentinel evidence;
* repeated weekly usage.

### Business Validation Metrics

* number of active installations;
* number of active tenants;
* number of connected applications;
* weekly incident volume;
* retained installations;
* conversion to paid capabilities;
* infrastructure cost per tenant.

---

## 18. Expansion Criteria

Sentinel should expand into a broader operational platform only after the focused product demonstrates adoption.

Potential later capabilities include:

* additional frameworks and languages;
* advanced service topology;
* broader alert-source integrations;
* on-call integrations;
* approved remediation workflows;
* status communication;
* advanced plugin development;
* enterprise identity;
* policy controls;
* advanced analytics;
* managed cloud hosting;
* multi-agent investigation;
* enterprise-scale data processing.

Expansion must be driven by verified user needs rather than architectural possibility.

---

## 19. Documentation Impact

Existing documentation should be interpreted according to the following classification.

### 19.1 Foundation

These requirements remain mandatory:

* tenant isolation;
* security;
* authorization;
* auditability;
* idempotency;
* domain boundaries;
* event integrity;
* traceability;
* reliable asynchronous processing.

### 19.2 Initial Product

These capabilities should be implemented first:

* integrations;
* events;
* alerts;
* incidents;
* deployments;
* evidence;
* hypotheses;
* operational knowledge;
* notifications;
* human feedback.

### 19.3 Future Platform

These capabilities remain documented but deferred:

* public plugin marketplace;
* autonomous remediation;
* full on-call management;
* complete observability;
* enterprise CMDB;
* complex agent orchestration;
* microservice deployment;
* advanced enterprise governance.

Future documents must clearly distinguish between:

* MVP;
* post-MVP;
* enterprise future state.

---

## 20. Decision Principles

All future product and engineering decisions should follow these principles.

### Principle 1: Solve Investigation Before Coordination

Sentinel must first help teams understand incidents.

It does not need to replace every tool used to coordinate them.

### Principle 2: Integrate Before Replacing

Use data from existing systems instead of rebuilding those systems.

### Principle 3: Evidence Before Confidence

A conclusion without inspectable evidence is not operational intelligence.

### Principle 4: Human Authority

Human operators retain authority over incident cause, resolution, and production actions.

### Principle 5: Narrow Initial Customer

Laravel and SaaS teams are the first market, not a permanent technical limitation.

### Principle 6: Modular Monolith First

Maintain clear boundaries without accepting unnecessary distributed-system cost.

### Principle 7: Platform Architecture, Product-Sized Implementation

Preserve future extensibility while keeping the implemented surface small.

### Principle 8: Validate Before Expanding

No major capability should be built solely because it appears in the long-term architecture.

---

## 21. Final Product Statement

Project Sentinel is:

> **A self-hosted incident-intelligence platform for Laravel and SaaS engineering teams that connects production errors, alerts, deployments, and operational knowledge to produce evidence-backed incident investigations.**

The long-term vision remains an extensible operational-intelligence platform.

The immediate mission is simpler:

> **Help an engineer understand why production started failing—quickly, clearly, and with evidence.**

---

## 22. Decision Status

This product focus reset is **accepted**.

It supersedes any interpretation of previous documentation that requires Project Sentinel to implement the full enterprise platform in its initial release.

Existing architecture remains valuable as a long-term design direction, but implementation priorities must follow the focused product strategy defined in this document.
