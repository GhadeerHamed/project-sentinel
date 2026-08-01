# Notifications API

## Purpose

This document defines the API for creating and reading Notifications.

Notifications deliver operational messages through configured Channels such as:

* Email.
* SMS.
* chat.
* webhook.
* in-app delivery.

The Notification Platform owns delivery state but does not own the source Alert, Incident, Workflow, Agent, or Plugin.

---

## Notification Resource

```json
{
  "id": "0198f3d2-973d-7365-ad09-baf69f18258c",
  "type": "notification",
  "attributes": {
    "notification_type": "incident_declared",
    "status": "processing",
    "priority": "high",
    "source_resource_type": "incident",
    "source_resource_id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
    "created_at": "2026-08-01T16:30:00Z"
  }
}
```

Supported statuses:

* `created`
* `scheduled`
* `processing`
* `partially_delivered`
* `delivered`
* `failed`
* `cancelled`
* `expired`

---

## Endpoints

| Method | Endpoint                                                                              | Purpose                 |
| ------ | ------------------------------------------------------------------------------------- | ----------------------- |
| `GET`  | `/organizations/{organization_id}/notifications`                                      | List Notifications      |
| `POST` | `/organizations/{organization_id}/notifications`                                      | Create a Notification   |
| `GET`  | `/organizations/{organization_id}/notifications/{notification_id}`                    | Read a Notification     |
| `POST` | `/organizations/{organization_id}/notifications/{notification_id}/cancel`             | Cancel pending delivery |
| `POST` | `/organizations/{organization_id}/notifications/{notification_id}/retry`              | Retry failed delivery   |
| `POST` | `/organizations/{organization_id}/notification-recipients/{recipient_id}/acknowledge` | Acknowledge receipt     |

Channel and Template administration may use separate administrative endpoints.

---

## List Notifications

```http
GET /api/v1/organizations/{organization_id}/notifications
```

Supported filters:

* `notification_type`
* `status`
* `priority`
* `source_resource_type`
* `source_resource_id`
* `created_from`
* `created_to`

Default ordering:

```text
created_at DESC, id DESC
```

---

## Create Notification

```http
POST /api/v1/organizations/{organization_id}/notifications
Idempotency-Key: {key}
```

Request:

```json
{
  "notification_type": "incident_declared",
  "source_resource_type": "incident",
  "source_resource_id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "template_id": "0198f3ca-9e18-71ab-9d98-17f6d76d38a1",
  "priority": "high",
  "recipients": [
    {
      "recipient_type": "team",
      "recipient_id": "0198f3cb-1929-742a-af75-b7c92e793a52",
      "channel_id": "0198f3cc-286c-79db-a807-33f60d778ad8"
    }
  ]
}
```

Response:

```http
202 Accepted
```

The response references the created Notification.

Delivery occurs asynchronously.

---

## Read Notification

```http
GET /api/v1/organizations/{organization_id}/notifications/{notification_id}
```

The response may include:

* Resolved recipients.
* delivery state.
* delivery attempts.
* acknowledgement state.
* escalation state.
* source Resource reference.

Sensitive destination values should be masked.

---

## Cancel Notification

```http
POST /api/v1/organizations/{organization_id}/notifications/{notification_id}/cancel
If-Match: "3"
```

Request:

```json
{
  "reason": "Incident was cancelled."
}
```

Cancellation applies only to pending or retryable delivery work.

Already delivered messages cannot be recalled through this command.

---

## Retry Notification

```http
POST /api/v1/organizations/{organization_id}/notifications/{notification_id}/retry
Idempotency-Key: {key}
```

Retry is permitted only when:

* The Notification has failed recipients.
* retry limits have not been exceeded.
* the Notification has not expired.
* the Channel remains Active.
* the operation is authorized.

Successful recipients must not receive duplicate delivery unless explicitly requested.

---

## Acknowledge Delivery

```http
POST /api/v1/organizations/{organization_id}/notification-recipients/{recipient_id}/acknowledge
Idempotency-Key: {key}
```

Request:

```json
{
  "acknowledgement_type": "taking_ownership",
  "note": "I am responding to the incident."
}
```

Acknowledgement does not automatically resolve the related Alert, Incident, or Workflow.

---

## Recipient Types

Supported recipient types may include:

* `identity`
* `membership`
* `team`
* `on_call_schedule`
* `external_contact`
* `webhook`
* `status_page`

Recipients and Channels must belong to the same Organization unless an approved external-recipient Policy applies.

---

## Authorization

Typical permissions:

```text
notification.read
notification.create
notification.cancel
notification.retry
notification.acknowledge
```

Creating Notifications may also require access to:

* The source Resource.
* the selected Template.
* the selected Channel.
* each recipient scope.

---

## Errors

Common errors:

```text
notification.not_found
notification.invalid_state
notification.expired
notification.channel_unavailable
notification.recipient_invalid
notification.delivery_failed
notification.retry_not_allowed
notification.already_acknowledged
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Notification belongs to one Organization.
* The source Resource must belong to the same Organization.
* Delivery occurs asynchronously.
* Notification creation uses idempotency.
* Cancellation does not recall completed deliveries.
* Retries must not duplicate successful delivery.
* Acknowledgements are attributable and append-only.
* Preferences cannot disable mandatory security or escalation Notifications.
* Destination values must remain protected.
* Cross-Organization recipients and Channels are prohibited unless explicitly governed.

---

## Related Documents

* `docs/api/15-plugins-api.md`
* `docs/api/18-workflows-api.md`
* `docs/api/19-webhooks.md`
* `docs/database/11-notification-schema.md`
