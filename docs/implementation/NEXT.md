# Current Task

Finalize built-in owner-role provisioning before PostgreSQL persistence.

## Required

* Determine whether built-in roles are global templates or Organization-owned records.
* Define the smallest framework-independent provisioning contract for `organization_owner`.
* Do not hide provisioning behind a read-only `resolveRoleId()` method.
* Update Create Organization to provision and assign the owner role atomically.
* Verify whether initial role assignment must emit a `role.assigned` Domain Event and Outbox message.
* Add focused application tests.

## Excluded

Do not add migrations, Eloquent, controllers, authentication, middleware, or HTTP code.

## Verification

Run targeted tests and:

```bash
docker compose exec app composer quality
```

Update `STATUS.md`; do not modify `NEXT.md`.

## Reporting

Return only:

* role ownership model selected
* contracts changed
* event behavior
* tests result
* remaining blocker

Maximum 15 lines.
