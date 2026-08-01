# Notification Schema

## Purpose

This document defines the database schema for the Notification Platform.

It covers:

* Notification Channels.
* Notification Templates.
* Notification Policies.
* recipient preferences.
* Notifications.
* recipients.
* delivery attempts.
* acknowledgements.
* escalation state.
* provider references.
* lifecycle history.

The Notification Platform owns notification delivery state.

Alerts, Incidents, Workflows, Agents, and Plugins remain authoritative for the Resources that request notifications.

---

## Design Principles

* Every tenant Notification belongs to one Organization.
* Notification state remains separate from Alert or Incident state.
* Recipient resolution occurs before delivery.
* Delivery attempts are append-only.
* Templates are versioned.
* Preferences cannot override mandatory Notifications.
* Credentials remain outside Notification records.
* Provider callbacks must be authenticated and idempotent.
* Delivery retries must not create duplicate business effects.
* Sensitive content must be minimized.
* Cross-Organization delivery configuration is prohibited.
* Notification failures must remain observable.

---

## Schema Overview

```text id="c8js4y"
notification_channels
├── notification_channel_versions
└── notification_provider_endpoints

notification_templates
└── notification_template_versions

notification_policies
└── notification_policy_versions

notification_preferences

notifications
├── notification_recipients
│   ├── notification_delivery_attempts
│   └── notification_acknowledgements
├── notification_provider_callbacks
├── notification_escalations
└── notification_lifecycle_history
```

---

# Notification Channels

## `notification_channels`

Represents an Organization-specific delivery Channel.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `channel_type`           | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `active_version_id`      | UUID        | Optional    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Supported Channel Types:

* `email`
* `sms`
* `push`
* `chat`
* `webhook`
* `voice`
* `in_app`
* `status_page`
* `custom`

Supported statuses:

* `draft`
* `validating`
* `active`
* `degraded`
* `suspended`
* `archived`

Constraints:

```text id="4gyc9r"
UNIQUE (organization_id, id)
UNIQUE (organization_id, name)
```

---

## `notification_channel_versions`

Stores immutable Channel configuration Versions.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `notification_channel_id` | UUID        | Required    |
| `version_number`          | BIGINT      | Required    |
| `provider`                | VARCHAR     | Required    |
| `configuration`           | JSONB       | Required    |
| `credential_reference`    | VARCHAR     | Optional    |
| `sender_identity`         | VARCHAR     | Optional    |
| `status`                  | VARCHAR     | Required    |
| `created_by_identity_id`  | UUID        | Required    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `activated_at`            | TIMESTAMPTZ | Optional    |
| `superseded_at`           | TIMESTAMPTZ | Optional    |

Supported statuses:

* `draft`
* `validated`
* `active`
* `superseded`
* `rejected`

Constraints:

```text id="fp891s"
UNIQUE (
    organization_id,
    notification_channel_id,
    version_number
)
```

Channel Versions are immutable after activation.

Secret values must not appear inside `configuration`.

---

## `notification_provider_endpoints`

Stores external provider destination metadata.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `notification_channel_id` | UUID        | Required    |
| `endpoint_type`           | VARCHAR     | Required    |
| `endpoint_reference`      | VARCHAR     | Required    |
| `status`                  | VARCHAR     | Required    |
| `verified_at`             | TIMESTAMPTZ | Optional    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `updated_at`              | TIMESTAMPTZ | Required    |

Examples include:

* Email sender domains.
* SMS sender identifiers.
* Chat workspaces.
* webhook endpoints.
* status page integrations.

Sensitive endpoint values should be encrypted or stored as secret references where required.

---

# Notification Templates

## `notification_templates`

Represents one logical Notification Template.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `slug`                   | VARCHAR     | Required    |
| `notification_type`      | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `active_version_id`      | UUID        | Optional    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Possible Notification Types:

* `alert_created`
* `alert_escalated`
* `incident_declared`
* `incident_updated`
* `incident_resolved`
* `workflow_task`
* `approval_requested`
* `agent_recommendation`
* `plugin_failure`
* `security_event`
* `administrative`
* `custom`

Constraints:

```text id="uj9pg2"
UNIQUE (organization_id, slug)
```

---

## `notification_template_versions`

