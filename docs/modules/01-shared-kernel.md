# Shared Kernel

## Purpose

The Shared Kernel contains the smallest set of stable concepts required by multiple Sentinel modules.

It exists to preserve consistency without creating hidden coupling between modules.

---

## Core Rule

A concept belongs in the Shared Kernel only when:

* Multiple modules require exactly the same meaning.
* Its semantics are stable.
* Duplicating it would create inconsistency.
* It does not belong to one business capability.
* Changes can be coordinated across all consumers.

When ownership is unclear, the concept should remain inside its original module.

---

## Responsibilities

The Shared Kernel may provide:

* Strongly typed identifiers.
* Tenant Context primitives.
* identity references.
* domain event contracts.
* correlation and causation identifiers.
* clock abstractions.
* optimistic concurrency Versions.
* pagination primitives.
* common domain error contracts.
* idempotency identifiers.
* basic money or time Value Objects when universally required.

It must not contain feature-specific business rules.

---

## Identifier Types

Every Resource identifier should use a distinct type.

Examples:

```text
OrganizationId
ProjectId
ApplicationId
EnvironmentId
EventId
AlertId
IncidentId
KnowledgeId
AgentId
PluginId
WorkflowId
```

Identifiers use UUIDv7 values stored with PostgreSQL native `uuid`.

Distinct identifier types prevent accidental interchange between unrelated Resources.

---

## Identifier Contract

A Resource identifier should support:

* Creation from a valid UUIDv7.
* secure generation.
* string serialization.
* equality comparison.
* validation.
* database conversion.

Example interface:

```php
interface ResourceId
{
    public static function generate(): static;

    public static function fromString(string $value): static;

    public function toString(): string;

    public function equals(self $other): bool;
}
```

The Shared Kernel defines the contract and base behavior, not module-specific identifier meaning.

---

## Human-Friendly References

Human-readable references do not replace Resource identifiers.

Examples:

```text
INC-2026-00421
ALT-2026-01842
```

These references:

* May be displayed to Users.
* may be searchable.
* may be sequential within an Organization.
* are not used as primary keys.
* do not grant authorization.

Generation rules remain owned by the relevant module.

---

## Organization Context

Tenant-owned operations require an Organization identifier.

The Shared Kernel may define:

```php
final readonly class TenantContext
{
    public function __construct(
        public OrganizationId $organizationId,
        public IdentityReference $actingIdentity,
        public CorrelationId $correlationId,
        public ?CausationId $causationId,
    ) {
    }
}
```

Tenant Context is immutable during one operation.

It does not perform authorization by itself.

---

## Identity Reference

Modules may use a common identity reference without depending directly on the Identity and Access module’s internal models.

Suggested fields:

```text
identity_id
identity_type
```

Supported types may include:

```text
user
api_client
service
agent
plugin
platform
```

Identity validation and lifecycle remain owned by the Identity and Access module.

---

## Correlation and Causation

Every significant operation should support:

```text
correlation_id
causation_id
```

### Correlation ID

Groups operations that belong to the same operational flow.

### Causation ID

Identifies the command, event, or job that caused another operation.

These identifiers propagate through:

* HTTP requests.
* commands.
* domain events.
* Outbox messages.
* Queue jobs.
* Workflow Steps.
* Agent and Plugin executions.

---

## Domain Event Contract

The Shared Kernel may define a common event envelope.

Example:

```php
interface DomainEvent
{
    public function eventId(): EventId;

    public function organizationId(): OrganizationId;

    public function occurredAt(): DateTimeImmutable;

    public function correlationId(): CorrelationId;

    public function causationId(): ?CausationId;

    public function eventName(): string;

    public function eventVersion(): int;
}
```

Each module owns the payload and semantics of its events.

---

## Event Naming

Event names should represent completed facts.

Examples:

```text
alert.created
incident.resolved
plugin.installed
workflow.completed
```

The Shared Kernel may enforce naming and Versioning conventions.

It must not define another module’s event payload.

---

## Clock Abstraction

Domain and application code should not call the system clock directly.

Shared contract:

```php
interface Clock
{
    public function now(): DateTimeImmutable;
}
```

Runtime implementations may provide:

* System clock.
* frozen test clock.
* controlled simulation clock.

This keeps time-dependent rules deterministic and testable.

---

## Concurrency Version

Resources using optimistic concurrency should use a shared Version type.

Example:

```php
final readonly class ResourceVersion
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new InvalidArgumentException();
        }
    }

    public function next(): self
    {
        return new self($this->value + 1);
    }
}
```

