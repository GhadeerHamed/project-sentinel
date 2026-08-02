# Implementation Status

## Current Milestone

```text
Milestone 2 — Shared Platform Foundation
```

## Completed Foundation

* [x] Laravel application initialization.
* [x] Docker development environment.
* [x] Nginx and PHP-FPM.
* [x] PostgreSQL integration.
* [x] Redis integration.
* [x] Queue Worker.
* [x] scheduler.
* [x] GitHub Actions CI.
* [x] Laravel Pint.
* [x] Larastan and PHPStan.
* [x] Composer quality scripts.
* [x] UUIDv7 Resource ID foundation.
* [x] UUIDv7 generator contract.
* [x] Laravel UUIDv7 generator adapter.
* [x] UUIDv7 service-container binding and tests.
* [x] Clock contract.
* [x] UTC system-clock adapter.
* [x] Clock service-container binding and tests.
* [x] Correlation identifier.

## In Progress

* [ ] Causation identifier.

## Remaining Shared Platform Foundation

* [ ] Identity Reference.
* [ ] Tenant Context.
* [ ] Domain Event contract.
* [ ] Resource Version.
* [ ] Optimistic concurrency primitives.
* [ ] Transaction Manager contract.
* [ ] Outbox message foundation.
* [ ] Common domain and application errors.
* [ ] Audit-recording contract.

## Next Recommended Task

Implement the framework-independent Causation ID Value Object with:

* Strict validation.
* UUIDv7 representation.
* equality behavior.
* string and JSON serialization.
* focused unit tests.

Reuse existing Shared Kernel identifier primitives where appropriate. Do not duplicate UUID validation logic.

## Status Update Rules

After completing a capability:

1. Run targeted tests.
2. run `docker compose exec app composer quality`.
3. mark the capability completed only when all checks pass.
4. move the next capability to `In Progress`.
5. update `Next Recommended Task`.
6. keep this file concise.
7. omit temporary debugging details.
8. do not modify `AGENTS.md` unless a permanent rule changed.

Never claim a capability is completed without confirming the implementation and tests.
