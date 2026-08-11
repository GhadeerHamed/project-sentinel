# Current Task

Review and reconcile the Shared Outbox Dispatcher contract before implementation.

## Required

This is an architecture and documentation review only.

Do not modify:

- application code;
- migrations;
- tests;
- `STATUS.md`;
- `NEXT.md`.

Review documentation directly relevant to:

- Outbox architecture;
- event-driven architecture;
- Domain Events;
- queue/workers;
- retries;
- transactional boundaries;
- observability;
- failure handling;
- Event → Signal / Alerting handoff;
- Milestone 6 prerequisites.

## Goal

Define the minimum production-safe shared mechanism that turns durable Outbox rows into asynchronous application event delivery.

The dispatcher must remain domain-neutral.

Do not design it specifically for `event.accepted`.

## Existing Boundary

Preserve:

- Domain mutation and Outbox persistence occur in one database transaction.
- Producers do not directly invoke downstream consumers.
- Milestone 5 already persists `event.accepted`.
- Outbox dispatch happens asynchronously after transaction commit.

Do not weaken the transactional Outbox guarantee.

## Outbox State Model

Determine the canonical persisted dispatch lifecycle.

Review whether current documentation requires or implies fields such as:

- pending;
- claimed/processing;
- dispatched/published;
- failed/dead;
- attempts;
- available_at;
- claimed_at;
- claimed_by;
- dispatched_at;
- failed_at;
- last_error.

Choose the smallest state model needed for reliable MVP dispatch.

Do not invent an overly complex workflow.

## Claiming

Define how workers claim rows safely.

Resolve:

- batch size;
- ordering;
- PostgreSQL locking strategy;
- whether to use `FOR UPDATE SKIP LOCKED`;
- lease/claim ownership;
- abandoned-claim recovery;
- worker crash behavior.

Multiple dispatcher workers must be able to run concurrently without processing the same row simultaneously.

Avoid global application locks.

## Ordering

Determine required ordering guarantees.

Clarify whether the platform requires:

- global ordering;
- per Organization ordering;
- per aggregate ordering;
- or only stable best-effort processing order.

Do not promise stronger ordering than the architecture actually requires.

## Delivery Guarantee

Define the canonical delivery semantic.

Expected model should be evaluated explicitly:

`at-least-once delivery`

Determine:

- whether exactly-once delivery is intentionally not guaranteed;
- how duplicate delivery is handled;
- what responsibilities belong to the dispatcher;
- what responsibilities belong to consumers.

Do not rely on in-memory deduplication.

## Consumer Idempotency

Define the contract expected from event consumers.

Determine whether consumers must persist a stable processed-message key such as:

- Outbox message ID;
- Domain Event ID;
- another documented identifier.

Clarify the transaction boundary between:

- duplicate detection;
- consumer side effects;
- processed-message recording.

This must support later `event.accepted` consumers safely.

## Dispatch Transport

Determine the first MVP transport.

Review whether canonical architecture expects:

- Laravel queue jobs;
- Redis queue;
- direct internal application dispatch;
- another broker abstraction.

Preserve runtime independence at the core boundary.

Do not introduce Kafka/RabbitMQ solely because they may be useful later.

## Dispatcher → Consumer Boundary

Define what the dispatcher publishes.

Confirm the canonical envelope includes where documented:

- Outbox message ID;
- event name/type;
- event version;
- Organization ID;
- aggregate type;
- aggregate ID;
- aggregate version;
- occurred_at;
- correlation ID;
- causation ID;
- payload.

Consumers must not depend on raw Outbox database rows.

## Success

Define when an Outbox record is considered successfully dispatched.

Distinguish between:

- successfully enqueued for asynchronous consumption;
- successfully consumed downstream.

The Outbox dispatcher should not falsely imply downstream business processing completed.

## Retry Policy

Define MVP retry behavior.

Resolve:

- attempt count;
- retry delay/backoff;
- retryable failures;
- non-retryable failures if applicable;
- maximum attempts;
- dead-letter/dead state behavior.

Keep the policy simple and explicit.

## Worker Crash Recovery

Resolve cases where:

1. a row is claimed;
2. the worker crashes before dispatch;
3. the worker crashes after enqueue but before marking success.

The design must tolerate duplicate dispatch.

Do not attempt distributed exactly-once semantics.

## Failure Storage

Determine what failure information may be persisted.

Allowed failure metadata should be safe and bounded.

Do not persist:

- secrets;
- credentials;
- Authorization headers;
- arbitrary unbounded exception dumps.

Define canonical error truncation/sanitization if needed.

## Scheduling

Determine:

- dispatcher execution model;
- queue worker vs scheduled polling;
- polling interval if documented;
- empty-poll behavior;
- wake-up strategy.

Prefer the smallest mechanism compatible with the existing Laravel + Redis deployment.

## Observability

Define the minimum operational signals required.

At minimum evaluate:

- pending count;
- oldest pending age;
- dispatch throughput;
- retry count;
- failed/dead count.

Do not build dashboards in this task.

## Cleanup / Retention

Determine whether successfully dispatched Outbox rows:

- remain indefinitely;
- are archived;
- are deleted after a retention window.

If retention belongs to a later capability, say so explicitly.

Do not implement cleanup yet unless required for the dispatcher foundation.

## Security / Tenant Isolation

Confirm:

- dispatcher is platform-internal;
- no human tenant permission is involved;
- Organization context remains present in the event envelope;
- one tenant's failure must not expose another tenant's data;
- logged failure data remains sanitized.

## Milestone 6 Boundary

Determine exactly what must exist before the first Milestone 6 consumer can be implemented.

Expected sequence:

Outbox persistence
→ dispatcher
→ transport
→ idempotent consumer
→ `event.accepted` processing

Do not implement Signal or Alert behavior in this task.

## First Implementation Slice

Recommend the smallest first implementation task.

Prefer separating:

1. Outbox claiming/state foundation;
2. dispatcher/transport;
3. consumer idempotency foundation;

if one task would be too large.

## Decision Precedence

When documentation conflicts, use:

1. ADR / explicit architecture decisions.
2. shared platform/module contracts.
3. architecture documentation.
4. deployment/queue documentation.
5. database documentation.
6. functional/roadmap examples.

State conflicts explicitly.

## Reporting

Return only:

- Outbox state model
- claiming strategy
- ordering guarantee
- delivery guarantee
- consumer idempotency contract
- transport decision
- success semantics
- retry/dead-letter policy
- crash-recovery behavior
- observability requirements
- retention decision
- security/tenant behavior
- documentation corrections required
- recommended implementation slices
- blockers

Maximum 25 lines.