# Audit Schema

## Purpose

This document defines the database schema for platform Audit Records.

Audit data explains:

* Who or what performed an action.
* Which Organization was affected.
* Which Resource was targeted.
* What operation was attempted.
* Whether it succeeded or failed.
* Why the action was performed.
* When and where it occurred.
* Which request, Workflow, Agent, or Plugin caused it.

Audit Records support:

* Security investigation.
* compliance.
* administrative oversight.
* incident reconstruction.
* privileged-access review.
* accountability.

Audit records are append-only.

---

## Scope

The Audit capability records security-sensitive and administrative activity.

Examples include:

* Authentication events.
* authorization decisions.
* Membership and Role changes.
* credential operations.
* configuration changes.
* privileged access.
* data exports.
* retention changes.
* Plugin and Agent governance.
* protected Workflow Actions.
* sensitive Resource access.
* security-policy changes.

Audit does not replace:

* Domain lifecycle history.
* Incident timelines.
* Workflow history.
* Agent execution history.
* Plugin execution history.
* application Logs.

---

## Design Principles

* Audit Records are immutable.
* Every tenant Audit Record includes `organization_id`.
* Human and non-human identities remain distinguishable.
* Successful and failed protected actions are recorded.
* Sensitive values are not copied into Audit payloads.
* Audit writes must not silently fail.
* Audit data must remain searchable by Organization, actor, action, Resource, and time.
* Audit retention is controlled by Policy.
* Legal Hold prevents required Audit deletion.
* Audit access is itself auditable.
* Cross-Organization visibility is prohibited by default.
* Audit timestamps use UTC.

---

## Schema Overview

```text id="1fhcyr"
audit_records
├── audit_resource_references
├── audit_changes
├── audit_metadata
├── audit_access_records
├── privileged_access_sessions
└── audit_export_records
```

Privileged access session ownership is also described in:

```text id="mtsf61"
docs/database/02-identity-access-schema.md
```

---

# Audit Records

## `audit_records`

Stores one immutable Audit event.

| Column                 | Type        | Rules                                  |
| ---------------------- | ----------- | -------------------------------------- |
| `id`                   | UUID        | Primary key                            |
| `organization_id`      | UUID        | Nullable only for platform-wide events |
| `event_type`           | VARCHAR     | Required                               |
| `action`               | VARCHAR     | Required                               |
| `outcome`              | VARCHAR     | Required                               |
| `actor_type`           | VARCHAR     | Required                               |
| `actor_id`             | UUID        | Optional                               |
| `actor_display`        | VARCHAR     | Optional                               |
| `target_type`          | VARCHAR     | Optional                               |
| `target_id`            | UUID        | Optional                               |
| `risk_level`           | VARCHAR     | Required                               |
| `reason`               | TEXT        | Optional                               |
| `failure_code`         | VARCHAR     | Optional                               |
| `request_id`           | UUID        | Optional                               |
| `correlation_id`       | UUID        | Optional                               |
| `causation_id`         | UUID        | Optional                               |
| `session_id`           | UUID        | Optional                               |
| `workflow_instance_id` | UUID        | Optional                               |
| `agent_execution_id`   | UUID        | Optional                               |
| `plugin_execution_id`  | UUID        | Optional                               |
| `source_ip_hash`       | VARCHAR     | Optional                               |
| `user_agent_hash`      | VARCHAR     | Optional                               |
| `occurred_at`          | TIMESTAMPTZ | Required                               |
| `recorded_at`          | TIMESTAMPTZ | Required                               |
| `integrity_hash`       | VARCHAR     | Optional                               |

Supported outcomes:

* `succeeded`
* `failed`
* `denied`
* `partially_succeeded`
* `cancelled`
* `expired`

Supported actor types:

* `user`
* `api_client`
* `service`
* `agent`
* `plugin`
* `workflow`
* `platform`
* `support`
* `emergency`
* `anonymous`

Suggested risk levels:

* `critical`
* `high`
* `medium`
* `low`
* `informational`

Constraints:

```text id="47qx8z"
UNIQUE (organization_id, id)
```

Platform-wide events may use a null `organization_id` only when no tenant is affected.

---

## Event Types

Audit event types should use stable business-oriented names.

Examples:

```text id="mb5qpm"
authentication.succeeded
authentication.failed
session.revoked

membership.created
membership.suspended
membership.removed

role.assigned
role.revoked
permission.changed

credential.created
credential.rotated
credential.revoked

organization.suspended
organization.deletion_requested

plugin.installed
plugin.capability_granted
plugin.suspended

agent.activated
agent.execution_requested
agent.recommendation_approved

workflow.action_authorized
workflow.action_executed

incident.severity_changed
knowledge.published

data.export_requested
data.export_downloaded

retention.policy_changed
legal_hold.created
legal_hold.released

privileged_access.started
privileged_access.ended
```

