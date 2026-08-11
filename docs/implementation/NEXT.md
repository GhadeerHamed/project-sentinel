# Current Task

Complete Batch Event Ingestion — Part 2B2B2: external-ID race and rollback verification.

## Goal

Use the existing deterministic multi-process PostgreSQL harness to verify the remaining external-ID concurrency behavior.

Do not modify HTTP behavior.

Prefer no production changes unless a deterministic race exposes a real correctness defect.

## Existing Infrastructure

Reuse:

- committed active Event Source hierarchy fixture;
- `ConcurrentLaravelWorker`;
- `ingest_event_batch` worker instruction;
- independent Laravel worker processes;
- ready/release synchronization barrier;
- production `IngestEventBatchHandler`;
- `saveIfExternalEventIdAbsent`;
- transactional batch idempotency;
- existing Event and Outbox persistence.

Do not redesign the harness.

## Race 1 — Equivalent External ID

Launch two concurrent workers with:

- different batch idempotency keys;
- same Organization;
- same Event Source;
- same `external_event_id`;
- equivalent canonical Event content.

Release both workers concurrently.

Verify:

- both requests succeed;
- both resolve to the same Sentinel Event ID;
- exactly one physical Event exists for the contested external ID;
- exactly one `event.accepted` Outbox record exists for that Event;
- each batch idempotency operation completes with the same logical Event ID;
- no raw PostgreSQL uniqueness error escapes.

This must exercise the actual production race boundary rather than a sequential pre-check.

## Race 2 — Conflicting External ID

Launch two concurrent workers with:

- different batch idempotency keys;
- same Organization;
- same Event Source;
- same `external_event_id`;
- different normalized canonical Event content.

Do not control which worker wins.

Verify:

- exactly one request succeeds;
- exactly one request fails with canonical:

`event.external_id_conflict`

- exactly one physical Event exists for the contested external ID;
- exactly one `event.accepted` Outbox record exists for the winning Event;
- no SQLSTATE, PDO, or raw PostgreSQL uniqueness error escapes.

## Race 3 — Losing Batch Atomicity

For the conflicting worker, use a multi-item batch.

Its ordered batch must include:

1. one unrelated new Event;
2. the contested conflicting external-ID Event.

Arrange the batch so the unrelated Event may be written before the external-ID race is resolved.

Verify that when this worker loses:

- its unrelated Event does not remain;
- its unrelated `event.accepted` Outbox record does not remain;
- its conflicting Event does not remain;
- its batch idempotency operation is not successfully completed;
- no completed replay payload remains for the failed batch.

The winning transaction must remain fully committed.

## Equivalent-Race Idempotency

For the equivalent race, verify both distinct batch idempotency records complete successfully.

Each stored:

`result_payload.event_ids`

must resolve to the same logical Sentinel Event.

Do not require both requests to share an idempotency record because their keys differ.

## Conflicting-Race Idempotency

For the losing conflicting request:

- do not mark the operation successfully completed;
- do not expose a completed replay result;
- preserve canonical retry/conflict behavior according to the existing idempotency contract.

## Outbox Guarantees

Across all scenarios verify:

- one physical newly accepted Event produces exactly one `event.accepted`;
- equivalent races do not duplicate Outbox records;
- losing conflicting transactions leave no surviving Outbox records;
- no `event.ingested` records are produced.

This verifies Outbox persistence only.

Do not implement the dispatcher.

## Error Safety

Worker output for the losing request must expose only the canonical application error.

It must not expose:

- SQLSTATE;
- constraint names;
- PDO exceptions;
- PostgreSQL messages;
- stack traces containing persistence internals.

## Production Changes

Expected:

none.

If the tests expose a real race defect:

1. retain the deterministic failing test;
2. make the smallest production correction;
3. rerun all three race scenarios;
4. report exactly what was corrected.

Do not introduce:

- Redis locks;
- application-global locks;
- coarse Organization locks;
- new concurrency architecture.

## Regression

Confirm these remain green:

- identical-idempotency concurrency test;
- concurrency harness proof test;
- normal batch handler tests;
- batch HTTP tests;
- single-event ingestion tests.

## Excluded

Do not add:

- HTTP changes;
- new batch semantics;
- partial success;
- new concurrency infrastructure unless strictly necessary;
- Outbox dispatcher;
- Signals;
- Alerts.

## Verification

Run focused deterministic concurrency tests.

Then run:

`docker compose exec app composer quality`

If all pass:

- mark Part 2B2B2 complete;
- clear all remaining batch-concurrency verification gaps;
- move Milestone 5 final completion review to In Progress.

Do not modify `NEXT.md`.

## Reporting

Return only:

- equivalent external-ID race result
- equivalent-race idempotency result
- conflicting external-ID race result
- losing-batch rollback result
- conflicting-race idempotency result
- Outbox guarantees
- production changes, if any
- files changed
- tests
- Part 2B2B2 completion status
- blockers

Maximum 15 lines.