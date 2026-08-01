# Threat Model

## Purpose

This document identifies the primary security threats facing Project Sentinel and the controls required to reduce them.

The threat model covers:

* Public APIs.
* tenant data.
* authentication.
* Plugins.
* Agents.
* Workflows.
* background processing.
* infrastructure.
* external providers.

---

## Protected Assets

Sentinel must protect:

* Organization data.
* operational Events and evidence.
* Alerts and Incidents.
* Knowledge.
* credentials and secret references.
* identity and permission data.
* Agent and Plugin configuration.
* Audit Records.
* customer exports.
* platform availability.

---

## Threat Actors

Potential threat actors include:

* Unauthenticated external attackers.
* compromised Users.
* malicious Organization Members.
* compromised API Clients.
* vulnerable or malicious Plugins.
* manipulated Agent inputs.
* compromised external providers.
* privileged operators abusing access.
* automated bots and denial-of-service attackers.

---

## Primary Trust Boundaries

```text
External Client
    ↓
Public API

External Provider
    ↓
Plugin, Webhook, or AI Provider Boundary

Application Layer
    ↓
Domain and Policy Layer

Domain Layer
    ↓
Database, Queue, Search, and Storage

Platform Operator
    ↓
Privileged Tenant Access
```

Every trust-boundary crossing requires appropriate validation and authorization.

---

# Primary Threats

## Cross-Tenant Data Access

### Threat

An identity accesses or modifies Resources belonging to another Organization.

Possible causes:

* Missing `organization_id` filters.
* trusting Resource identifiers.
* incorrect cache keys.
* Search Projection leakage.
* background jobs losing tenant context.
* malformed Resource relationships.

### Required Controls

* Organization-scoped queries.
* tenant-aware foreign keys.
* centralized tenant-context resolution.
* authorization on every operation.
* Organization-aware cache and Search keys.
* automated tenant-isolation tests.
* Audit of detected isolation violations.

---

## Credential Theft

### Threat

Credentials are exposed through:

* Logs.
* database records.
* source code.
* error messages.
* insecure transport.
* compromised client systems.

### Required Controls

* TLS.
* secure hashing.
* managed secret storage.
* short-lived credentials where practical.
* credential rotation and revocation.
* secret scanning.
* output redaction.
* restricted secret access.

---

## Broken Authorization

### Threat

An authenticated identity performs an operation outside its permitted scope.

Examples:

* A User accesses another Project.
* a Plugin performs an undeclared Action.
* an Agent reads restricted evidence.
* a Workflow executes after approval expires.

### Required Controls

* Deny-by-default Policy evaluation.
* Resource-level authorization.
* explicit capability grants.
* execution-time authorization.
* scope validation.
* optimistic concurrency for protected changes.
* authorization tests for every endpoint and command.

---

## Identity Confusion

### Threat

A non-human identity is treated as a User or receives the initiating User’s full permissions.

### Required Controls

* Separate identity types.
* dedicated Agent, Plugin, API Client, and Service identities.
* explicit acting-identity propagation.
* separate initiating and executing identity fields.
* complete Audit attribution.
* no implicit impersonation.

---

## Request Replay and Duplicate Effects

### Threat

Requests, webhooks, or queued messages are replayed and repeat a business Action.

### Required Controls

* Idempotency keys.
* webhook timestamps and signatures.
* unique external Event identifiers.
* duplicate message detection.
* Action state validation.
* immutable approval decisions.
* bounded retry behavior.

---

## Malicious Input

### Threat

External input attempts to cause:

* injection.
* unsafe deserialization.
* path traversal.
* command execution.
* malformed data processing.
* stored cross-site scripting.
* excessive resource consumption.

### Required Controls

* Strict schema validation.
* parameterized database queries.
* output encoding.
* upload validation.
* payload-size limits.
* safe serialization.
* content-type validation.
* bounded parsing and processing.

---

## Server-Side Request Forgery

### Threat

Webhook, Plugin, or import configuration causes Sentinel to access internal or prohibited network destinations.

### Required Controls

* HTTPS-only external URLs.
* private-address blocking.
* DNS and redirect validation.
* cloud metadata endpoint blocking.
* outbound network restrictions.
* destination allowlists where appropriate.
* timeout and response-size limits.

---

## Plugin Compromise

### Threat

A Plugin attempts to:

* access another Organization.
* steal credentials.
* write directly to domain data.
* request excessive permissions.
* execute unsafe provider calls.

### Required Controls

* Distinct Installation identities.
* least-privilege capability grants.
* secret references.
* Plugin Version validation.
* no direct database access.
* governed domain commands.
* outbound network controls.
* execution monitoring and suspension.

---

## Agent Manipulation

### Threat

