# Notification Module

## Purpose

The Notification module owns Notification requests, routing, delivery attempts, recipient resolution, and delivery status.

It delivers operational messages without owning the business event that caused them.

---

## Owns

* Notification.
* Notification request.
* Notification Channel.
* Notification Template.
* recipient reference.
* delivery attempt.
* delivery status.
* retry state.
* provider response metadata.
* suppression and deduplication state.

---

## Does Not Own

* Alert or Incident lifecycle.
* Workflow state.
* User identity or Membership.
* provider credentials.
* Application or Environment configuration.
* business decisions about whether an Alert or Incident should exist.

Source modules request Notifications through explicit contracts or published events.

---

## Notification Resource

Important attributes:

```text
id
organization_id
source_type
source_id
channel_id
template_id
recipient
status
priority
scheduled_at
sent_at
delivered_at
failed_at
correlation_id
```

The primary identifier uses UUIDv7.

---

## Delivery Status

Suggested states:

```text
pending
scheduled
processing
sent
delivered
failed
cancelled
suppressed
```

Provider acceptance does not necessarily mean confirmed delivery.

---

## Commands

```text
RequestNotification
ScheduleNotification
CancelNotification
RetryNotification
CreateChannel
UpdateChannel
DisableChannel
CreateTemplate
UpdateTemplate
EnableTemplate
DisableTemplate
```

Commands must preserve tenant ownership, authorization, and idempotency.

---

## Queries

```text
GetNotification
ListNotifications
GetDeliveryAttempts
GetChannel
ListChannels
GetTemplate
ListTemplates
```

Queries must preserve Organization and sensitivity scope.

---

## Published Events

```text
notification.requested
notification.scheduled
notification.sent
notification.delivered
notification.failed
notification.cancelled
notification.suppressed
channel.disabled
```

Events must exclude secret values and unnecessary message content.

---

## Consumed Events

The module may consume:

```text
alert.created
alert.acknowledged
alert.resolved
incident.created
incident.severity_changed
incident.resolved
workflow.notification_requested
credential.revoked
organization.suspended
organization.archived
```

Event consumption must remain idempotent.

---

## Notification Request

A Notification request should include:

```text
organization_id
source_type
source_id
notification_type
recipient_scope
priority
template_reference
template_data
idempotency_key
correlation_id
```

The request must reference the source Resource rather than copy unrestricted domain data.

---

## Recipient Resolution

Recipients may be resolved from:

* Explicit authorized recipients.
* Incident responders.
* Application ownership.
* Organization notification settings.
* configured escalation rules.
* external addresses permitted by Policy.

Identity and Membership validation remains owned by the Identity and Access module.

---

## Channels

Supported Channel types may include:

```text
email
webhook
chat
sms
in_app
```

Each Channel belongs to one Organization unless it is a platform-owned system Channel.

Channel configuration stores secret references, never plaintext credentials.

---

## Templates

Templates define:

* Notification type.
* Channel compatibility.
* subject or title.
* message structure.
* supported variables.
* locale.
* Version.
* enabled status.

Templates must escape or sanitize untrusted content according to the delivery format.

---

## Routing

Routing may consider:

* Notification type.
* severity.
* Environment criticality.
* recipient preferences.
* quiet hours.
* escalation Policy.
* Channel availability.
* Organization plan limits.

Routing does not replace authorization or tenant isolation.

---

## Deduplication

Equivalent requests should not create repeated delivery during a configured window.

A deduplication key may include:

```text
organization_id
notification_type
source_type
source_id
recipient
channel_id
```

Deduplication must never cross Organization boundaries.

---

## Suppression

Notifications may be suppressed because of:

* Alert suppression.
* recipient preference.
* quiet hours.
* disabled Channel.
* duplicate request.
* Organization suspension.
* explicit operational Policy.

Suppression must record its reason.

Critical security or platform Notifications may override normal preference rules through explicit Policy.

---

## Delivery Execution

Delivery should occur asynchronously.

Typical flow:

```text
Notification requested
→ routing resolved
→ delivery job queued
→ provider called
→ result recorded
→ delivery event published
```

External provider calls must not occur inside the source module’s transaction.

---

## Retries

Transient failures may use bounded retries with backoff.

Examples:

* Provider timeout.
* rate limiting.
* temporary network failure.
* provider unavailability.

Permanent failures include:

* Invalid recipient.
* disabled Channel.
* revoked credential.
* rejected payload.
* unauthorized destination.

Permanent failures should not retry indefinitely.

---

## Provider Boundary

Provider integrations must use adapter interfaces.

Examples:

```text
EmailProvider
WebhookProvider
ChatProvider
SmsProvider
```

Domain and application code must not depend directly on provider SDKs.

---

## Idempotency

Provider delivery should use an idempotency key where supported.

Retrying the same Notification must not create uncontrolled duplicate deliveries.

The Notification identifier should remain stable across delivery attempts.

---

## Alert and Incident Integration

Alert and Incident modules may request Notifications.

The Notification module must not:

* change Alert state.
* acknowledge Alerts.
* assign Incident responders.
* resolve Incidents.
* reinterpret Incident severity.

It reports delivery outcomes only.

---

## Workflow Integration

Workflows may request or wait for Notification outcomes.

Workflow execution must use Notification commands and published events.

A Notification failure does not automatically fail the entire Workflow unless the Workflow Policy requires it.

---

## Repository Interfaces

```text
NotificationRepository
NotificationChannelRepository
NotificationTemplateRepository
DeliveryAttemptRepository
NotificationDeduplicationRepository
```

Other modules must not write Notification persistence directly.

---

## Transactions

Creating a Notification should atomically persist:

* Notification request.
* routing result where resolved synchronously.
* deduplication state.
* Outbox Record.

Provider delivery occurs outside that transaction.

---

## Authorization

Typical permissions:

```text
notification.read
notification.send
notification.retry
notification.cancel
notification.channel.manage
notification.template.manage
```

Sensitive recipient lists and message content may require stronger field-level authorization.

---

## Tenant Isolation

Every Notification, Channel, Template, recipient rule, and delivery attempt must remain Organization-scoped.

Webhook destinations and credentials must belong to the same Organization as the Notification request.

---

## Audit Requirements

Audit Records are required for:

* Channel creation and changes.
* credential-reference changes.
* Template changes.
* manual Notification requests.
* manual retries or cancellation.
* privileged recipient or content access.

Normal automated delivery history is preserved through Notification and delivery records.

---

## Prohibited Practices

The Notification module must not:

* Store plaintext provider credentials.
* send messages directly from Alert or Incident code.
* retry permanent failures indefinitely.
* expose sensitive message content in Logs.
* reuse Channel credentials across Organizations.
* treat provider acceptance as confirmed delivery.
* allow unrestricted external recipients.
* mutate the source Resource after delivery failure.

---

## Business Rules

* Every Notification has one owning Organization.
* source modules request delivery but do not call providers directly.
* Notification delivery is asynchronous and idempotent.
* recipient and Channel ownership are validated before delivery.
* templates are versioned and sanitized.
* retry behavior distinguishes transient and permanent failure.
* delivery history remains attributable and observable.
* Notification outcomes do not modify source-domain state automatically.

---

## Related Documents

* `docs/modules/07-alert-module.md`
* `docs/modules/08-incident-module.md`
* `docs/modules/14-identity-access-module.md`
* `docs/modules/17-workflow-module.md`
* `docs/database/11-notification-schema.md`
* `docs/api/16-notifications-api.md`
* `docs/security/05-secrets-credentials.md`
* `docs/deployment/06-workers-queues.md`