Event names should remain stable after publication.

---

# Resource References

## `audit_resource_references`

Connects one Audit Record to additional affected Resources.

| Column              | Type        | Rules                        |
| ------------------- | ----------- | ---------------------------- |
| `id`                | UUID        | Primary key                  |
| `organization_id`   | UUID        | Optional for platform events |
| `audit_record_id`   | UUID        | Required                     |
| `resource_type`     | VARCHAR     | Required                     |
| `resource_id`       | UUID        | Required                     |
| `relationship_type` | VARCHAR     | Required                     |
| `created_at`        | TIMESTAMPTZ | Required                     |

Possible relationship types:

* `target`
* `parent`
* `related`
* `source`
* `result`
* `evidence`
* `scope`

This table allows one Audit Record to describe an action affecting multiple Resources.

---

# Change Records

## `audit_changes`

Stores safe before-and-after summaries for changed fields.

| Column                 | Type        | Rules                        |
| ---------------------- | ----------- | ---------------------------- |
| `id`                   | UUID        | Primary key                  |
| `organization_id`      | UUID        | Optional for platform events |
| `audit_record_id`      | UUID        | Required                     |
| `field_name`           | VARCHAR     | Required                     |
| `previous_value`       | JSONB       | Optional                     |
| `new_value`            | JSONB       | Optional                     |
| `value_classification` | VARCHAR     | Required                     |
| `is_redacted`          | BOOLEAN     | Required                     |
| `created_at`           | TIMESTAMPTZ | Required                     |

Possible classifications:

* `public`
* `internal`
* `confidential`
* `restricted`
* `secret`

Secret values must never be stored.

For sensitive fields, Audit should record a safe statement such as:

```text id="oj2c2x"
credential_reference changed
```

rather than the credential value.

---

# Audit Metadata

## `audit_metadata`

Stores additional structured, non-secret context.

| Column            | Type        | Rules    |
| ----------------- | ----------- | -------- |
| `organization_id` | UUID        | Optional |
| `audit_record_id` | UUID        | Required |
| `metadata_key`    | VARCHAR     | Required |
| `metadata_value`  | JSONB       | Required |
| `classification`  | VARCHAR     | Required |
| `created_at`      | TIMESTAMPTZ | Required |

Primary key:

```text id="08w78p"
PRIMARY KEY (
    audit_record_id,
    metadata_key
)
```

Metadata may include:

* API route.
* authentication method.
* authorization Policy.
* approval identifier.
* deployment identifier.
* requested scope.
* provider name.
* operation Version.

Metadata must not contain:

* Passwords.
* API Keys.
* access tokens.
* raw Session tokens.
* private encryption keys.
* unrestricted provider payloads.

---

# Audit Access Records

## `audit_access_records`

Records access to Audit data.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Optional    |
| `accessed_by_identity_id` | UUID        | Required    |
| `access_type`             | VARCHAR     | Required    |
| `scope_definition`        | JSONB       | Required    |
| `reason`                  | TEXT        | Required    |
| `result_count`            | BIGINT      | Optional    |
| `occurred_at`             | TIMESTAMPTZ | Required    |
| `correlation_id`          | UUID        | Optional    |

Supported access types:

* `view`
* `search`
* `export`
* `administrative_review`
* `security_investigation`
* `compliance_review`

Reading sensitive Audit data must itself be auditable.

---

# Privileged Access Sessions

## `privileged_access_sessions`

Stores support, emergency, recovery, or security-response access.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `identity_id`             | UUID        | Required    |
| `access_type`             | VARCHAR     | Required    |
| `reason`                  | TEXT        | Required    |
| `scope_definition`        | JSONB       | Required    |
| `status`                  | VARCHAR     | Required    |
| `approved_by_identity_id` | UUID        | Optional    |
| `started_at`              | TIMESTAMPTZ | Required    |
| `expires_at`              | TIMESTAMPTZ | Required    |
| `ended_at`                | TIMESTAMPTZ | Optional    |
| `revoked_at`              | TIMESTAMPTZ | Optional    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `version`                 | BIGINT      | Required    |

Supported access types:

* `support`
* `emergency`
* `security_response`
* `recovery`
* `migration`

Supported statuses:

* `requested`
* `approved`
* `active`
* `ended`
* `expired`
* `revoked`
* `rejected`

