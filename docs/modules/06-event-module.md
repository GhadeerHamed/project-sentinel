# Event Module

## Purpose

The Event module owns ingestion, validation, normalization, persistence, and publication of operational Events and derived Signals.

It converts external telemetry into trusted Sentinel operational facts.

---

## Owns

The module owns:

* Event.
* Event ingestion request.
* Event source reference.
* Event normalization result.
* Signal.
* Event processing status.
* ingestion idempotency record.
* Event and Signal history.

---

## Responsibilities

The module is responsible for:

* Accepting operational telemetry.
* validating source and tenant context.
* converting input into the canonical Event model.
* enforcing ingestion idempotency.
* persisting immutable Events.
* deriving Signals.
* publishing Event and Signal facts.
* preserving evidence and source attribution.
* applying ingestion limits and retention rules.

---

## Does Not Own

The module does not own:

* Alert lifecycle.
* Incident lifecycle.
* Application or Environment configuration.
* Plugin installation state.
* Notification delivery.
* Workflow execution.
* Agent decisions.

It must not create or mutate Alerts or Incidents directly.

---

## Event Resource

An Event represents an immutable operational fact.

Important attributes include:

```text
id
organization_id
project_id
application_id
environment_id
source_type
source_id
event_type
severity
occurred_at
received_at
payload
attributes
fingerprint
status
schema_version
```

The primary identifier uses UUIDv7.

---

## Canonical Event Model

Every accepted Event must be converted into Sentinel’s canonical structure.

The canonical model separates:

* Stable metadata.
* source attribution.
* classification.
* searchable attributes.
* provider-specific payload.
* timestamps.
* evidence references.

Provider-specific input must not become the domain contract.

---

## Event Immutability

Accepted Events are immutable.

Corrections should use:

* A replacement Event.
* a linked correction Event.
* processing metadata.
* an administrative annotation.

Existing Event facts must not be silently rewritten.

---

## Signal Resource

A Signal represents an operationally meaningful interpretation derived from one or more Events.

Examples:

```text
error-rate-increase
service-unavailable
latency-threshold-exceeded
deployment-detected
credential-failure-pattern
```

A Signal must preserve references to its source evidence.

---

## Event Status

Suggested processing states:

```text
received
validated
accepted
rejected
processing
processed
failed
```

The authoritative Event fact exists only after successful validation and acceptance.

---

## Commands

Primary commands include:

```text
IngestEvent
IngestEventBatch
NormalizeEvent
RejectEvent
ReprocessEvent
DeriveSignals
AttachEventEvidence
```

Administrative reprocessing must not change the original Event payload.

---

## Queries

Primary queries include:

```text
GetEvent
ListEvents
GetEventTimeline
GetEventEvidence
ListSignals
GetSignal
GetSignalEvidence
```

Queries must preserve tenant, Application, Environment, and sensitivity scope.

---

## Published Events

The module publishes:

```text
event.accepted
event.rejected
event.processing_failed
signal.detected
signal.updated
signal.expired
```

Published facts must include:

```text
organization_id
event_id or signal_id
application_id
environment_id
occurred_at
correlation_id
causation_id
event_version
```

---

## Consumed Events

The module may consume:

```text
organization.suspended
organization.archived
application.archived
environment.archived
plugin.data_received
retention.policy_applied
```

These events may affect ingestion eligibility, processing, or retention.

Consumption must remain idempotent.

---

## Ingestion Contract

Every ingestion request must establish:

```text
organization_id
source identity
application_id
environment_id when applicable
idempotency key
schema version
received timestamp
```

Client-supplied ownership identifiers must be validated against the authenticated source.

---

## Idempotency

Duplicate ingestion must not create duplicate Events.

Idempotency may use:

* Client idempotency key.
* provider event identifier.
* source identifier plus fingerprint.
* database uniqueness constraints.

The deduplication scope must include Organization and source.

---

## Validation

Validation should verify:

* Authenticated source.
* active Organization.
* active Application.
* eligible Environment.
* supported schema version.
* required fields.
* timestamp validity.
* payload size.
* attribute limits.
* ownership consistency.

Invalid Events must not enter authoritative processing.

---

## Batch Ingestion

Batch ingestion should:

* Apply one trusted tenant and source context.
* validate each item independently.
* enforce bounded batch size.
* preserve per-item results.
* avoid one invalid item corrupting accepted items.
* support batch-level idempotency where useful.

Partial acceptance must be explicit.

---

## Source Attribution

Every Event must identify its origin.

Possible source types include:

```text
sdk
api_client
plugin
webhook
service
manual
platform
```

Source attribution must remain immutable after acceptance.

---

## Timestamp Rules

The module distinguishes:

```text
occurred_at
received_at
processed_at
```

`occurred_at` comes from the source when trusted.

`received_at` is assigned by Sentinel.