The owning module decides which Resources require concurrency protection.

---

## Idempotency

The Shared Kernel may define:

```text
IdempotencyKey
OperationId
```

These values identify repeatable operations safely.

They do not determine how a module stores or enforces idempotency.

---

## Pagination

Common pagination contracts may include:

```text
PageSize
Cursor
PageResult
SortDirection
```

Pagination primitives must not contain module-specific filters.

Cursor contents should remain opaque to API consumers.

---

## Domain Errors

The Shared Kernel may define stable error categories:

```text
validation_error
conflict
not_found
forbidden
concurrency_conflict
invalid_state
dependency_unavailable
```

Modules should provide specific errors within these categories.

Infrastructure exceptions must not escape directly into domain contracts.

---

## Result Contracts

Where explicit result objects are used, they should distinguish:

```text
Success
Failure
```

They must not hide programming errors or unexpected infrastructure failures.

Exceptions may still be used for invariant violations and exceptional conditions according to the implementation conventions.

---

## Time Value Objects

Shared time concepts may include:

* `OccurredAt`
* `CreatedAt`
* `UpdatedAt`
* `Duration`
* `TimeRange`

Only universally consistent concepts belong here.

Module-specific deadlines, acknowledgement windows, or retention periods remain inside their owning modules.

---

## Serialization

Shared Value Objects may define safe scalar serialization for:

* APIs.
* persistence adapters.
* events.
* Queue payloads.

Domain objects must not depend directly on framework serializers or ORM casting interfaces.

---

## Validation

Shared primitives validate their own structural invariants.

Examples:

* UUID format.
* positive Version.
* valid time range.
* non-empty idempotency key.
* valid correlation identifier.

They must not perform:

* Database lookups.
* authorization.
* external provider validation.
* module-specific business checks.

---

## Dependency Rules

All modules may depend on the Shared Kernel.

The Shared Kernel must not depend on:

* Business modules.
* Laravel.
* database implementations.
* HTTP.
* Redis.
* Queue drivers.
* external SDKs.

Its dependencies should remain limited to the language runtime and carefully selected framework-independent libraries.

---

## Change Management

Changes to the Shared Kernel have broad impact.

Every change should verify:

* Which modules consume the concept.
* Whether semantics remain backward-compatible.
* event and Queue compatibility.
* database serialization impact.
* migration requirements.
* test coverage across affected modules.

Feature-specific convenience is not sufficient reason to expand the Shared Kernel.

---

## Testing Requirements

Shared Kernel tests should cover:

* Identifier validation and equality.
* UUIDv7 generation.
* serialization.
* clock behavior.
* Version transitions.
* correlation propagation.
* invalid primitive construction.
* pagination boundaries.
* event envelope consistency.

Tests should remain fast and infrastructure-independent.

---

## Prohibited Contents

The Shared Kernel must not contain:

* Alert or Incident lifecycle rules.
* Organization Membership rules.
* Plugin or Agent capability logic.
* notification delivery logic.
* Workflow orchestration.
* repository implementations.
* Eloquent models.
* Laravel facades.
* provider clients.
* generic business services.
* miscellaneous helper functions.

---

## Prohibited Practices

The following are prohibited:

* Using the Shared Kernel as a general utilities directory.
* moving code into it only to avoid dependency decisions.
* placing mutable feature configuration in it.
* adding infrastructure-specific interfaces without cross-module need.
* sharing domain Entities between modules.
* centralizing all errors into one large hierarchy.
* allowing Shared Kernel changes without evaluating all consumers.
* introducing unstable business concepts prematurely.

---

## Business Rules

* The Shared Kernel remains intentionally small.
* only stable cross-module concepts belong in it.
* modules share identifiers and contracts, not authoritative Entities.
* Shared Kernel code remains framework-independent.
* structural validation belongs inside shared Value Objects.
* authorization and business validation remain inside owning modules.
* all modules may depend on the Shared Kernel.
* the Shared Kernel depends on no business module.
* broad impact must be evaluated before every Shared Kernel change.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/14-identity-access-module.md`
* `docs/modules/18-laravel-runtime-adapter.md`
* `docs/architecture/02-domain-architecture.md`
* `docs/architecture/05-event-model.md`
* `docs/adr/ADR-005-domain-driven-design.md`
* `docs/adr/ADR-009-core-is-framework-agnostic.md`
* `docs/database/00-data-architecture.md`
* `docs/api/01-api-conventions.md`