Every action performed during the session must reference its privileged-access session.

---

# Audit Exports

## `audit_export_records`

Stores Audit export requests and delivery state.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `requested_by_identity_id` | UUID        | Required    |
| `scope_definition`         | JSONB       | Required    |
| `reason`                   | TEXT        | Required    |
| `status`                   | VARCHAR     | Required    |
| `storage_reference`        | VARCHAR     | Optional    |
| `file_hash`                | VARCHAR     | Optional    |
| `requested_at`             | TIMESTAMPTZ | Required    |
| `completed_at`             | TIMESTAMPTZ | Optional    |
| `expires_at`               | TIMESTAMPTZ | Optional    |
| `downloaded_at`            | TIMESTAMPTZ | Optional    |
| `failure_code`             | VARCHAR     | Optional    |

Supported statuses:

* `requested`
* `authorized`
* `processing`
* `completed`
* `failed`
* `expired`
* `cancelled`

Export files must use:

* Encrypted storage.
* Organization-scoped paths.
* time-limited access.
* checksum validation.
* controlled deletion.

---

# Audit Creation

An Audit Record should include:

1. Organization context.
2. actor identity.
3. action and event type.
4. target Resource.
5. outcome.
6. reason where required.
7. request and correlation identifiers.
8. safe changed-field summaries.
9. additional Resource references.
10. occurrence and recording timestamps.

Audit creation must not rely only on application Logs.

---

# Transaction Boundaries

When a protected domain action succeeds, the preferred transaction includes:

1. Authoritative domain change.
2. Domain history.
3. Audit Record.
4. Outbox message.
5. Commit.

This prevents a successful protected action from being committed without its required Audit Record.

For high-volume operations, asynchronous Audit recording may be used only when:

* The source event is durably recorded.
* delivery is reliable.
* failure is observable.
* reconstruction is possible.
* the action is not required to be atomically audited.

---

# Failed and Denied Operations

Failed or denied protected operations may occur before a domain transaction exists.

These should still produce Audit Records for events such as:

* Authentication failure.
* authorization denial.
* tenant-isolation violation.
* invalid privileged-access attempt.
* credential misuse.
* rejected export.
* rejected Plugin or Agent action.
* invalid approval attempt.

Sensitive failure details must not be exposed to the caller unnecessarily.

---

# Domain History and Audit

Domain History records business state changes.

Audit Records explain accountability and security context.

Example:

```text id="76u350"
Incident lifecycle history:
Status changed from Investigating to Resolved.

Audit Record:
User X resolved Incident Y through request Z after approval A.
```

Both records may be created for the same operation.

They serve different purposes.

---

# Integrity Protection

Audit integrity may use:

* Append-only database permissions.
* restricted database Roles.
* record hashes.
* chained hashes.
* signed export files.
* immutable archive storage.
* periodic integrity verification.

An optional integrity chain may include:

```text id="swln11"
previous_integrity_hash
integrity_hash
```

Hashing improves tamper detection.

It does not replace access control or secure storage.

---

# Immutability

Audit Records must not be updated after creation.

Corrections should create a new Audit Record that references the original.

Physical deletion is allowed only through governed retention processing.

Administrative users must not have normal update permission on Audit tables.

---

# Tenant Isolation

Tenant Audit queries must always include `organization_id`.

Organization administrators may access only authorized records belonging to their Organization.

Platform-wide operators may access multiple Organizations only through:

* Privileged access.
* defined purpose.
* explicit authorization.
* restricted scope.
* complete Audit recording.

Audit exports must belong to one Organization unless a governed platform investigation requires broader scope.

---

# Sensitive Data

Audit data must avoid copying:

* Credentials.
* raw tokens.
* passwords.
* private keys.
* full sensitive Event payloads.
* full Agent prompts or provider responses.
* confidential Notification content.
* personal data not needed for accountability.

Where necessary, store:

* Resource identifiers.
* hashes.
* classifications.
* safe summaries.
* secret references.
* redacted values.

---

# Required Indexes

Recommended indexes include:

```text id="kd28lm"
audit_records (
    organization_id,
    occurred_at
)

audit_records (
    organization_id,
    event_type,
    occurred_at
)

audit_records (
    organization_id,
    action,
    occurred_at
)

audit_records (
    organization_id,
    actor_type,
    actor_id,
    occurred_at
)

audit_records (
    organization_id,
    target_type,
    target_id,
    occurred_at
)

audit_records (
    organization_id,
    outcome,
    occurred_at
)

audit_records (
    organization_id,
    risk_level,
    occurred_at
)

audit_records (
    correlation_id
)

audit_resource_references (
    organization_id,
    resource_type,
    resource_id
)

audit_access_records (
    organization_id,
    accessed_by_identity_id,
    occurred_at
)

privileged_access_sessions (
    organization_id,
    identity_id,
    status
)

audit_export_records (
    organization_id,
    status,
    requested_at
)
```

