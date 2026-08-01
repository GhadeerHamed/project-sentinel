# Security Monitoring

## Purpose

This document defines how Project Sentinel detects, records, and responds to security-relevant activity.

Security monitoring complements operational observability and Audit Records.

---

## Monitoring Objectives

Sentinel should detect:

* Authentication abuse.
* authorization failures.
* tenant-isolation violations.
* credential misuse.
* suspicious Agent or Plugin activity.
* privileged-access abuse.
* unusual exports.
* Audit pipeline failures.
* security-control failures.
* abnormal infrastructure behavior.

---

## Security Signals

Important security signals include:

```text
authentication.failed
authentication.revoked_credential_used
authorization.denied
tenant_isolation.violation
credential.access_failed
credential.rotation_failed
webhook.signature_invalid
agent.capability_violation
plugin.capability_violation
privileged_access.started
privileged_access.scope_exceeded
audit.write_failed
data.export_unusual
```

Signal names should remain stable and machine-readable.

---

## Severity

Suggested severity levels:

* `critical`
* `high`
* `medium`
* `low`
* `informational`

Severity should consider:

* Data sensitivity.
* affected Organizations.
* privilege level.
* likelihood of compromise.
* repeated behavior.
* operational impact.

---

## Detection Sources

Security detections may use:

* API telemetry.
* authentication events.
* authorization decisions.
* Audit Records.
* application Logs.
* infrastructure Logs.
* Agent and Plugin execution records.
* webhook failures.
* secret-manager activity.
* database and storage access events.

Sensitive payloads must not be copied unnecessarily into monitoring systems.

---

## Authentication Monitoring

Monitor for:

* Repeated login failures.
* credential stuffing patterns.
* use of revoked or expired credentials.
* unusual Session creation.
* repeated MFA failures.
* abnormal API Client behavior.
* suspicious recovery attempts.

Controls should support rate limiting and automated credential suspension where justified.

---

## Authorization Monitoring

Monitor:

* Repeated denied Actions.
* access outside assigned Resource scope.
* production access by unexpected identities.
* expired approval use.
* background jobs executing without valid context.
* field-level access violations.

A tenant-isolation denial should receive elevated priority.

---

## Agent and Plugin Monitoring

Monitor:

* Capability violations.
* abnormal execution volume.
* repeated provider failures.
* unusual context or payload size.
* cross-tenant access attempts.
* unsafe Action proposals.
* invalid callback signatures.
* unexpected destination access.

Unsafe Agents or Plugin Installations must be suspendable immediately.

---

## Privileged Access Monitoring

Every privileged-access session should record:

* Identity.
* Organization.
* purpose.
* approved scope.
* start and expiration.
* Actions performed.

Alert when:

* Scope is exceeded.
* access continues after expiration.
* sensitive exports occur unexpectedly.
* repeated privileged sessions are requested.
* access lacks required approval.

---

## Data Access Monitoring

Monitor:

* Large exports.
* unusual download volume.
* repeated access to restricted evidence.
* cross-region data movement.
* Search patterns targeting sensitive Resources.
* access to archived or Legal Hold data.

Monitoring must not expose unauthorized data to operators.

---

## Audit Pipeline Monitoring

Critical conditions include:

* Audit write failure.
* delayed Audit processing.
* missing actor attribution.
* missing Organization context.
* integrity-check failure.
* unauthorized Audit access.
* failed Audit archival.

Protected Actions may need to fail when mandatory Audit persistence is unavailable.

---

## Alert Creation

High-confidence security detections should create Sentinel Alerts.

Critical detections may also:

* Suspend a credential.
* revoke Sessions.
* suspend an Agent or Plugin.
* block an operation.
* open a security Incident.
* notify authorized responders.

Automated containment must remain governed and auditable.

---

## Noise Control

Security monitoring should use:

* Deduplication.
* rate-based aggregation.
* suppression rules.
* severity thresholds.
* environment-aware rules.
* known-safe automation allowlists.

Noise reduction must not hide critical tenant-isolation or credential-compromise events.

---

## Retention

Security telemetry retention depends on:

* Risk level.
* investigation needs.
* contract.
* regulation.
* Legal Hold.

Raw high-volume Logs may use shorter retention than security findings and Audit Records.

---

## Access Control

Security monitoring data must be restricted to authorized roles.

Access should follow:

* Organization scope.
* investigation scope.
* sensitivity.
* privileged-access controls.

Viewing sensitive security data may itself require Audit recording.

---

## Incident Escalation

A security Alert should become a security Incident when:

* Compromise is likely or confirmed.
* tenant data may have crossed boundaries.
* privileged credentials are affected.
* sensitive data may have been exposed.
* containment requires coordinated response.

---

## Monitoring Health

Sentinel must monitor the monitoring system itself.

Important health checks include:

* Event ingestion delay.
* missing Log sources.
* rule-processing failures.
* notification failures.
* queue backlog.
* storage failures.
* clock drift.
* unavailable security providers.

A failed monitoring pipeline must create an operational Alert.

---

## Prohibited Practices

The following are prohibited:

* Logging plaintext credentials.
* storing unrestricted sensitive payloads in detection systems.
* allowing monitoring failures to remain silent.
* sharing tenant security data across Organizations.
* allowing automatic containment without Audit.
* suppressing critical tenant-isolation findings.
* granting unrestricted monitoring access to normal operators.

---

## Business Rules

* Security-relevant activity produces structured telemetry.
* Critical and High detections remain actionable.
* Tenant-isolation violations are treated as critical.
* Monitoring data follows tenant and sensitivity controls.
* Automated containment is governed and audited.
* Security-monitoring failures are themselves monitored.
* Security Alerts may escalate into security Incidents.
* Repeated security failures must not remain silent.

---

## Related Documents

* `docs/security/00-security-overview.md`
* `docs/security/01-threat-model.md`
* `docs/security/07-agent-plugin-security.md`
* `docs/security/09-incident-response.md`
* `docs/database/06-alert-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/13-audit-schema.md`
