# Webhooks

## Purpose

This document defines outbound Webhooks sent by Project Sentinel to external consumers.

Webhooks provide asynchronous notification of selected platform events.

They are public integration contracts and are versioned independently from internal domain events.

---

## Webhook Endpoint Resource

```json
{
  "id": "0198f3d2-973d-7365-ad09-baf69f18258c",
  "type": "webhook_endpoint",
  "attributes": {
    "name": "Operations Integration",
    "url": "https://example.com/sentinel/webhooks",
    "status": "active",
    "event_types": [
      "incident.declared",
      "incident.resolved"
    ],
    "version": 2
  }
}
```

Supported statuses:

* `pending_verification`
* `active`
* `suspended`
* `failed`
* `archived`

---

## Management Endpoints

| Method  | Endpoint                                                                         | Purpose                 |
| ------- | -------------------------------------------------------------------------------- | ----------------------- |
| `GET`   | `/organizations/{organization_id}/webhook-endpoints`                             | List endpoints          |
| `POST`  | `/organizations/{organization_id}/webhook-endpoints`                             | Create an endpoint      |
| `GET`   | `/organizations/{organization_id}/webhook-endpoints/{endpoint_id}`               | Read an endpoint        |
| `PATCH` | `/organizations/{organization_id}/webhook-endpoints/{endpoint_id}`               | Update configuration    |
| `POST`  | `/organizations/{organization_id}/webhook-endpoints/{endpoint_id}/rotate-secret` | Rotate signing secret   |
| `POST`  | `/organizations/{organization_id}/webhook-endpoints/{endpoint_id}/suspend`       | Suspend delivery        |
| `POST`  | `/organizations/{organization_id}/webhook-endpoints/{endpoint_id}/reactivate`    | Resume delivery         |
| `GET`   | `/organizations/{organization_id}/webhook-deliveries`                            | List deliveries         |
| `POST`  | `/organizations/{organization_id}/webhook-deliveries/{delivery_id}/retry`        | Retry a failed delivery |

---

## Create Endpoint

```http
POST /api/v1/organizations/{organization_id}/webhook-endpoints
Idempotency-Key: {key}
```

Request:

```json
{
  "name": "Operations Integration",
  "url": "https://example.com/sentinel/webhooks",
  "event_types": [
    "incident.declared",
    "incident.resolved"
  ]
}
```

The signing secret is returned only once.

Private network, loopback, and prohibited destinations must be rejected.

---

## Event Envelope

Every Webhook uses a stable envelope:

```json
{
  "id": "0198f3e0-2e40-75bc-8c5e-f77f326ae01c",
  "type": "incident.declared",
  "version": "1",
  "organization_id": "0198f3b4-2f11-7ca2-bcc1-f94671fac317",
  "occurred_at": "2026-08-01T16:30:00Z",
  "data": {
    "resource_id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
    "resource_type": "incident"
  }
}
```

The payload must contain only fields approved for the public Webhook contract.

---

## Event Types

Initial event types may include:

```text
alert.created
alert.acknowledged
alert.resolved

incident.declared
incident.updated
incident.resolved
incident.closed

workflow.started
workflow.completed
workflow.failed

agent.execution_completed
plugin.execution_failed
```

Consumers subscribe only to supported event types.

---

## Delivery Headers

Example:

```http
Content-Type: application/json
X-Sentinel-Event-ID: {event_id}
X-Sentinel-Event-Type: incident.declared
X-Sentinel-Timestamp: 1785591000
X-Sentinel-Signature: v1={signature}
```

Header names and signature format must remain stable within the Webhook Version.

---

## Signature Verification

Webhook requests must be signed using an approved HMAC algorithm.

Conceptual signature input:

```text
timestamp + "." + raw_request_body
```

Consumers should:

1. Read the raw request body.
2. verify the timestamp is recent.
3. compute the expected signature.
4. compare signatures using constant-time comparison.
5. reject invalid or replayed requests.