Events with excessive future or historical timestamps may be rejected, quarantined, or marked according to Policy.

---

## Severity

Event severity may be provided by the source and normalized to Sentinel levels.

Suggested values:

```text
critical
high
medium
low
informational
```

Source severity does not automatically determine Alert or Incident severity.

---

## Fingerprinting

A fingerprint may group operationally equivalent Events.

Fingerprinting should use stable normalized fields.

It must not include:

* Secrets.
* volatile timestamps.
* unrestricted payload values.
* cross-tenant input.

Fingerprint rules must be versioned when changed.

---

## Signal Derivation

Signal derivation may use:

* Event type.
* severity.
* frequency.
* time window.
* Application and Environment context.
* prior related Events.
* configured detection rules.

Signal derivation must remain deterministic where practical and preserve evidence references.

---

## Alert Integration

The Alert module consumes `signal.detected`.

Preferred flow:

```text
Event accepted
↓
Signal derived
↓
signal.detected
↓
Alert evaluation
```

The Event module does not decide whether an Alert should exist.

---

## Plugin Integration

Plugins may produce Event ingestion requests through approved contracts.

Plugin input must still pass:

* Authentication.
* capability checks.
* tenant validation.
* schema validation.
* idempotency.
* normalization.

Plugins must not write Event tables directly.

---

## Agent Integration

Agents may read authorized Events and Signals as evidence.

Agents must not:

* mutate Event facts.
* remove source attribution.
* reinterpret evidence as authoritative without a new domain fact.
* access cross-tenant telemetry.

---

## Repository Interfaces

The module owns repository contracts such as:

```text
EventRepository
SignalRepository
IngestionIdempotencyRepository
EventEvidenceRepository
```

Other modules use Event queries or published facts rather than writing Event persistence directly.

---

## Transactions

Event acceptance should atomically commit:

* Event record.
* ingestion idempotency result.
* processing metadata.
* Outbox Record.

Signal creation should atomically commit:

* Signal state.
* evidence relationships.
* Outbox Record.

---

## Retention

Event and Signal retention depends on:

* Organization Policy.
* Environment type.
* data classification.
* evidence dependencies.
* Legal Hold.
* archive requirements.

Environment criticality is not part of the current Registry contract. A future retention capability may consider it only after that Environment capability is introduced.

Deleting an Event must not invalidate retained Incident evidence without an approved preservation strategy.

---

## Search Integration

The Search module may index selected Event and Signal fields.

Search documents must:

* Include Organization scope.
* preserve sensitivity.
* exclude secrets.
* remain rebuildable.
* follow deletion and retention state.

Raw payload indexing should be limited.

---

## Authorization

Typical permissions include:

```text
event.ingest
event.read
event.reprocess
event.export
signal.read
```

Ingestion identities may have write-only access without general Event read permission.

Administrative reprocessing requires elevated authorization and Audit.

---

## Audit Requirements

Audit Records are required for:

* Ingestion credential changes.
* administrative reprocessing.
* manual Event creation.
* export of sensitive Event data.
* retention override.
* evidence modification.
* privileged access to restricted payloads.

Routine high-volume ingestion does not require one administrative Audit Record per Event when equivalent immutable operational history exists.

---

## Testing Requirements

Tests must cover:

* Canonical normalization.
* idempotent ingestion.
* cross-tenant rejection.
* inactive parent rejection.
* batch partial acceptance.
* timestamp validation.
* immutable Event behavior.
* fingerprint Versioning.
* Signal evidence linkage.
* duplicate event delivery.
* event publication.
* retention restrictions.

---

## Prohibited Practices

The following are prohibited:

* Mutating accepted Event payloads.
* writing Alerts directly from Event processing.
* provider-specific domain contracts.
* Event ingestion without trusted source identity.
* querying Events without Organization scope.
* storing credentials in Event payloads.
* using source severity as final Incident priority.
* Plugin direct database writes.
* losing evidence attribution during Signal derivation.
* unrestricted raw payload indexing.

---

## Business Rules

* Events are immutable operational facts.
* every Event belongs to one Organization and Application.
* Environment ownership is validated when present.
* all external input is normalized into the canonical Event model.
* ingestion is idempotent.
* provider-specific schemas remain outside the domain contract.
* Signals preserve source evidence.
* the Event module does not own Alerts or Incidents.
* accepted state and Outbox publication eligibility are committed atomically.
* retention must preserve required operational evidence.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/04-application-module.md`
* `docs/modules/05-environment-module.md`
* `docs/modules/07-alert-module.md`
* `docs/modules/11-plugin-module.md`
* `docs/database/05-event-signal-schema.md`
* `docs/api/10-events-api.md`
* `docs/architecture/05-event-model.md`
* `docs/adr/ADR-004-operational-processing-model.md`
* `docs/adr/ADR-011-canonical-event-model.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
