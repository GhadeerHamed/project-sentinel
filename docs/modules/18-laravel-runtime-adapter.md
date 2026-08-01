# Laravel Runtime Adapter

## Purpose

The Laravel Runtime Adapter connects Sentinel’s framework-independent modules to Laravel, HTTP, queues, scheduling, persistence, and external infrastructure.

Laravel is the first runtime implementation, not the owner of domain rules.

---

## Responsibilities

The adapter provides:

* HTTP routing and controllers.
* request validation.
* authentication integration.
* Tenant Context resolution.
* dependency injection bindings.
* database repository implementations.
* transaction management.
* queue job dispatch and execution.
* scheduler integration.
* event and Outbox publication.
* serialization.
* infrastructure provider adapters.
* framework-specific observability.

---

## Does Not Own

The adapter does not own:

* Domain Entities.
* lifecycle rules.
* authorization decisions.
* module ownership.
* business commands.
* business validation.
* domain events.
* canonical Resource contracts.

Framework code must delegate business behavior to module application contracts.

---

## Dependency Direction

Required direction:

```text
Laravel Runtime
→ Module Application Layer
→ Module Domain Layer
→ Shared Kernel
```

Prohibited direction:

```text
Domain
→ Laravel
```

Domain code must not depend on:

* Eloquent.
* Laravel facades.
* HTTP requests.
* queue classes.
* service providers.
* framework events.
* framework validation rules.

---

## Suggested Runtime Structure

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Console/
├── Providers/
└── Infrastructure/
    ├── Persistence/
    ├── Messaging/
    ├── Storage/
    ├── Providers/
    └── Observability/