---

## Delivery Semantics

Webhook delivery is:

```text
At least once
```

Consumers must deduplicate using:

```text
event_id
```

Sentinel does not guarantee exactly-once delivery.

Delivery order should not be assumed across unrelated Resources.

---

## Successful Response

Any `2xx` response means the delivery was accepted.

The consumer should respond quickly and process asynchronously.

Redirect responses should not be followed automatically unless explicitly supported.

---

## Retries

Sentinel retries temporary failures such as:

* Network errors.
* timeouts.
* `408`
* `429`
* `5xx`

Permanent failures such as most `4xx` responses should not be retried indefinitely.

Retries use bounded exponential backoff.

---

## Delivery Record

A delivery record should expose:

```json
{
  "id": "0198f3e1-b265-7505-95e9-58a75a42c9df",
  "type": "webhook_delivery",
  "attributes": {
    "event_id": "0198f3e0-2e40-75bc-8c5e-f77f326ae01c",
    "event_type": "incident.declared",
    "status": "delivered",
    "attempt_count": 1,
    "created_at": "2026-08-01T16:30:01Z",
    "delivered_at": "2026-08-01T16:30:02Z"
  }
}
```

Supported statuses:

* `pending`
* `delivering`
* `delivered`
* `failed`
* `exhausted`
* `cancelled`

---

## Manual Retry

```http
POST /api/v1/organizations/{organization_id}/webhook-deliveries/{delivery_id}/retry
Idempotency-Key: {key}
```

Retry is allowed only when:

* The endpoint remains Active.
* the delivery belongs to the Organization.
* the event remains within replay retention.
* retry limits and Policy allow it.

---

## Endpoint Suspension

Sentinel may suspend an endpoint after repeated failures.

Suspension must:

* Stop new deliveries.
* preserve queued and historical records.
* create an Audit Record.
* expose the failure reason safely.

Reactivation may require endpoint verification.

---

## Payload Security

Webhook payloads must not include:

* Credentials.
* tokens.
* secret references.
* unrestricted Event payloads.
* private Agent prompts.
* raw provider responses.
* unauthorized evidence.

Consumers should fetch complete Resource state through the authenticated API when required.

---

## URL Security

Endpoint validation must protect against server-side request forgery.

Sentinel should reject or control:

* Loopback addresses.
* private network addresses.
* cloud metadata endpoints.
* unsupported protocols.
* embedded credentials.
* unsafe redirects.

Only HTTPS endpoints should be accepted in production.

---

## Authorization

Typical permissions:

```text
webhook.read
webhook.create
webhook.update
webhook.rotate_secret
webhook.suspend
webhook.retry
webhook.delivery.read
```

Secret rotation and replay operations may require stronger permissions.

---

## Errors

Common errors:

```text
webhook.endpoint_not_found
webhook.url_invalid
webhook.event_type_invalid
webhook.endpoint_suspended
webhook.delivery_not_found
webhook.retry_not_allowed
webhook.secret_rotation_failed
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Webhook Endpoint belongs to one Organization.
* Webhook contracts are separate from internal domain events.
* Payloads are signed.
* Delivery is at least once.
* Consumers deduplicate using the event identifier.
* Secrets are displayed only at creation or rotation.
* Endpoints use HTTPS.
* Unsafe destinations are prohibited.
* Repeated failures may suspend an endpoint.
* Sensitive data must not appear in payloads.
* Cross-Organization delivery and management are prohibited.

---

## Related Documents

* `docs/api/00-api-overview.md`
* `docs/api/02-authentication.md`
* `docs/api/05-idempotency-concurrency.md`
* `docs/api/15-plugins-api.md`
* `docs/api/16-notifications-api.md`
* `docs/database/10-plugin-schema.md`
* `docs/database/11-notification-schema.md`
* `docs/architecture/04-data-flow.md`