Stores immutable Template content.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `notification_template_id` | UUID        | Required    |
| `version_number`           | BIGINT      | Required    |
| `subject_template`         | TEXT        | Optional    |
| `body_template`            | TEXT        | Required    |
| `content_format`           | VARCHAR     | Required    |
| `variable_schema`          | JSONB       | Required    |
| `channel_overrides`        | JSONB       | Optional    |
| `sensitivity`              | VARCHAR     | Required    |
| `created_by_identity_id`   | UUID        | Required    |
| `created_at`               | TIMESTAMPTZ | Required    |
| `activated_at`             | TIMESTAMPTZ | Optional    |

Supported content formats:

* `plain_text`
* `markdown`
* `html`
* `structured_json`

Constraints:

```text id="289mfh"
UNIQUE (
    organization_id,
    notification_template_id,
    version_number
)
```

Template Versions are immutable.

---

# Notification Policies

## `notification_policies`

Represents one routing and delivery Policy.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `name`                   | VARCHAR     | Required    |
| `slug`                   | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `active_version_id`      | UUID        | Optional    |
| `created_by_identity_id` | UUID        | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Supported statuses:

* `draft`
* `active`
* `suspended`
* `archived`

Constraints:

```text id="f7jc3n"
UNIQUE (organization_id, slug)
```

---

## `notification_policy_versions`

Stores immutable routing logic.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `notification_policy_id`   | UUID        | Required    |
| `version_number`           | BIGINT      | Required    |
| `trigger_definition`       | JSONB       | Required    |
| `recipient_definition`     | JSONB       | Required    |
| `channel_definition`       | JSONB       | Required    |
| `escalation_definition`    | JSONB       | Optional    |
| `schedule_definition`      | JSONB       | Optional    |
| `deduplication_definition` | JSONB       | Required    |
| `priority_definition`      | JSONB       | Required    |
| `created_by_identity_id`   | UUID        | Required    |
| `created_at`               | TIMESTAMPTZ | Required    |
| `activated_at`             | TIMESTAMPTZ | Optional    |

Constraints:

```text id="ppp3z6"
UNIQUE (
    organization_id,
    notification_policy_id,
    version_number
)
```

Policy Versions are immutable after activation.

---

# Notification Preferences

## `notification_preferences`

Stores recipient-specific preferences.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `identity_id`       | UUID        | Required    |
| `notification_type` | VARCHAR     | Required    |
| `channel_type`      | VARCHAR     | Required    |
| `enabled`           | BOOLEAN     | Required    |
| `quiet_hours`       | JSONB       | Optional    |
| `minimum_severity`  | VARCHAR     | Optional    |
| `updated_at`        | TIMESTAMPTZ | Required    |
| `version`           | BIGINT      | Required    |

Constraints:

```text id="k0icqf"
UNIQUE (
    organization_id,
    identity_id,
    notification_type,
    channel_type
)
```

Preferences may control optional Notifications.

They must not disable:

* Mandatory security Notifications.
* required Incident escalation.
* compliance Notifications.
* protected administrative Notifications.

---

# Notifications

## `notifications`

Stores one logical Notification request.

| Column                 | Type        | Rules       |
| ---------------------- | ----------- | ----------- |
| `id`                   | UUID        | Primary key |
| `organization_id`      | UUID        | Required    |
| `notification_type`    | VARCHAR     | Required    |
| `source_resource_type` | VARCHAR     | Required    |
| `source_resource_id`   | UUID        | Required    |
| `template_version_id`  | UUID        | Optional    |
| `policy_version_id`    | UUID        | Optional    |
| `status`               | VARCHAR     | Required    |
| `priority`             | VARCHAR     | Required    |
| `severity`             | VARCHAR     | Optional    |
| `subject`              | TEXT        | Optional    |
| `content`              | TEXT        | Required    |
| `content_format`       | VARCHAR     | Required    |
| `deduplication_key`    | VARCHAR     | Optional    |
| `scheduled_at`         | TIMESTAMPTZ | Optional    |
| `expires_at`           | TIMESTAMPTZ | Optional    |
| `created_by_type`      | VARCHAR     | Required    |
| `created_by_id`        | UUID        | Optional    |
| `created_at`           | TIMESTAMPTZ | Required    |
| `updated_at`           | TIMESTAMPTZ | Required    |
| `completed_at`         | TIMESTAMPTZ | Optional    |
| `version`              | BIGINT      | Required    |

