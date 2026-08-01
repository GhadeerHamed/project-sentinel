# Data Protection

## Purpose

This document defines how Project Sentinel protects sensitive data throughout its lifecycle.

---

## Data Classification

Sentinel uses these classifications:

* `internal`
* `confidential`
* `restricted`
* `regulated`

Classification influences:

* Access control.
* encryption.
* Search indexing.
* Agent context.
* Plugin access.
* logging.
* exports.
* retention.

Derived data must not receive a lower classification than its source.

---

## Data Minimization

Sentinel should collect and retain only data required for:

* Operational analysis.
* Incident response.
* security.
* compliance.
* contracted product behavior.

Unnecessary sensitive fields should be rejected, removed, or redacted.

---

## Encryption in Transit

All external and internal network communication must use encrypted transport.

Required examples:

* HTTPS for APIs.
* TLS for database connections.
* encrypted queue connections.
* TLS for provider integrations.
* secure object-storage access.

Unencrypted production communication is prohibited.

---

## Encryption at Rest

Sensitive data must be encrypted in:

* Databases.
* object storage.
* backups.
* archives.
* Search infrastructure.
* temporary exports.

Encryption keys must be managed separately from encrypted data.

Highly sensitive fields may require application-level encryption.

---

## Access Control

Data access requires:

* Authenticated identity.
* valid Organization context.
* explicit permission.
* Resource-scope access.
* sensitivity-level access.

Field-level authorization must be applied where a Resource contains mixed-sensitivity fields.

---

## API Responses

API responses must expose only authorized fields.

Sensitive data must not be returned by default, including:

* Credentials.
* raw tokens.
* private provider responses.
* restricted evidence.
* internal security metadata.
* unnecessary personal information.

---

## Logs and Telemetry

Logs, traces, and metrics must avoid:

* Credentials.
* authorization headers.
* unrestricted request bodies.
* raw Event payloads.
* Agent prompts and outputs.
* confidential Notification content.

Where identifiers are needed, use safe IDs, hashes, or redacted values.

---

## Search and Analytics

Sensitive data may be indexed only when required and explicitly permitted.

Search and analytics must preserve:

* Organization isolation.
* source sensitivity.
* authorization scope.
* retention requirements.

Deletion of authoritative data must remove corresponding derived copies.

---

## Agent Data Protection

Agent context must include only the minimum authorized information required for the objective.

Sentinel must prevent:

* Cross-Organization prompt context.
* unnecessary sensitive evidence exposure.
* provider retention beyond approved Policy.
* reuse of tenant prompts for training unless explicitly permitted.

Agent outputs must be validated and classified before storage or display.

---

## Plugin Data Protection

Plugins may access only data covered by their capability grants and Resource scope.

Plugin payloads must be:

* Minimized.
* validated.
* encrypted in transit.
* excluded from unrestricted Logs.
* retained only as necessary.

Provider credentials remain separate from Plugin configuration.

---

## Files and Exports

Uploaded files and exports must use:

* Organization-scoped ownership.
* authorized access.
* encrypted storage.
* malware or content validation where appropriate.
* short-lived download access.
* retention and deletion controls.

Export files should expire automatically.

---

## Backups and Archives

Backups and archives must be:

* Encrypted.
* access-controlled.
* integrity-checked.
* retained according to Policy.
* tested for restoration.
* protected from unauthorized deletion.

Restored data must follow current authorization and completed deletion requirements.

---

## Data Retention

Sensitive data must not remain longer than required.

Retention must consider:

* Resource Type.
* sensitivity.
* customer configuration.
* contract.
* regulation.
* Legal Hold.
* evidence dependencies.

Derived data should normally follow the authoritative Resource lifecycle.

---

## Deletion

Governed deletion must remove or invalidate:

* Primary records.
* Search Projections.
* caches.
* embeddings.
* object-storage files.
* temporary exports.
* archived copies where eligible.

Legal Hold overrides normal deletion.

---

## Data Residency

Future deployments may require Organization data to remain within an approved region.

Residency controls may apply to:

* Database storage.
* backups.
* archives.
* Search.
* AI providers.
* external integrations.

Cross-region movement must be explicit and governed.

---

## Personal Data

Where personal data is processed, Sentinel should support:

* Purpose limitation.
* access restriction.
* correction where appropriate.
* export.
* anonymization.
* governed deletion.
* Audit of sensitive access.

Operational records should avoid unnecessary personal data.

---

## Monitoring

Sentinel should monitor:

* Unauthorized sensitive-data access.
* unusual exports.
* cross-tenant access attempts.
* encryption failures.
* expired files still accessible.
* derived data remaining after deletion.
* provider data-handling violations.

High-risk findings should create security Alerts.

---

## Prohibited Practices

The following are prohibited:

* Lowering data classification in derived systems.
* storing plaintext secrets.
* unrestricted sensitive payload logging.
* indexing confidential data without approval.
* exposing signed storage URLs for excessive durations.
* sending unrestricted tenant data to Agents or Plugins.
* retaining temporary exports indefinitely.
* restoring deleted data without reapplying deletion state.

---

## Business Rules

* Sensitive data is classified and access-controlled.
* Data collection and exposure are minimized.
* Production data is encrypted in transit and at rest.
* Derived data preserves source classification.
* Search, Agents, Plugins, Logs, and exports follow the same protection rules.
* Data retention is explicit.
* Legal Hold overrides deletion.
* Tenant data remains isolated across storage and providers.
* Sensitive-data access and exports are auditable where required.

---

## Related Documents

* `docs/security/00-security-overview.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/05-secrets-credentials.md`
* `docs/security/07-agent-plugin-security.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/architecture/08-security-architecture.md`
