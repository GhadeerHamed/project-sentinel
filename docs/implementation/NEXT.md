# Current Task

Implement Batch Event Ingestion — Part 2B2B1: committed fixture and identical-idempotency race verification.

## Goal

Use the existing multi-process concurrency harness and `ingest_event_batch` worker instruction to verify one race only:

two concurrent identical batch requests using the same idempotency key.

Do not implement the external-ID race scenarios yet.

## Existing Infrastructure

Reuse:

- `ConcurrentLaravelWorker`;
- independently booted Laravel workers;
- deterministic ready/release synchronization;
- suite-safe DB disconnect/purge behavior;
- `ingest_event_batch` worker instruction;
- production `IngestEventBatchHandler`.

Do not redesign any of these.

## Committed Fixture

Create the smallest reusable test fixture containing an active:

Organization
→ Project
→ Application
→ Environment
→ Event Source

The fixture must satisfy all production handler requirements.

Requirements:

- persist real PostgreSQL rows;
- commit them before workers launch;
- provide safe IDs required by worker instructions;
- do not require bearer credentials because the worker invokes the application handler directly;
- avoid human/session setup not required by batch ingestion.

Prefer a reusable test helper if it will also serve Part 2B2B2.

Do not add production fixture code.

## Race

Launch two workers with:

- same Organization;
- same Event Source;
- same ordered batch;
- same `Idempotency-Key`;
- same normalized batch content.

Both workers must reach the existing deterministic barrier before execution begins.

Release them together.

## Expected Result

Verify both workers succeed.

Both must return exactly the same ordered Event IDs.

For a batch of N unique new logical Events, verify:

- exactly N physical Event rows exist;
- not 2N;
- exactly N `event.accepted` Outbox rows exist;
- exactly one completed `event.ingest.batch` idempotency operation exists;
- stored `result_payload.event_ids` matches the returned ordered Event IDs.

No duplicate Event or Outbox rows may exist.

## Replay Payload

Verify the persisted idempotency result:

- contains the complete ordered `event_ids`;
- contains no partial state;
- preserves the exact batch ordering.

Do not expose internal idempotency fields through worker results.

## Worker Safety

Worker outputs must contain only safe structured values:

- success/failure;
- ordered Event IDs;
- canonical error category if failure occurs.

No SQL errors, credentials, hashes, or stack traces.

## Production Changes

Expected:

none.

If this race exposes a real production defect:

1. preserve the failing test;
2. make the smallest correction;
3. rerun the race;
4. report the correction.

Do not broaden scope.

## Tests

Add deterministic PostgreSQL integration coverage for:

- committed hierarchy visible to both workers;
- simultaneous identical batch execution;
- both workers returning the same IDs;
- one physical Event set;
- one Outbox record per logical new Event;
- one completed batch idempotency record;
- correct ordered `result_payload`.

Also confirm the existing concurrency harness proof remains green.

## Excluded

Do not implement:

- equivalent external-ID race;
- conflicting external-ID race;
- losing multi-item rollback race;
- HTTP changes;
- production concurrency infrastructure;
- Redis/global locks;
- Outbox dispatcher;
- Signals;
- Alerts.

## Verification

Run focused concurrency tests.

Then run:

`docker compose exec app composer quality`

If all pass:

- mark Part 2B2B1 complete;
- move Part 2B2B2 to In Progress.

Do not modify `NEXT.md`.

## Reporting

Return only:

- fixture strategy
- race synchronization
- worker results
- Event-row result
- Outbox result
- idempotency/result-payload result
- production changes, if any
- files changed
- tests
- blockers

Maximum 12 lines.