Supported statuses:

* `created`
* `scheduled`
* `processing`
* `partially_delivered`
* `delivered`
* `failed`
* `cancelled`
* `expired`

Suggested priority values:

* `urgent`
* `high`
* `normal`
* `low`

Constraints:

```text id="9tcx8n"
UNIQUE (organization_id, id)
```

Optional deduplication rule:

```text id="h41f4r"
UNIQUE (
    organization_id,
    deduplication_key
)
WHERE deduplication_key IS NOT NULL
```

---

## Notification Content

Rendered Notification content may be stored to preserve exactly what was sent.

Content must:

* Exclude credentials.
* minimize sensitive evidence.
* respect recipient visibility.
* avoid unrestricted operational payloads.
* comply with Channel size limits.

For highly sensitive Notifications, the delivery may contain only a safe summary and a link to Sentinel.

---

# Notification Recipients

## `notification_recipients`

Represents one resolved recipient and Channel destination.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `notification_id`          | UUID        | Required    |
| `recipient_type`           | VARCHAR     | Required    |
| `recipient_id`             | UUID        | Optional    |
| `channel_id`               | UUID        | Required    |
| `destination_reference`    | VARCHAR     | Required    |
| `status`                   | VARCHAR     | Required    |
| `requires_acknowledgement` | BOOLEAN     | Required    |
| `resolved_at`              | TIMESTAMPTZ | Required    |
| `delivered_at`             | TIMESTAMPTZ | Optional    |
| `acknowledged_at`          | TIMESTAMPTZ | Optional    |
| `failed_at`                | TIMESTAMPTZ | Optional    |
| `version`                  | BIGINT      | Required    |

Supported recipient types:

* `identity`
* `membership`
* `team`
* `on_call_schedule`
* `external_contact`
* `webhook`
* `status_page`

Supported statuses:

* `pending`
* `queued`
* `sending`
* `delivered`
* `acknowledged`
* `failed`
* `cancelled`
* `expired`

The destination reference should be encrypted or tokenized where appropriate.

---

# Delivery Attempts

## `notification_delivery_attempts`

Stores each provider delivery attempt.

| Column                      | Type        | Rules       |
| --------------------------- | ----------- | ----------- |
| `id`                        | UUID        | Primary key |
| `organization_id`           | UUID        | Required    |
| `notification_recipient_id` | UUID        | Required    |
| `attempt_number`            | INTEGER     | Required    |
| `provider`                  | VARCHAR     | Required    |
| `provider_message_id`       | VARCHAR     | Optional    |
| `status`                    | VARCHAR     | Required    |
| `started_at`                | TIMESTAMPTZ | Required    |
| `completed_at`              | TIMESTAMPTZ | Optional    |
| `provider_status_code`      | VARCHAR     | Optional    |
| `failure_code`              | VARCHAR     | Optional    |
| `failure_message`           | TEXT        | Optional    |
| `retryable`                 | BOOLEAN     | Optional    |
| `next_retry_at`             | TIMESTAMPTZ | Optional    |

Supported statuses:

* `queued`
* `sending`
* `accepted`
* `delivered`
* `failed`
* `rejected`
* `timed_out`
* `cancelled`

Constraints:

```text id="2j7c35"
UNIQUE (
    organization_id,
    notification_recipient_id,
    attempt_number
)
```

Delivery attempts are append-only.

---

# Acknowledgements

## `notification_acknowledgements`

Stores acknowledgement of a delivered Notification.

| Column                        | Type        | Rules       |
| ----------------------------- | ----------- | ----------- |
| `id`                          | UUID        | Primary key |
| `organization_id`             | UUID        | Required    |
| `notification_id`             | UUID        | Required    |
| `notification_recipient_id`   | UUID        | Required    |
| `acknowledged_by_identity_id` | UUID        | Required    |
| `acknowledgement_type`        | VARCHAR     | Required    |
| `note`                        | TEXT        | Optional    |
| `acknowledged_at`             | TIMESTAMPTZ | Required    |

Supported acknowledgement types:

* `received`
* `accepted`
* `taking_ownership`
* `reviewed`
* `declined`