```

Framework-independent modules should live outside normal Laravel feature folders.

Example:

```text
src/
├── Shared/
├── Organization/
├── Project/
├── Application/
├── Environment/
├── Event/
├── Alert/
├── Incident/
└── ...
```

---

## Controllers

Controllers should:

1. Accept validated input.
2. resolve authenticated identity.
3. resolve Tenant Context.
4. construct a command or query.
5. invoke the application handler.
6. map the result to an API response.

Controllers must not contain business rules.

Example:

```php
final class AcknowledgeAlertController
{
    public function __invoke(
        AcknowledgeAlertRequest $request,
        AcknowledgeAlertHandler $handler,
    ): JsonResponse {
        $result = $handler->handle(
            new AcknowledgeAlert(
                organizationId: $request->tenant()->organizationId,
                alertId: AlertId::fromString($request->route('alert')),
                expectedVersion: $request->expectedVersion(),
            ),
        );

        return response()->json($result, 200);
    }
}
```

---

## Request Validation

Laravel Form Requests may validate:

* Required fields.
* scalar formats.
* UUID syntax.
* payload size.
* supported enum values.

Domain modules must still validate:

* Business invariants.
* lifecycle transitions.
* tenant ownership.
* authorization.
* current Resource state.

HTTP validation is not domain validation.

---

## Tenant Context Middleware

Middleware should establish one trusted Tenant Context before protected operations.

It must:

* Resolve the requested Organization.
* verify authenticated access.
* reject missing or conflicting scope.
* attach immutable context to the request.
* propagate correlation identifiers.

Route parameters alone must not establish tenant access.

---

## Authentication

Laravel authentication adapters may support:

* Browser Sessions.
* API tokens.
* external identity providers.
* service credentials.

Authentication output must be converted into Sentinel’s framework-independent Authentication Context.

Business modules must not depend on Laravel guard objects.

---

## Authorization

Laravel gates or Policies may act as transport adapters.

The authoritative authorization decision remains in the application Policy layer.

Controllers must not be the only enforcement point.

Queue jobs, scheduled commands, Workflow Steps, Agent Tools, and Plugin callbacks require the same Policy evaluation.

---

## Persistence

Eloquent models may be used as infrastructure persistence models.

They must not become domain Entities.

Repository implementations are responsible for:

* Mapping database rows to domain objects.
* preserving Organization scope.
* optimistic concurrency.
* saving authoritative state.
* translating database failures.
* enforcing repository contracts.

---

## Repository Binding

Laravel service providers bind module interfaces to infrastructure implementations.

Example:

```php
$this->app->bind(
    IncidentRepository::class,
    EloquentIncidentRepository::class,
);
```

Domain and application code depend only on repository interfaces.

---

## Transactions

Application handlers should use a framework-independent transaction contract.

Laravel provides its implementation through the database manager.

A state-changing transaction may include:

* Aggregate persistence.
* history records.
* idempotency records.
* Outbox Records.

External provider calls should occur outside long database transactions.

---

## Queues

Laravel jobs adapt Queue messages to module commands.

Jobs should contain:

```text
organization_id
acting_identity
resource identifiers
correlation_id
causation_id
payload_version
```

Job handlers must:

* Restore Tenant Context.
* reload authoritative state.
* evaluate authorization.
* remain idempotent.
* classify transient and permanent failures.

Jobs must not serialize complete mutable domain objects.

---

## Outbox

Laravel Workers publish pending Outbox Records.

The publisher should:

1. Claim Records safely.
2. serialize the versioned event.
3. publish it.
4. mark successful publication.
5. retry transient failures.

Consumers must tolerate duplicate delivery.

---

## Scheduler

Laravel scheduling should dispatch asynchronous work for:

* Workflow Timers.
* Plugin synchronization.
* retention.
* credential checks.
* maintenance.
* monitoring.

Long-running business work must execute in Workers, not inside the scheduler process.

---

## Serialization

The adapter maps module results into:

* JSON API responses.
* Queue payloads.
* event envelopes.
* provider requests.

Serialization must preserve:

* Stable field names.
* identifier formats.
* timestamp formats.
* event Versions.
* sensitive-field filtering.

Domain objects must not implement framework serializer interfaces.

---

## External Providers

External systems must use adapter interfaces.

Examples:

```text
NotificationProvider
AgentProvider
PluginProvider
ObjectStorage
SearchEngine
SecretStore
Clock
```

Provider SDKs remain inside infrastructure code.

---

## Error Mapping

Module errors should be mapped to API responses consistently.

Examples:

```text
Validation error      → 422
Authentication error  → 401
Authorization error   → 403
Unavailable Resource  → 404
Concurrency conflict  → 409
Rate limit            → 429
Dependency failure    → 503
```

Infrastructure exceptions must not be returned directly to clients.

---

## Observability

The runtime adapter should emit:

* Structured Logs.
* request metrics.
* traces.
* Queue metrics.
* scheduler heartbeat.
* provider-call telemetry.
* deployment Version.

Telemetry must propagate request and correlation identifiers and exclude secrets.

---

## Testing

Testing should include:

* Domain tests without Laravel.
* application-handler tests with fake interfaces.
* repository integration tests using PostgreSQL.
* HTTP contract tests.
* middleware tenant-isolation tests.
* Queue and Outbox tests.
* service-provider binding tests.
* architecture dependency tests.

Most business behavior should be testable without booting the Laravel framework.

---

## Prohibited Practices

The adapter must not:

* Put business logic in controllers.
* use Eloquent models as domain Entities.
* access another module’s tables directly.
* resolve tenants from route values without validation.
* rely only on Laravel Policies for authorization.
* serialize complete domain objects into jobs.
* use facades inside domain code.
* call providers directly from domain Entities.
* publish framework events as domain contracts.
* hide infrastructure failures behind successful responses.

---

## Business Rules

* Laravel is an adapter around Sentinel modules.
* domain and application rules remain framework-independent.
* controllers translate HTTP requests into commands and queries.
* Eloquent models remain infrastructure persistence models.
* Tenant Context and authorization apply to every execution path.
* repository implementations preserve tenant scope and concurrency.
* queues carry references and context, not domain objects.
* Outbox publication remains separate from domain transactions.
* provider SDKs stay inside infrastructure adapters.
* business behavior remains testable without Laravel.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/01-shared-kernel.md`
* `docs/modules/14-identity-access-module.md`
* `docs/deployment/01-local-development.md`
* `docs/deployment/02-docker-environment.md`
* `docs/deployment/06-workers-queues.md`
* `docs/api/00-api-overview.md`
* `docs/adr/ADR-009-core-is-framework-agnostic.md`
* `docs/adr/ADR-010-component-based-architecture.md`