Prompt injection or malicious evidence influences an Agent to expose data or propose unsafe Actions.

### Required Controls

* Treat all Agent input as untrusted.
* limit Agent context.
* separate instructions from external content.
* explicit Tool and capability allowlists.
* output validation.
* evidence attribution.
* Human or Workflow approval for protected Actions.
* execution-time Policy evaluation.

Agent output must never be treated as trusted authorization.

---

## Workflow Abuse

### Threat

A Workflow bypasses normal controls or repeats a dangerous Action.

### Required Controls

* Immutable Workflow Versions.
* explicit Action permissions.
* approval requirements.
* idempotent execution.
* state and Version checks.
* retry limits.
* Action execution through owning capabilities.
* Audit of privileged Steps.

---

## Privileged Access Abuse

### Threat

Platform operators or support personnel access tenant data without valid purpose.

### Required Controls

* No default tenant access.
* time-limited privileged sessions.
* explicit reason and scope.
* approval where required.
* full Audit recording.
* strong authentication.
* customer visibility where appropriate.
* rapid revocation.

---

## Sensitive Data Leakage

### Threat

Sensitive data leaks through:

* APIs.
* Search.
* Logs.
* Notifications.
* Webhooks.
* Agent context.
* exports.
* error messages.

### Required Controls

* Data classification.
* field-level authorization.
* response filtering.
* Search sensitivity controls.
* safe Notification templates.
* minimal Webhook payloads.
* secure exports.
* redaction and retention limits.

---

## Audit Tampering

### Threat

An attacker modifies or deletes evidence of activity.

### Required Controls

* Append-only Audit Records.
* restricted database permissions.
* integrity hashes where required.
* immutable archive storage.
* monitored Audit write failures.
* auditable Audit access.
* governed retention and deletion.

---

## Denial of Service

### Threat

An attacker or noisy tenant exhausts:

* API capacity.
* ingestion capacity.
* database connections.
* queue workers.
* AI provider quotas.
* storage.
* external-provider limits.

### Required Controls

* Rate limiting.
* quotas.
* bounded payloads.
* queue isolation.
* timeouts.
* circuit breakers.
* per-Organization concurrency limits.
* backpressure.
* resource monitoring.

---

## Supply Chain Compromise

### Threat

A dependency, package, container, or build pipeline introduces malicious code.

### Required Controls

* Dependency pinning.
* vulnerability scanning.
* signed build artifacts.
* minimal container images.
* protected CI/CD.
* restricted deployment credentials.
* software-bill-of-materials support where required.
* timely security updates.

---

## Data Loss or Corruption

### Threat

Operational data is lost or corrupted through defects, infrastructure failure, or incorrect retention processing.

### Required Controls

* Database constraints.
* transactional writes.
* Outbox pattern.
* backups.
* point-in-time recovery.
* restoration testing.
* retention safeguards.
* optimistic concurrency.
* integrity verification.

---

# Risk Classification

Threats should be evaluated using:

* Likelihood.
* impact.
* affected Organizations.
* data sensitivity.
* exploit complexity.
* detectability.
* recovery difficulty.

Suggested risk levels:

* `critical`
* `high`
* `medium`
* `low`

Critical and High threats require documented mitigation before production release.

---

# Security Testing Priorities

Required security tests include:

* Cross-Organization access attempts.
* permission bypass attempts.
* credential revocation.
* webhook replay and signature validation.
* idempotency conflicts.
* malicious Event and Plugin payloads.
* Agent prompt-injection scenarios.
* SSRF attempts.
* upload validation.
* rate-limit behavior.
* privileged-access auditing.
* retention and Legal Hold protection.

---

# Threat Review Triggers

The threat model must be reviewed when introducing:

* A new identity type.
* a new Plugin capability.
* a new Agent Tool.
* a new external provider.
* a new data-storage system.
* privileged support access.
* public file uploads.
* cross-region data movement.
* major infrastructure changes.
* new sensitive-data categories.

---

## Business Rules

* Tenant isolation is a critical security invariant.
* Every protected Action has an attributable identity.
* External and AI-generated input is untrusted.
* Plugins, Agents, and Workflows cannot bypass Policy.
* Secrets are never stored or logged in plaintext.
* Privileged access is temporary and audited.
* Audit failures for protected Actions must be visible.
* Security controls apply to background processing as well as HTTP requests.
* Critical and High threats require explicit mitigation.

---

## Related Documents

* `docs/security/00-security-overview.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/03-authentication-security.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/security/05-secrets-credentials.md`
* `docs/security/07-agent-plugin-security.md`
* `docs/security/08-security-monitoring.md`
* `docs/security/09-incident-response.md`
* `docs/architecture/08-security-architecture.md`