Acknowledgement does not automatically change the source Alert, Incident, or Workflow.

---

# Provider Callbacks

## `notification_provider_callbacks`

Stores authenticated provider delivery updates.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `notification_channel_id` | UUID        | Required    |
| `provider_event_id`       | VARCHAR     | Required    |
| `provider_message_id`     | VARCHAR     | Optional    |
| `event_type`              | VARCHAR     | Required    |
| `payload_hash`            | VARCHAR     | Required    |
| `status`                  | VARCHAR     | Required    |
| `received_at`             | TIMESTAMPTZ | Required    |
| `processed_at`            | TIMESTAMPTZ | Optional    |
| `failure_code`            | VARCHAR     | Optional    |

Constraints:

```text id="s1u9dj"
UNIQUE (
    notification_channel_id,
    provider_event_id
)
```

Provider callbacks must be verified before processing.

---

# Notification Escalations

## `notification_escalations`

Stores escalation activity caused by delivery or acknowledgement conditions.

| Column                      | Type        | Rules       |
| --------------------------- | ----------- | ----------- |
| `id`                        | UUID        | Primary key |
| `organization_id`           | UUID        | Required    |
| `notification_id`           | UUID        | Required    |
| `notification_recipient_id` | UUID        | Optional    |
| `escalation_level`          | INTEGER     | Required    |
| `trigger_type`              | VARCHAR     | Required    |
| `reason`                    | TEXT        | Required    |
| `target_definition`         | JSONB       | Required    |
| `triggered_at`              | TIMESTAMPTZ | Required    |
| `resolved_at`               | TIMESTAMPTZ | Optional    |

Possible triggers:

* `delivery_failed`
* `delivery_timed_out`
* `acknowledgement_missing`
* `severity_increased`
* `priority_increased`
* `manual`
* `policy`

Escalation may create additional Notification recipients or a new Notification.

---

# Lifecycle History

## `notification_lifecycle_history`

Stores immutable Notification state changes.

| Column            | Type        | Rules       |
| ----------------- | ----------- | ----------- |
| `id`              | UUID        | Primary key |
| `organization_id` | UUID        | Required    |
| `notification_id` | UUID        | Required    |
| `previous_status` | VARCHAR     | Optional    |
| `new_status`      | VARCHAR     | Required    |
| `reason`          | TEXT        | Optional    |
| `changed_by_type` | VARCHAR     | Required    |
| `changed_by_id`   | UUID        | Optional    |
| `changed_at`      | TIMESTAMPTZ | Required    |
| `correlation_id`  | UUID        | Optional    |

Lifecycle history is append-only.

---

# Notification Creation Transaction

Creating a Notification should normally persist:

1. Notification.
2. resolved recipients.
3. initial lifecycle history.
4. Outbox message.

Provider delivery occurs after commit.

Recipient resolution failures should be represented explicitly.

---

# Delivery Processing

Delivery processing should:

1. Load the Notification and recipient.
2. confirm both remain eligible.
3. verify Channel status.
4. create a delivery attempt.
5. call the provider.
6. record the provider result.
7. update recipient state.
8. update Notification aggregate state.
9. publish resulting events.

Provider calls must not remain open inside long database transactions.

---

# Retry Rules

Retries must consider:

* Provider response.
* attempt count.
* Notification expiration.
* recipient state.
* Channel health.
* idempotency support.
* Policy limits.

Retries must not occur after:

* Successful delivery.
* cancellation.
* expiration.
* permanent provider rejection.
* recipient acknowledgement when no further delivery is required.

---

# Aggregate Notification Status

Notification status may be calculated from recipient states.

Examples:

* All recipients delivered → `delivered`
* Some delivered and some permanently failed → `partially_delivered`
* All failed → `failed`
* Still waiting for deliveries → `processing`

The logical Notification status must not hide individual recipient failures.

---

# Optimistic Concurrency

Mutable Notification and recipient records use `version`.

Example:

```sql id="piufbg"
UPDATE notification_recipients
SET
    status = :new_status,
    version = version + 1
WHERE organization_id = :organization_id
  AND id = :recipient_id
  AND version = :expected_version;
```

A failed Version match produces a concurrency conflict.

Delivery attempts and acknowledgements remain immutable.

