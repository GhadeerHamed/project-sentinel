# Current Task

Implement Batch Event Ingestion — Part 1: application/domain core and shared normalization.

## Goal

Build the framework-independent batch ingestion core without HTTP or PostgreSQL batch orchestration yet.

The existing single-event ingestion behavior must remain unchanged.

## Required

Reuse the canonical single-event ingestion rules for each batch item:

- Event Source-derived hierarchy;
- schema version `1`;
- canonical event type → category mapping;
- severity validation;
- `occurred_at`;
- JSON-object payload;
- normalized flat scalar attributes;
- 64 KiB per-Event limit;
- optional `external_event_id`;
- optional correlation/causation/trace identifiers;
- immutable Event construction.

## Batch Contract

Support:

- minimum 1 item;
- maximum 100 items.

Reject:

- zero items;
- more than 100 items.

Preserve input order.

## Shared Normalization

Extract/reuse only the smallest shared mechanism needed so single and batch ingestion cannot drift.

Good candidates:

- Event input normalization;
- schema validation;
- event type/category resolution;
- payload/attributes size validation;
- canonical Event construction input.

Do not:

- create an abstract base handler;
- redesign single-event ingestion;
- duplicate all validation logic inside the batch handler.

## Application Layer

Add the minimum framework-independent batch types required for the future persistence slice, such as:

- batch command;
- batch item input;
- normalized item/result types where useful.

Do not implement HTTP-specific request classes.

Do not implement the final PostgreSQL batch transaction yet.

## Duplicate Prevalidation

Within a single batch request:

- detect repeated `external_event_id` values;
- equivalent normalized Events may resolve to one logical Event later;
- conflicting reuse of the same external ID must be rejected.

Build the comparison logic needed for this deterministically.

Do not query PostgreSQL for existing external IDs in this task.

## Idempotency

Define/use the normalized complete-batch representation required for the later:

`event.ingest.batch`

idempotency hash.

Do not implement new persistence or transactional idempotency orchestration yet.

## Excluded

Do not add or modify:

- batch HTTP route/controller;
- database schema;
- PostgreSQL batch persistence;
- Outbox batch writes;
- transactional batch orchestration;
- concurrency handling;
- Event read APIs;
- Signals or Alerts.

## Tests

Cover at minimum:

- one-item batch accepted by core;
- multi-item batch;
- exactly 100 items;
- zero items rejected;
- 101 items rejected;
- canonical normalization matches single-event rules;
- invalid schema rejected;
- invalid Event type rejected;
- invalid payload rejected;
- oversized Event rejected;
- input order preserved;
- equivalent duplicate external IDs identified consistently;
- conflicting duplicate external IDs rejected;
- deterministic normalized batch representation/hash input;
- existing single-event tests remain passing.

## Verification

Run focused tests.

Run:

`docker compose exec app composer quality`

Update `STATUS.md` only to indicate Batch Event Ingestion remains in progress.

Do not modify `NEXT.md`.

## Reporting

Return only:

- batch application types added
- shared normalization extracted/reused
- batch bounds behavior
- in-request duplicate behavior
- normalized batch/idempotency representation
- single-event regression status
- files changed
- tests
- blockers

Maximum 12 lines.