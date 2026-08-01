# Agent and Plugin Security

## Purpose

This document defines the security controls for AI Agents and Plugins.

Both are non-human actors operating through explicit identities, capabilities, and Organization scope.

---

## Core Principles

* Every Agent and Plugin uses a dedicated identity.
* Capabilities are explicitly granted.
* Tenant context is mandatory.
* External and generated content is untrusted.
* Protected Actions require runtime authorization.
* Secrets remain outside prompts, configuration, and execution payloads.
* Execution must remain observable and suspendable.

---

## Agent Identity

Each active Agent Version must use an Agent Identity linked to:

* Organization.
* Agent Definition.
* Agent Version.
* granted capabilities.
* Resource scope.
* execution Policy.

Agents must not inherit the full permissions of the User who starts an execution.

---

## Plugin Identity

Each Plugin Installation must use a distinct Plugin Identity linked to:

* Organization.
* Plugin Definition.
* installed Version.
* capability grants.
* Resource scope.
* credential bindings.

One Installation must not reuse another Installation’s identity or credentials.

---

## Capability Enforcement

Effective access is the intersection of:

```text
Declared capability
∩ Organization grant
∩ Resource scope
∩ runtime Policy
```

Examples:

```text
incident.read
event.ingest
knowledge.read
workflow.trigger
notification.send
```

Agents and Plugins cannot grant themselves additional capabilities.

---

## Tenant Isolation

Agent and Plugin execution must preserve:

```text
organization_id
identity_id
execution_id
correlation_id
```

They must not access:

* Another Organization’s Resources.
* another tenant’s credentials.
* shared prompt context.
* cross-tenant embeddings.
* another Installation’s synchronization state.

---

## Agent Input Security

Agent context must be:

* Authorized.
* purpose-limited.
* sensitivity-aware.
* minimized.
* attributable to source evidence.

External content, Events, Knowledge, Plugin data, and User text must be treated as untrusted input.

Prompt injection must not change:

* System instructions.
* capability grants.
* Organization scope.
* authorization requirements.
* approval rules.

---

## Agent Output Security

Agent output must be treated as untrusted until validated.

Validation may include:

* Required output schema.
* supported Resource references.
* Organization ownership.
* allowed Action types.
* content safety.
* evidence attribution.

Agent output does not become authoritative domain state automatically.

---

## Agent Actions

Agents should normally produce:

* Findings.
* summaries.
* recommendations.
* proposed Actions.

Protected Actions must pass through:

1. The owning domain capability.
2. current authorization.
3. approval where required.
4. idempotency protection.
5. Audit recording.

Agent confidence does not replace Policy evaluation.

---

## Plugin Configuration

Plugin configuration must not contain plaintext credentials.

It may contain:

* Provider options.
* synchronization scope.
* mapping rules.
* permitted Resource scope.
* operational settings.

Credentials must use secure secret references.

---

## Plugin Execution

Plugins must interact with Sentinel through approved APIs or application contracts.

They must not:

* Access the database directly.
* bypass domain validation.
* modify authoritative tables directly.
* perform undeclared Actions.
* access unrestricted tenant context.

Imported data must preserve provider and source attribution.

---

## Provider Callbacks

Inbound callbacks must verify:

* Signature.
* timestamp.
* payload integrity.
* replay protection.
* endpoint ownership.
* expected Installation.

Invalid callbacks must not create authoritative data.

---

## External Provider Calls

Outbound calls should use:

* HTTPS.
* bounded timeouts.
* response-size limits.
* retry limits.
* destination validation.
* circuit breakers where appropriate.

Plugins must not be allowed to call arbitrary internal network destinations.

---

## Secret Handling

Secrets must not appear in:

* Agent prompts.
* Agent outputs.
* Plugin configuration.
* Logs.
* Audit payloads.
* provider error messages.
* execution artifacts.

Secrets should be loaded only by the runtime component that requires them.

---

## Execution Records

Every Agent or Plugin execution must record:

* Organization.
* identity.
* exact Agent or Plugin Version.
* initiating identity or process.
* target Resource.
* status.
* timestamps.
* correlation identifier.
* safe failure information.

Raw provider payloads should not be stored unless explicitly required and governed.

---

## Suspension and Revocation

Sentinel must be able to suspend immediately:

* An Agent.
* an Agent Version.
* a Plugin Installation.
* a capability grant.
* a credential.
* an external provider integration.

Suspension prevents new normal executions while preserving history.

---

## Monitoring

Sentinel should monitor:

* Capability violations.
* repeated execution failures.
* unusual context volume.
* excessive provider usage.
* prompt-injection indicators.
* invalid callback signatures.
* cross-tenant access attempts.
* unsafe Action proposals.
* credential failures.
* abnormal data exports.

High-risk findings should create security Alerts.

---

## Audit Requirements

Audit Records should be created for:

* Agent activation or suspension.
* Plugin installation or suspension.
* capability grants and revocations.
* credential changes.
* protected Action proposals and execution.
* approval decisions.
* significant security failures.
* manual execution overrides.

---

## Prohibited Practices

The following are prohibited:

* Sharing Agent or Plugin identities.
* inheriting unrestricted User permissions.
* placing secrets in prompts or configuration.
* direct Plugin database access.
* trusting Agent output as authorization.
* cross-Organization context reuse.
* unrestricted Tool access.
* executing protected Actions without current Policy evaluation.
* silently ignoring repeated security failures.

---

## Business Rules

* Every Agent and Plugin has a distinct non-human identity.
* Capabilities are explicit and least-privileged.
* Agent and Plugin context remains Organization-scoped.
* External and generated content is untrusted.
* Agent output does not directly become authoritative state.
* Plugins write through owning domain capabilities.
* Protected Actions require runtime authorization.
* Credentials use secret references.
* Executions preserve exact Version and identity attribution.
* Unsafe Agents, Plugins, capabilities, and credentials can be suspended immediately.

---

## Related Documents

* `docs/security/00-security-overview.md`
* `docs/security/01-threat-model.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/security/05-secrets-credentials.md`
* `docs/security/06-data-protection.md`
* `docs/database/09-agent-schema.md`
* `docs/database/10-plugin-schema.md`
* `docs/architecture/06-plugin-system.md`
* `docs/architecture/07-agent-architecture.md`
