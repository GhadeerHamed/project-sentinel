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
* future Signal derivation.
* future Event processing records.
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
event_source_id
external_event_id
event_type
category
severity
occurred_at
received_at
payload
attributes
schema_version
correlation_id
causation_id
trace_id
created_at
```

The primary identifier uses UUIDv7. Every accepted Event persists the validated Organization, Project, Application, and Environment identifiers as accepted-context references; they are not mutable snapshots.

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

## Ingestion Status

The authoritative Event fact exists only after successful validation and acceptance. Request outcomes such as `accepted`, `duplicate`, `rejected`, and `failed` belong to the ingestion record. Later processing state belongs to derived processing records and must not mutate canonical Event data.

---

## Commands

Primary commands include:

```text
IngestEvent
```

The first slice implements only `IngestEvent`. Batch ingestion, reprocessing, Signal derivation, and evidence attachment are later Milestone 5 capabilities. Administrative reprocessing must not change the original Event payload.

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

Queries must preserve tenant, Application, Environment, and sensitivity scope. Signal queries are deferred until Signal derivation is introduced.

---

## Published Events

The first ingestion slice publishes only:

```text
event.accepted
```

Later Event-processing capabilities may publish:

```text
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
authenticated Event Source identity
application_id
environment_id
idempotency key
schema version
received timestamp
```

The route Organization plus request Application and Environment must resolve to one active Organization → Project → Application → Environment hierarchy. The Project identifier is derived from that relationship and persisted with the Event. Suspended Organizations and archived Projects, Applications, or Environments reject ingestion.

The source identity is derived exclusively from authentication. Client-supplied `source`, `source_id`, `source_type`, or source-system fields are not accepted.

---

## Event Source Authentication

The first supported Event Source type is `api_client`. An Event Source has a server-generated UUIDv7 identifier, belongs to one Organization, is scoped to one active Organization → Project → Application → Environment hierarchy, and must be `active` to ingest. Its ingestion credential is an opaque bearer token in:

```http
Authorization: Bearer esc_{credential_id}.{secret}
```

The server resolves the credential to its Event Source and verifies that its Organization matches the route Organization. Credential secrets are shown only at issuance and stored only as a one-way hash; they must never be logged or persisted in plaintext. The first slice permits one active credential per Event Source. Revoking, disabling, expiring, or replacing that credential immediately prevents ingestion; rotation and overlapping credentials are deferred.

Event Source attribution is distinct from a future human acting identity. Plugin, Agent, webhook, manual, and platform sources are deferred.

---

## Idempotency

Duplicate ingestion must not create duplicate Events.

`Idempotency-Key` is required. Its scope is Organization, authenticated Event Source, operation `event.ingest`, key, and normalized request hash. A matching retry returns the original accepted Event; reuse with different normalized input returns a conflict. Concurrent matching requests create one Event.

`external_event_id` is a separate optional producer identifier. When present, it is unique within `(organization_id, event_source_id, external_event_id)`. A matching source duplicate returns the original accepted Event, while the same external identifier with different normalized input is a conflict. Fingerprint-based semantic deduplication is deferred.

---

## Validation

Validation should verify:

* Authenticated source.
* active Event Source and credential.
* active Organization, Project, Application, and Environment.
* supported canonical envelope schema version.
* required fields.
* timestamp validity.
* payload size.
* attribute limits.
* ownership consistency.

Invalid Events must not enter authoritative processing.

---

## Deferred Batch Ingestion

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

The first slice persists `event_source_id` and optional `external_event_id`. The supported source type is:

```text
api_client
```

Additional source types are deferred. Source attribution must remain immutable after acceptance.

---

## Event Types

`event_type` must already be a lowercase dot-separated canonical name; the MVP does not translate provider aliases. The initial catalog and server-derived category are:

```text
application.error -> error
application.health_failed -> availability
application.health_recovered -> availability
deployment.completed -> deployment
custom.operational_event -> platform
```

Unknown types are rejected. Provider-specific mappings and a broader catalog are deferred.

---

## Timestamp Rules

The module distinguishes:

```text
occurred_at
received_at
created_at
```

`occurred_at` comes from the source when trusted.

`received_at` and `created_at` are assigned by Sentinel and normalized to UTC. The MVP validates timestamp format and timezone but does not apply future- or age-skew rejection thresholds until Policy defines them.

Later processing timestamps belong to processing records.

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
unknown
```

Source severity does not automatically determine Alert or Incident severity.

---

## Deferred Semantic Fingerprinting

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

Signal derivation is deferred beyond the first ingestion slice.

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

Plugin ingestion is deferred. When introduced, Plugins may produce Event ingestion requests through approved contracts.

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

Agent ingestion and Agent Event access are deferred. When introduced, Agents may read authorized Events and Signals as evidence.

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
* `event.accepted` Outbox Record.

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
```

The first slice grants its authenticated Event Source only `event.ingest`; Event read, reprocessing, export, and Signal permissions are later capabilities.

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
* authenticated active Event Source validation.
* idempotent ingestion and source-level duplicate handling.
* cross-tenant and inactive-parent rejection.
* timestamp and payload validation.
* immutable Event behavior.
* transactional `event.accepted` publication.

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
* every accepted Event belongs to one validated Organization, Project, Application, and Environment.
* all external input is normalized into the canonical Event model.
* ingestion is idempotent.
* source-level duplicate detection is separate from request idempotency.
* provider-specific schemas remain outside the domain contract.
* Signal derivation, Alerts, and Incidents are outside the first ingestion slice.
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
