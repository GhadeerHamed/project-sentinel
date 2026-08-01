# Security Overview

## Purpose

This document defines the security model for Project Sentinel.

Security applies across:

* Users.
* Organizations.
* Applications.
* APIs.
* Plugins.
* Agents.
* Workflows.
* operational data.
* infrastructure.

Detailed controls are defined in the remaining security documents.

---

## Security Principles

Sentinel follows these principles:

* Deny by default.
* least privilege.
* explicit tenant isolation.
* strong identity attribution.
* defense in depth.
* secure defaults.
* immutable Audit Records.
* minimal sensitive-data exposure.
* short-lived credentials where practical.
* continuous verification for protected Actions.

---

## Trust Boundaries

Primary trust boundaries include:

```text
External Client
    ↓
Public API Boundary
    ↓
Application and Policy Layer
    ↓
Domain Capabilities
    ↓
Database, Queue, Search, and Storage
```

Additional boundaries exist between Sentinel and:

* Identity providers.
* Plugin providers.
* AI model providers.
* notification providers.
* webhook consumers.
* cloud infrastructure.

Crossing a trust boundary requires authentication, validation, and authorization.

---

## Tenant Isolation

Organization is the primary tenant boundary.

Every tenant operation must resolve exactly one:

```text
organization_id
```

Tenant-owned queries and relationships must include Organization scope.

Sentinel must prevent:

* Cross-Organization reads.
* cross-Organization writes.
* cross-tenant cache reuse.
* cross-tenant Search results.
* Agent or Plugin context leakage.
* shared credentials without explicit governance.

Resource identifiers do not grant access.

---

## Identity Model

Sentinel distinguishes:

* User Identity.
* API Client Identity.
* Service Identity.
* Agent Identity.
* Plugin Identity.
* Workflow execution context.
* privileged support or emergency Identity.

Non-human identities must use dedicated credentials and explicit permissions.

They must not impersonate Users.

---

## Authentication

Authentication mechanisms may include:

* Secure User Sessions.
* API tokens.
* API Client credentials.
* workload identity.
* OpenID Connect.
* SAML.
* signed webhook requests.
* mutual TLS where required.

Authentication proves identity only.

Authorization is evaluated separately.

---

## Authorization

Protected operations require evaluation of:

* Acting identity.
* Organization.
* permission.
* Resource scope.
* current Resource state.
* Environment.
* authentication strength.
* approval requirements.
* Agent or Plugin grants.
* applicable security Policy.

Authorization follows deny-by-default behavior.

Protected Actions must be authorized at execution time.

---

## Policy Layer

The Security Policy Layer governs decisions such as:

* Whether an identity may access a Resource.
* whether an Agent may read evidence.
* whether a Plugin may perform an Action.
* whether approval is required.
* whether stronger authentication is required.
* whether production scope is restricted.
* whether an operation must be audited.

Policy decisions must remain independent from controllers and UI behavior.

---

## Credential Security

Credentials must:

* Never be stored in plaintext.
* use secure hashing or approved secret storage.
* be scoped to one identity and purpose.
* support expiration and revocation.
* support rotation.
* be excluded from Logs and Audit payloads.

Secret values should be displayed only once when created.

---

## Data Classification

Sentinel data should be classified as:

* `internal`
* `confidential`
* `restricted`
* `regulated`

Classification controls:

* API exposure.
* Search indexing.
* Agent context.
* Plugin access.
* retention.
* export.
* logging.
* encryption requirements.

Derived data must not reduce the classification of its source.

---

## Encryption

Sentinel requires:

* TLS for data in transit.
* encrypted database and storage volumes.
* encrypted backups.
* encrypted archive storage.
* managed encryption keys.
* restricted key access.

Highly sensitive fields may require application-level encryption.

Encryption does not replace authorization.

---

## Input and Output Security

All external input is untrusted.

Sentinel must validate:

* API requests.
* Event payloads.
* Plugin responses.
* Agent output.
* webhook payloads.
* uploaded files.
* external provider callbacks.

Output must be filtered according to:

* Organization.
* identity permissions.
* Resource scope.
* sensitivity.
* field-level restrictions.

---

## Agent Security

Agents must:

* Use dedicated identities.
* receive explicit capabilities.
* access only approved context.
* treat external content as untrusted.
* preserve evidence references.
* produce recommendations rather than bypass controls.
* require normal authorization for protected Actions.

Agent prompts, context, and provider responses must not leak between Organizations.

---

## Plugin Security

Plugins must:

* Use distinct Installation identities.
* receive explicit capability grants.
* store credentials as secret references.
* validate provider payloads.
* authenticate callbacks.
* preserve Organization scope.
* execute protected Actions through owning capabilities.

Plugins must not access the database directly.

---

## Workflow Security

Workflows coordinate Actions but do not own all permissions.

Protected Workflow Actions require:

* Valid acting identity.
* current authorization.
* valid approval where required.
* idempotency.
* target Resource validation.
* Audit recording.

Approval does not replace execution-time authorization.

---

## Logging and Audit

Operational Logs and Audit Records serve different purposes.

Logs support:

* Debugging.
* performance monitoring.
* operational investigation.

Audit Records support:

* Accountability.
* security investigation.
* compliance.
* privileged-access review.

Neither may contain plaintext credentials or unnecessary sensitive payloads.

---

## Security Monitoring

Sentinel should monitor:

* Authentication failures.
* authorization denials.
* tenant-isolation violations.
* suspicious credential use.
* privileged-access activity.
* Plugin and Agent failures.
* unexpected data exports.
* Audit pipeline failures.
* secret-access anomalies.
* repeated webhook signature failures.

High-risk findings should create Alerts.

---

## Privileged Access

Support and emergency access must be:

* Explicitly authorized.
* time-limited.
* purpose-bound.
* scope-limited.
* fully audited.
* revocable.

Normal platform operators must not have unrestricted tenant access.

---

## Secure Development

Security requirements apply throughout development.

Required practices include:

* Dependency scanning.
* secret scanning.
* code review.
* automated security tests.
* migration review.
* least-privilege infrastructure permissions.
* secure configuration management.
* vulnerability remediation.

Production credentials must never be used in development environments.

---

## Incident Response

Security Incidents must support:

* Detection.
* containment.
* evidence preservation.
* credential revocation.
* tenant impact assessment.
* recovery.
* communication.
* Post-Incident Review.

Security evidence must follow retention and Legal Hold requirements.

---

## Prohibited Practices

The following are prohibited:

* Trusting Resource identifiers as authorization.
* cross-Organization queries without explicit platform governance.
* plaintext credential storage.
* logging access tokens or secrets.
* direct Plugin database access.
* unrestricted Agent context.
* relying only on network location for trust.
* bypassing Policy checks in background jobs.
* treating approval as permanent authorization.
* exposing stack traces through public APIs.
* silently ignoring Audit failures for protected Actions.

---

## Business Rules

* Organization is the primary tenant boundary.
* Every protected operation has an attributable identity.
* Authentication and authorization remain separate.
* Authorization is deny-by-default.
* Protected Actions are authorized at execution time.
* Credentials are scoped, revocable, and never stored in plaintext.
* Agent and Plugin identities receive explicit capabilities.
* Sensitive data is minimized in APIs, Logs, Search, and AI context.
* Privileged access is limited and audited.
* Security controls apply equally to synchronous and asynchronous processing.
* Cross-Organization access is prohibited unless explicitly governed.

---

## Related Documents

* `docs/security/01-threat-model.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/03-authentication-security.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/security/05-secrets-credentials.md`
* `docs/security/06-data-protection.md`
* `docs/security/07-agent-plugin-security.md`
* `docs/security/08-security-monitoring.md`
* `docs/security/09-incident-response.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-008-security-policy-layer.md`
