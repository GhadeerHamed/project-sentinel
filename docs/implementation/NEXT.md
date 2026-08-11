# Current Task

Implement Batch Event Ingestion — Part 2B1: transactional batch handler.

## Goal

Implement the application/infrastructure batch execution path.

Do not add the HTTP endpoint yet.

Concurrency stress/hardening is explicitly deferred to Part 2B2.

## Existing Foundations

Reuse:

- `IngestEventBatchCommand`
- `IngestEventBatchItem`
- canonical `EventEnvelope` normalization
- existing single-Event persistence
- existing external-ID duplicate semantics
- existing hierarchy validation
- shared idempotency infrastructure
- shared idempotency `resultPayload`
- TransactionManager
- Outbox persistence
- UUIDv7 generator
- Clock

Do not create parallel implementations of these mechanisms.

## Batch Idempotency

Operation:

`event.ingest.batch`

Scope by:

- Organization
- authenticated Event Source
- operation
- Idempotency-Key
- deterministic normalized batch hash

On first execution:

- process the logical batch;
- persist the ordered logical Event IDs in idempotency `resultPayload`.

Use this shape:

```text
{
  event_ids: [
    "<uuid>",
    "<uuid>"
  ]
}
```
The array:

preserves input order;
may contain repeated Event IDs;
contains no Event-specific data other than IDs;
contains no secrets.
Replay

For same key + same request hash:

do not create Events;
do not create Outbox records;
obtain the stored ordered event_ids;
load the referenced Events tenant-safely;
reconstruct the same logical batch result.

If a referenced Event cannot be resolved consistently, fail explicitly rather than silently producing a different replay result.

Same key + different request hash remains a conflict.

Hierarchy

Validate the authenticated Event Source hierarchy using the existing single-ingestion rules:

Organization
→ Project
→ Application
→ Environment

All must be active for new ingestion.

Do not derive hierarchy from batch items.

External-ID Resolution

For each logical batch item with external_event_id:

No existing Event

Create a new Event.

Existing equivalent Event

Reuse the existing Event ID.

Do not create:

another Event;
another event.accepted.
Existing conflicting Event

Fail the entire batch with:

event.external_id_conflict

Reuse the canonical normalized-event comparison.

Do not implement a new duplicate algorithm.

In-Request Duplicates

Use the Part 1 normalized duplicate behavior.

Equivalent repeated external IDs must map to the same logical Event ID.

Conflicting repeated external IDs must reject the batch.

Preserve one result entry per original input item.

Example:

input:  A, B, A
result: E1, E2, E1
Persistence

For every newly accepted Event:

generate UUIDv7;
persist immutable canonical Event data;
persist source-derived hierarchy;
use server-owned timestamps;
preserve existing normalized duplicate hash behavior.

Do not add a batch table.

Outbox

For each newly persisted Event, create exactly one:

event.accepted

Outbox record.

Do not create an Outbox record when resolving an equivalent existing Event.

Do not emit:

event.ingested

Atomicity

One transaction must cover:

idempotency acquisition;
hierarchy validation where required;
duplicate resolution;
all new Event writes;
all new event.accepted Outbox writes;
idempotency completion with ordered event_ids.

If any step fails:

no new Event remains;
no new Outbox record remains;
idempotency must not become completed.
Application Result

Return a transport-independent batch result containing one canonical logical Event result per original input item, preserving input order.

Equivalent duplicates may therefore appear multiple times with the same Sentinel Event ID.

The result must contain enough data for the later HTTP layer to render the documented response.

Do not include:

idempotency internals;
normalized duplicate hashes;
Outbox data;
credential data.
Reuse

Extract a shared single/batch ingestion service only if genuinely necessary to preserve behavior.

Do not:

create base handlers;
redesign single ingestion;
duplicate the Event construction algorithm.

Single-event behavior must remain unchanged.

Tests

Cover at minimum:

one-item transactional batch;
multi-item batch;
100-item batch;
all-new Events persisted;
input result order preserved;
equivalent in-request duplicate maps to the same Event ID;
equivalent existing external ID reuses existing Event;
conflicting existing external ID rolls back entire batch;
exactly one event.accepted per newly created Event;
no Outbox for reused Event;
batch idempotency completion stores ordered event_ids;
idempotency replay reconstructs the same ordered result;
replay creates no new Events or Outbox records;
idempotency hash mismatch conflicts;
Event persistence failure rolls back the batch;
Outbox persistence failure rolls back the batch;
idempotency completion failure rolls back Events and Outbox;
existing single-event ingestion tests remain green.
Deferred to Part 2B2

Do not implement broad concurrency hardening in this task.

Part 2B2 will explicitly verify:

concurrent identical batch requests;
concurrent external-ID collisions;
database race handling;
deterministic winner/replay semantics.

Basic transaction correctness is still required.

Excluded

Do not add:

HTTP request/controller/route/resource;
partial success;
async batch jobs;
streaming;
schema changes;
Signals;
Alerts;
Outbox dispatcher.
Verification

Run focused batch tests and:

docker compose exec app composer quality

Update STATUS.md; do not modify NEXT.md.

Reporting

Return only:

handler implemented
idempotency result-payload behavior
replay behavior
duplicate resolution
persistence behavior
Outbox behavior
rollback behavior
result ordering
single-event regression status
files changed
tests
blockers

Maximum 15 lines.