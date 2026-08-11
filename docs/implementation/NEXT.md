# Current Task

Implement Batch Event Ingestion — Part 2B2A: deterministic PostgreSQL concurrency test harness.

## Goal

Create the smallest reusable testing foundation needed to run two or more truly independent Laravel application processes concurrently against the PostgreSQL test database.

Do not add the actual batch-ingestion race scenarios yet.

## Problem

The previous concurrency harness is quarantined because forked PDO connections do not reliably model independent application requests or lock synchronization.

The new harness must not share an inherited PDO connection between workers.

Each worker must:

- run in its own PHP process;
- bootstrap Laravel independently;
- create its own database connections;
- execute application code against the same prepared test fixture.

## Scope

Implement testing infrastructure only.

Do not modify production Event ingestion behavior unless required solely to expose an existing application entry point to the test worker.

Prefer zero production-code changes.

## Worker Process

Provide a test-only mechanism capable of launching an independent PHP/Laravel worker.

The worker must be able to:

1. boot the application;
2. receive a small serialized test instruction;
3. resolve required services from the container;
4. wait at a deterministic synchronization barrier;
5. execute the requested application operation;
6. return a structured success/error result to the parent test process.

Do not invoke HTTP unless required by the later race test.

Application-handler execution is sufficient.

## Process Isolation

Workers must not use:

- `pcntl_fork()` with inherited Laravel/PDO state;
- inherited PDO connections;
- shared in-process transactions.

Use separate OS-level PHP processes.

The implementation may use the project's existing process facilities or Symfony Process if already available through Laravel dependencies.

Do not add a heavyweight dependency solely for this harness.

## Deterministic Barrier

Provide a deterministic coordination mechanism so multiple workers can reach a known execution point before proceeding.

Suitable approaches include test-only:

- PostgreSQL advisory locks;
- a small database-backed barrier;
- another deterministic PostgreSQL coordination primitive.

Do not use arbitrary:

- `sleep()`;
- timing assumptions;
- polling loops without a deterministic synchronization condition.

The barrier must work across independent database connections/processes.

## Fixture Model

The parent test process must be able to:

1. create a realistic Organization → Project → Application → Environment → Event Source fixture;
2. commit that fixture;
3. launch workers against the committed fixture.

Workers must not depend on uncommitted parent-test transaction state.

Account for the existing Laravel database-test strategy.

If transactional test traits prevent child-process visibility, use the smallest dedicated integration-test setup that commits fixture data safely and cleans it afterward.

## Structured Worker Result

The parent must receive a deterministic structured result containing at minimum:

- success/failure;
- result identifiers where applicable;
- canonical application error code where applicable;
- safe diagnostic information.

Do not serialize:

- bearer secrets;
- credential hashes;
- Authorization headers;
- full exception traces containing secrets.

## Proof Test

Add one small proof-of-harness integration test.

The proof must demonstrate that:

- two independent PHP processes boot successfully;
- both connect independently to PostgreSQL;
- both observe the same committed fixture/state;
- both reach the deterministic barrier;
- both proceed after release;
- both return structured results;
- the test does not rely on sleeps.

The proof does not need to exercise Event ingestion races yet.

## Reusability

Keep the harness generic enough for later concurrency verification involving:

- idempotency;
- optimistic concurrency;
- unique-resource races.

Do not make the generic harness depend on Event-specific domain classes.

Event-specific worker commands/adapters may be added later in Part 2B2B.

## Excluded

Do not add:

- batch race scenarios;
- HTTP batch tests;
- production locks;
- Redis locks;
- schema changes to production tables;
- Event/Outbox behavior changes;
- Outbox dispatcher;
- Signals;
- Alerts.

## Verification

Run the focused concurrency-harness test.

Then run:

`docker compose exec app composer quality`

Update `STATUS.md` only to record harness completion and move Part 2B2B to In Progress.

Do not modify `NEXT.md`.

## Reporting

Return only:

- process-isolation approach
- synchronization approach
- fixture strategy
- worker protocol
- proof test
- production changes
- files changed
- tests
- blockers

Maximum 12 lines.