---

# Tenant Isolation

Every Organization-specific Notification table includes `organization_id`.

The schema must prevent:

* Use of another Organization’s Channel.
* use of another Organization’s Template or Policy.
* delivery to another Organization’s Team or Membership.
* cross-Organization provider callbacks.
* acknowledgements across Organizations.
* escalation targets across Organizations.

External recipients must be explicitly permitted by Organization Policy.

---

# Required Indexes

Recommended indexes include:

```text id="w2rgkj"
notification_channels (
    organization_id,
    status,
    channel_type
)

notification_templates (
    organization_id,
    status,
    notification_type
)

notification_policies (
    organization_id,
    status
)

notification_preferences (
    organization_id,
    identity_id
)

notifications (
    organization_id,
    status,
    created_at
)

notifications (
    organization_id,
    source_resource_type,
    source_resource_id
)

notifications (
    organization_id,
    scheduled_at,
    status
)

notification_recipients (
    organization_id,
    notification_id,
    status
)

notification_recipients (
    organization_id,
    recipient_type,
    recipient_id,
    status
)

notification_delivery_attempts (
    organization_id,
    notification_recipient_id,
    attempt_number
)

notification_delivery_attempts (
    organization_id,
    status,
    next_retry_at
)

notification_provider_callbacks (
    notification_channel_id,
    provider_event_id
)

notification_escalations (
    organization_id,
    notification_id,
    triggered_at
)
```

---

# Retention

Notification retention applies to:

* Notifications.
* rendered content.
* recipient records.
* delivery attempts.
* acknowledgements.
* provider callback metadata.
* escalation history.
* lifecycle history.

Retention may vary by:

* Channel.
* Notification Type.
* sensitivity.
* source Resource.
* legal requirements.
* Organization Policy.

Sensitive rendered content may use shorter retention than delivery metadata.

---

# Security Requirements

* Provider credentials must use secret references.
* Sensitive content must be minimized.
* destinations must be protected.
* provider callbacks must be authenticated.
* Templates must escape or sanitize untrusted variables.
* external recipients require explicit authorization.
* Notifications must not disclose Resource existence to unauthorized recipients.
* cross-Organization delivery must be rejected.
* Channel failure messages must not expose credentials.

---

# Audit Requirements

Auditable operations include:

* Channel creation.
* credential rotation.
* Template activation.
* Policy activation.
* mandatory preference changes.
* manual Notification creation.
* cancellation.
* escalation override.
* external recipient configuration.
* sensitive Notification delivery.
* provider callback rejection.

Normal high-volume delivery attempts may rely on Notification execution records rather than general Audit Records.

---

# Business Rules

* Every tenant Notification belongs to exactly one Organization.
* Notification lifecycle remains separate from source Resource lifecycle.
* Every resolved recipient has an explicit Channel and destination.
* Delivery attempts are immutable.
* Template Versions are immutable.
* Notification Policy Versions are immutable.
* Preferences cannot disable mandatory Notifications.
* Acknowledgement does not automatically resolve an Alert or Incident.
* Provider callbacks must be authenticated and idempotent.
* Retries must not duplicate successful delivery.
* Sensitive content must be minimized.
* External recipients require explicit authorization.
* Suspended Channels cannot begin normal delivery.
* Cross-Organization relationships are prohibited.
* Missing or conflicting Organization ownership results in rejection.

---

# Success Criteria

The Notification schema is successful when:

* Notifications remain traceable to their source Resources.
* recipient resolution remains explicit.
* delivery state can be queried per recipient.
* retries remain safe and observable.
* acknowledgements remain attributable.
* Templates and Policies remain versioned.
* mandatory Notifications cannot be disabled accidentally.
* provider callbacks update the correct delivery.
* sensitive content remains protected.
* Notification failures can trigger escalation.
* tenant isolation is preserved.
* Channels can be changed without modifying source domains.

---

# Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/06-alert-schema.md`
* `docs/database/07-incident-schema.md`
* `docs/database/09-agent-schema.md`
* `docs/database/10-plugin-schema.md`
* `docs/database/12-workflow-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/15-retention-archival.md`
* `docs/functional/11-notification-platform.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-007-event-driven-architecture.md`
* `docs/adr/ADR-008-security-policy-layer.md`
