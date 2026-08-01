# Idempotency and Concurrency

## Purpose

This document defines how Project Sentinel prevents duplicate effects and conflicting updates.

---

## Idempotency

Idempotency ensures that retrying the same request does not repeat its business effect.

It should be supported for operations such as:

* Event ingestion.
* Resource creation.
* Workflow start.
* Agent execution.
* Plugin synchronization.
* Notification requests.
* protected Actions.

---

## Idempotency Key

Clients provide:

```http
Idempotency-Key: {client_generated_key}
```

Keys are scoped by:

```text
organization_id
identity_or_client_id
operation
idempotency_key
```

The same key may be reused in another Organization without conflict.

---

## Request Matching

Sentinel stores a normalized request hash with the key.

When the same key is received:

* Same request hash: return the original result.
* Different request hash: return `409 Conflict`.
* Processing still active: return the current operation state.
* Record expired: process according to the endpoint contract.

Example conflict:

```json
{
  "error": {
    "code": "idempotency.key_conflict",
    "message": "The idempotency key was already used with a different request."
  }
}
```

---

## Stored Idempotency Data

An idempotency record should contain:

```text
organization_id
identity_id
operation
idempotency_key
request_hash
status
response_status
response_reference
created_at
completed_at
expires_at
```

Large response bodies should not be stored when a Resource or operation reference is sufficient.

---

## Processing Flow

```text
Receive request

↓

Resolve Organization and identity

↓

Create or lock idempotency record

↓

Compare request hash

↓

Execute command once

↓

Store result reference

↓

Return result
```

The idempotency record and authoritative state change should be committed atomically where practical.

---

## Idempotency Statuses

Suggested statuses:

* `processing`
* `completed`
* `failed`
* `expired`

A failed request may be retried only according to the endpoint’s documented behavior.

---

## Retention

Idempotency records should remain long enough to cover expected client retries.

Retention may differ by operation type.

Records must not contain:

* Credentials.
* unrestricted request payloads.
* sensitive response bodies.

---

# Optimistic Concurrency

Optimistic concurrency prevents one request from silently overwriting a newer Resource state.

Mutable protected Resources expose a numeric Version.

Example response:

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "status": "investigating",
  "version": 8
}
```

---

## Expected Version

Clients provide the Version they read:

```http
If-Match: "8"
```

The application updates the Resource only when the stored Version still equals `8`.

Conceptual update:

```sql
UPDATE incidents
SET
    status = :status,
    version = version + 1
WHERE organization_id = :organization_id
  AND id = :incident_id
  AND version = :expected_version;
```

If no row is updated, Sentinel returns:

```http
412 Precondition Failed
```

---

## Version Conflict

Example:

```json
{
  "error": {
    "code": "concurrency.version_conflict",
    "message": "The resource was modified by another operation.",
    "details": {
      "expected_version": 8,
      "current_version": 9
    }
  }
}
```

The client should reload the Resource before deciding whether to retry.

---

## Resources Requiring Version Checks

Expected-Version checks should be required for:

* Incident lifecycle changes.
* Alert ownership and status changes.
* configuration activation.
* Workflow state changes.
* Plugin Installation changes.
* Agent configuration changes.
* retention and security Policy changes.

Append-only records do not require optimistic concurrency.

---

## Command Concurrency

Some commands may conflict even when they update different fields.

Examples:

* Resolving an already closed Incident.
* approving an expired approval.
* executing an Action already completed.
* activating a superseded configuration Version.

Domain lifecycle rules must still be evaluated after the Version check.

---

## Retry Rules

Automatic retries are safe only when:

* The operation is idempotent.
* the latest Resource state has been loaded.
* domain rules are reevaluated.
* retry limits are bounded.

Clients must not blindly retry a concurrency conflict with the old Version.

---

## Database Constraints

Application checks do not replace database constraints.

The database should enforce:

* Unique business identifiers.
* tenant-aware relationships.
* active-record uniqueness.
* idempotency-key uniqueness.
* valid foreign-key ownership where possible.

Constraint violations must be translated into stable API errors.

---

## Pessimistic Locking

Row locks may be used internally for short critical sections such as:

* Work queue selection.
* sequencing.
* aggregate counters.
* idempotency record creation.

Locks must remain:

* Short-lived.
* Organization-aware.
* consistently ordered.
* protected from long external calls.

External APIs must not expose database locking concepts.

---

## Security Rules

* Idempotency keys must be tenant-scoped.
* One identity must not retrieve another identity’s cached response without authorization.
* Request hashes must exclude unstable transport values.
* Stored idempotency data must not expose secrets.
* Version conflicts must not reveal another Organization’s Resource.
* Organization ownership must be validated before concurrency checks.

---

## Business Rules

* Retryable commands use idempotency protection.
* The same key and request return the original result.
* The same key with different content returns a conflict.
* Protected mutable Resources use expected-Version checks.
* Version conflicts never use silent last-write-wins behavior.
* Domain rules are reevaluated after concurrency checks.
* Automatic retries must be bounded and idempotent.
* Database constraints remain the final protection for uniqueness.
* Cross-Organization idempotency and concurrency access is prohibited.

---

## Related Documents

* `docs/api/00-api-overview.md`
* `docs/api/01-api-conventions.md`
* `docs/api/03-errors.md`
* `docs/database/00-data-architecture.md`
* `docs/database/16-indexing-strategy.md`