Indexes must support investigation queries without creating excessive write overhead.

---

# Partitioning

The `audit_records` table may become large and append-heavy.

Future partitioning may use:

* `occurred_at`
* storage region
* dedicated tenant placement

Time-based partitioning is the preferred initial option when needed.

Partitioning must preserve:

* Organization filtering.
* retention processing.
* integrity verification.
* export operations.
* Legal Holds.

---

# Retention

Audit retention depends on:

* Organization Policy.
* contract.
* regulation.
* security requirements.
* event risk.
* investigation status.
* Legal Hold.

Possible retention categories include:

| Category               | Example                                        |
| ---------------------- | ---------------------------------------------- |
| Short                  | Routine low-risk access events                 |
| Standard               | Administrative configuration changes           |
| Extended               | Credential, Role, and privileged-access events |
| Permanent or regulated | Events required by law or contract             |

Retention rules are defined in:

```text id="5hp8jv"
docs/database/15-retention-archival.md
```

---

# Archival

Older Audit Records may move to lower-cost immutable storage.

Archived Audit data must preserve:

* Organization ownership.
* event identifiers.
* timestamps.
* actor attribution.
* Resource references.
* integrity metadata.
* retention deadlines.
* access restrictions.

Archived data must remain retrievable for authorized investigations.

---

# Legal Hold

Audit Records under Legal Hold must not be deleted.

Legal Hold may apply by:

* Organization.
* actor.
* Resource.
* Incident.
* event type.
* date range.
* investigation.
* export.

Legal Hold creation and release must themselves be audited.

---

# Backup and Recovery

Audit backup must support:

* Encryption.
* point-in-time recovery.
* integrity validation.
* restoration testing.
* retention compliance.
* controlled access.

Audit restoration must not allow restored records to become editable.

---

# Monitoring

Sentinel should monitor:

* Audit write failures.
* delayed Audit processing.
* missing actor context.
* missing Organization context.
* invalid event types.
* integrity-check failures.
* unauthorized Audit access.
* unusual export volume.
* privileged-access misuse.
* retention deletion failures.

Critical Audit pipeline failures should create operational Alerts.

---

# Prohibited Practices

The following are prohibited:

* Updating Audit Records after creation.
* storing plaintext secrets.
* omitting Organization scope from tenant Audit queries.
* using application Logs as the only Audit source.
* deleting Audit data outside retention processing.
* allowing unrestricted platform-operator access.
* exporting Audit data without authorization.
* hiding failed or denied protected actions.
* copying full sensitive payloads unnecessarily.
* allowing identities to modify their own Audit history.
* treating Audit as domain lifecycle history.
* silently dropping Audit Records when storage fails.

---

# Business Rules

* Audit Records are append-only.
* Every tenant Audit Record includes `organization_id`.
* Platform-wide events may omit Organization only when no tenant is affected.
* Every protected action preserves actor attribution.
* Human, Agent, Plugin, Service, and Workflow actors remain distinguishable.
* Successful, failed, and denied protected actions may require Audit Records.
* Audit Records must not contain secrets.
* Domain History and Audit remain separate.
* Audit access is itself auditable.
* Privileged access is time-bounded and purpose-limited.
* Audit exports use encrypted, expiring access.
* Retention processing respects Legal Holds.
* Audit failures must remain observable.
* Cross-Organization visibility is denied by default.
* Missing required Audit persistence causes the protected operation to fail when atomic Audit is required.

---

# Success Criteria

The Audit schema is successful when:

* Protected actions remain attributable.
* security investigations can reconstruct activity.
* tenant Audit data remains isolated.
* privileged access remains visible.
* failed and denied actions remain traceable.
* sensitive values are not exposed.
* Audit records cannot be silently modified.
* large Audit volumes can be retained and searched.
* exports remain controlled.
* Legal Holds can prevent deletion.
* archived Audit data remains verifiable.
* Audit pipeline failures are detected.

---

# Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/09-agent-schema.md`
* `docs/database/10-plugin-schema.md`
* `docs/database/11-notification-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/database/16-indexing-strategy.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/functional/14-administration.md`
* `docs/functional/15-observability.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-012-evidence-based-operational-intelligence.md`
