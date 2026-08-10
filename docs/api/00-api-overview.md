# API Overview

## Purpose

This document defines the platform-wide API architecture for Project Sentinel.

It establishes how clients, integrations, Plugins, Agents, and internal services interact with Sentinel through stable contracts.

Detailed endpoint specifications are defined in the remaining API documents.

---

## API Objectives

The Sentinel API must provide:

* Stable and predictable contracts.
* strict Organization isolation.
* explicit authentication and authorization.
* safe Resource creation and modification.
* consistent errors.
* reliable idempotency.
* optimistic concurrency control.
* efficient filtering and pagination.
* observable request processing.
* backward-compatible evolution.
* framework-independent domain contracts.
* support for asynchronous operations.

---

## API Scope

The public API covers:

* Organizations.
* Projects.
* Applications.
* Environments.
* Events.
* Signals where externally accessible.
* Alerts.
* Incidents.
* Knowledge.
* Agents.
* Plugins.
* Notifications where externally accessible.
* Search.
* Workflows.
* Webhooks.

Administrative and internal APIs may expose additional operations but follow the same security and consistency rules.

---

## API Styles

Sentinel primarily uses:

```text
HTTPS + JSON + REST
```

Additional interaction patterns may include:

* Webhooks for outbound event delivery.
* asynchronous operations for long-running work.
* event ingestion endpoints.
* file upload and download endpoints.
* internal messaging between components.
* future streaming APIs when justified.

REST is the initial public interface.

Internal domain architecture must not depend on HTTP.

---

## Base Path

The recommended public API base path is:

```text
/api/v1
```

Example:

```text
/api/v1/organizations/{organization_id}/incidents
```

Versioning is defined at the API boundary.

Domain models and database schemas are versioned independently.

---

## Resource Hierarchy

Tenant-owned Resources are accessed within an explicit Organization scope.

Example hierarchy:

```text
organizations
└── projects
    └── applications
        └── environments
            └── event-sources
```

Operational Resources remain Organization-scoped:

```text
organizations/{organization_id}/alerts
organizations/{organization_id}/incidents
organizations/{organization_id}/knowledge
organizations/{organization_id}/workflows
```

Nested routes should be used when the parent relationship is important to the operation.

Excessively deep route nesting should be avoided.

---

## Organization Context

Every tenant API operation must resolve one Organization context.

The Organization may be identified through:

* Route parameter.
* authenticated API Client ownership.
* verified webhook mapping.
* trusted internal execution context.

An Organization identifier supplied by the caller must be validated against the authenticated identity.

Resource identifiers alone never grant access.

---

## Authentication

Supported authentication methods may include:

* User Sessions.
* Personal or scoped API tokens.
* API Client credentials.
* service credentials.
* signed webhook requests.
* workload identities.
* future enterprise SSO.

Authentication proves identity.

It does not automatically grant permission to perform an operation.

Detailed rules are defined in:

```text
docs/api/02-authentication.md
```

---

## Authorization

Every protected request must evaluate:

* Acting identity.
* Organization Membership or ownership.
* requested operation.
* Resource scope.
* Role and permissions.
* current Resource state.
* Environment restrictions.
* approval requirements.
* security Policy.
* Agent or Plugin capability grants where relevant.

Authorization follows deny-by-default behavior.

Authorization must occur before sensitive Resource details are returned.

---

## Identity Types

API requests may be performed by:

* Users.
* API Clients.
* Services.
* Agents.
* Plugins.
* Workflows.
* trusted platform processes.

The acting identity type must remain traceable through:

* request context.
* domain commands.
* Audit Records.
* domain events.
* background jobs.

A non-human identity must not impersonate a User.

---

## Request Format

JSON request bodies should use:

```http
Content-Type: application/json
```

Example:

```json
{
  "title": "Checkout requests are failing",
  "severity": "high",
  "summary": "Payment requests are returning elevated errors."
}
```

Requests must be validated before application commands are executed.

Unknown or unsupported fields should normally be rejected for write operations.

---

## Response Format

A successful single-Resource response should return the Resource directly or through one consistent envelope.

Recommended format:

```json
{
  "data": {
    "id": "018f6f80-79aa-7c50-9ee2-85b41ee99511",
    "type": "incident",
    "attributes": {
      "title": "Checkout requests are failing",
      "status": "investigating",
      "severity": "high"
    }
  },
  "meta": {
    "request_id": "018f6f82-c6f1-71db-b394-abf8e00107ed"
  }
}
```

The exact envelope must remain consistent across all endpoints.

---

## Collection Responses

Collection endpoints should return:

```json
{
  "data": [],
  "pagination": {
    "next_cursor": null,
    "has_more": false
  },
  "meta": {
    "request_id": "018f6f82-c6f1-71db-b394-abf8e00107ed"
  }
}
```

Collection responses may also include authorized:

* totals.
* facets.
* applied filters.
* sort information.

Counts and facets must not reveal unauthorized Resources.

---

## Resource Identifiers

Public Resource identifiers should use globally unique, non-sequential identifiers.

Preferred format:

```text
UUIDv7
```

Identifiers must be:

* Stable.
* opaque to clients.
* safe for distributed creation.
* sortable where useful.
* independent from database sequence values.

Clients must not infer Organization ownership, creation order, permissions, or Resource type from an identifier.

---

## HTTP Methods

Sentinel uses standard HTTP semantics.

| Method   | Purpose                                                                 |
| -------- | ----------------------------------------------------------------------- |
| `GET`    | Read Resources                                                          |
| `POST`   | Create Resources or execute commands                                    |
| `PATCH`  | Apply partial authorized changes                                        |
| `PUT`    | Replace a complete replaceable representation when explicitly supported |
| `DELETE` | Request deletion, revocation, removal, or archival where appropriate    |

Business lifecycle Actions should not be forced into generic CRUD operations.

Example:

```http
POST /api/v1/organizations/{organization_id}/incidents/{incident_id}/resolve
```

This is preferred over an ambiguous generic status update when resolution has business rules and required evidence.

---

## Command Endpoints

Explicit command endpoints should be used for important lifecycle operations.

Examples:

```text
POST /alerts/{alert_id}/acknowledge
POST /alerts/{alert_id}/resolve
POST /incidents/{incident_id}/assign
POST /incidents/{incident_id}/mitigate
POST /incidents/{incident_id}/resolve
POST /knowledge/{knowledge_id}/publish
POST /agents/{agent_id}/execute
POST /plugins/{installation_id}/synchronize
POST /workflows/{workflow_id}/cancel
```

Command endpoints make business intent explicit.

---

## Status Codes

Common success codes:

|  Code | Meaning                                    |
| ----: | ------------------------------------------ |
| `200` | Successful read or command                 |
| `201` | Resource created                           |
| `202` | Accepted for asynchronous processing       |
| `204` | Successful operation with no response body |

Common error codes:

|  Code | Meaning                                         |
| ----: | ----------------------------------------------- |
| `400` | Invalid request                                 |
| `401` | Authentication required or invalid              |
| `403` | Authenticated but not authorized                |
| `404` | Resource unavailable or not visible             |
| `409` | Conflict, duplicate, or invalid lifecycle state |
| `412` | Concurrency precondition failed                 |
| `422` | Semantically invalid command                    |
| `429` | Rate limit exceeded                             |
| `500` | Unexpected platform failure                     |
| `503` | Required capability temporarily unavailable     |

Detailed error behavior is defined in:

```text
docs/api/03-errors.md
```

---

## Validation

Validation occurs in layers.

### Transport Validation

Checks:

* JSON syntax.
* required headers.
* body size.
* content type.
* field types.
* supported fields.

### Application Validation

Checks:

* Referenced Resource existence.
* Organization ownership.
* permissions.
* lifecycle state.
* expected Version.
* business rules.

### Domain Validation

Checks invariants owned by the domain capability.

A syntactically valid request may still be rejected as an invalid business command.

---

## Error Format

Errors should use one consistent structure.

Example:

```json
{
  "error": {
    "code": "incident.invalid_transition",
    "message": "The incident cannot be closed from its current state.",
    "details": {
      "current_status": "investigating",
      "requested_status": "closed"
    }
  },
  "meta": {
    "request_id": "018f6f82-c6f1-71db-b394-abf8e00107ed"
  }
}
```

Error responses must not expose:

* credentials.
* internal stack traces.
* database details.
* hidden Resource existence.
* another Organization’s data.
* sensitive Policy configuration.

---

## Idempotency

Creation and command endpoints that may be retried should support:

```http
Idempotency-Key: client-generated-key
```

Likely idempotent operations include:

* Event ingestion.
* Incident creation.
* Agent execution requests.
* Plugin synchronization requests.
* Workflow starts.
* Notification requests.
* protected external Actions.

The same key and equivalent request should return the original result.

The same key with a different request must be rejected.

Detailed behavior is defined in:

```text
docs/api/05-idempotency-concurrency.md
```

---

## Optimistic Concurrency

Mutable Resources should support expected-Version checks.

Possible request header:

```http
If-Match: "17"
```

Or an explicit request field:

```json
{
  "expected_version": 17
}
```

A Version mismatch returns:

```http
412 Precondition Failed
```

or a documented `409 Conflict`.

Silent last-write-wins behavior is prohibited for protected mutable Resources.

---

## Pagination

Large collections should use cursor-based pagination.

Example:

```http
GET /incidents?limit=50&cursor=encoded-cursor
```

Cursor pagination should use stable ordering.

Typical ordering:

```text
created_at DESC, id DESC
```

Large offset pagination should be avoided for high-volume Resources.

Detailed rules are defined in:

```text
docs/api/04-pagination-filtering.md
```

---

## Filtering

Filters should use predictable query parameters.

Example:

```http
GET /alerts?status=open&severity=critical&application_id={id}
```

Supported filters depend on the Resource.

Common filters include:

* Status.
* severity.
* priority.
* Project.
* Application.
* Environment.
* owner.
* date range.
* Resource Type.
* source.
* tag.

Unsupported filters should be rejected rather than silently ignored.

---

## Sorting

Sorting should use an explicit parameter.

Example:

```http
GET /incidents?sort=-declared_at
```

Suggested semantics:

```text
declared_at     ascending
-declared_at    descending
```

Only approved indexed fields should be sortable.

The server may enforce a stable identifier as a secondary sort field.

---

## Field Selection

Sparse field selection may be introduced when it provides clear value.

Example:

```http
GET /incidents/{id}?fields=id,title,status,severity
```

Field selection must not bypass authorization or sensitivity restrictions.

It is optional for the initial API.

---

## Resource Expansion

Related Resources may be expanded selectively.

Example:

```http
GET /incidents/{id}?include=alerts,owners
```

Expansion must be:

* Explicit.
* bounded.
* authorized.
* protected from recursive loading.
* limited to supported relationships.

The API must avoid unbounded nested responses.

---

## Bulk Operations

Bulk endpoints may be provided for high-value operations.

Examples:

* Bulk Alert acknowledgement.
* bulk assignment.
* Event ingestion batches.
* bulk export requests.

Bulk operations must define:

* Maximum item count.
* atomic or partial-success behavior.
* per-item errors.
* idempotency.
* authorization behavior.
* rate-limit cost.

A bulk request must not weaken per-Resource authorization.

---

## Asynchronous Operations

Long-running operations should return:

```http
202 Accepted
```

Examples:

* Large exports.
* Plugin synchronization.
* Agent execution.
* Workflow execution.
* Search rebuild.
* Organization deletion.
* retention jobs.

The response should include an operation Resource:

```json
{
  "data": {
    "id": "018f6f91-74d3-7464-8c3e-277a41b99ec4",
    "type": "operation",
    "attributes": {
      "status": "queued"
    }
  }
}
```

Clients may poll the operation or receive a webhook when supported.

---

## Operation Status

Asynchronous operations should expose:

* Operation identifier.
* Organization.
* operation type.
* status.
* requested time.
* start time.
* completion time.
* progress where meaningful.
* failure code.
* resulting Resource reference.

Possible statuses:

* `queued`
* `running`
* `completed`
* `completed_with_failures`
* `failed`
* `cancelled`
* `expired`

---

## Event Ingestion API

Event ingestion requires additional protections:

* Source authentication.
* payload-size limits.
* schema validation.
* idempotency.
* duplicate detection.
* timestamp validation.
* rate limiting.
* safe metadata normalization.

Accepted ingestion may return:

```http
202 Accepted
```

when processing continues asynchronously.

The API response must distinguish:

* Accepted.
* duplicate.
* rejected.
* failed.

---

## File Handling

Large evidence and export files should not be embedded directly in standard JSON payloads.

Recommended flow:

1. Request upload authorization.
2. receive an Organization-scoped temporary upload target.
3. upload the file.
4. complete file registration.
5. validate content and ownership.
6. attach the file to an authorized Resource.

Downloads should use short-lived signed access or authorized streaming endpoints.

Storage paths are not authorization.

---

## Webhooks

Webhooks deliver selected platform events to external consumers.

Webhook delivery must support:

* Organization scope.
* signed requests.
* stable event identifiers.
* retries.
* delivery history.
* idempotent consumer processing.
* secret rotation.
* endpoint suspension.
* replay controls.

Webhook delivery does not expose internal domain event payloads directly.

Public webhook contracts are separately versioned.

Detailed behavior is defined in:

```text
docs/api/19-webhooks.md
```

---

## Rate Limiting

Rate limits may apply by:

* Organization.
* identity.
* API Client.
* endpoint.
* Event Source.
* operation risk.
* subscription plan.
* provider dependency.

Rate-limit responses should include:

```http
429 Too Many Requests
```

and may provide:

* limit.
* remaining capacity.
* reset time.
* retry guidance.

Rate limits must prevent one Organization from exhausting shared capacity unfairly.

---

## Request Limits

The API should define limits for:

* Request body size.
* batch size.
* page size.
* uploaded file size.
* filter count.
* included relationships.
* date-range width.
* concurrent asynchronous operations.
* Event ingestion rate.

Limits should be documented and enforced consistently.

---

## API Versioning

The public API uses explicit major Versions.

Example:

```text
/api/v1
```

A new major Version is required for incompatible contract changes.

Compatible changes may include:

* Adding optional response fields.
* adding new endpoints.
* adding optional request fields.
* adding new error codes where behavior remains compatible.
* adding new webhook event types.

Potentially breaking changes include:

* Removing fields.
* changing field meaning.
* changing required fields.
* changing identifier format.
* changing status semantics.
* changing default ordering.
* changing authorization behavior.
* changing error structure.

---

## Deprecation

Deprecated APIs should provide:

* Deprecation notice.
* replacement endpoint.
* migration guidance.
* support deadline.
* final removal date.

Deprecation may be communicated through:

* Documentation.
* response headers.
* release notes.
* customer notices.

A deprecated endpoint must remain secure and observable until removed.

---

## Internal APIs

Internal service APIs must follow the same principles for:

* Authentication.
* tenant context.
* idempotency.
* concurrency.
* errors.
* observability.
* versioning.

Network location alone is not sufficient trust.

Internal components should prefer application contracts and messaging where direct synchronous coupling is unnecessary.

---

## API and Domain Separation

Controllers and transport handlers must not contain domain business logic.

Recommended flow:

```text
HTTP Request

↓

Authentication

↓

Tenant Context

↓

Request Validation

↓

Application Command or Query

↓

Authorization and Domain Rules

↓

Persistence

↓

Response Mapping
```

Domain objects must not depend on:

* HTTP requests.
* response objects.
* route definitions.
* Laravel controllers.
* JSON serialization.

---

## Query and Command Separation

Read operations and write operations should remain conceptually separate.

Queries:

* Return authorized representations.
* must not produce hidden business changes.
* may use optimized Read Models.

Commands:

* Express business intent.
* validate invariants.
* change authoritative state.
* create domain history.
* create Outbox events.
* produce Audit Records where required.

---

## Consistency Expectations

Clients must understand that Sentinel uses both:

* Strong transactional consistency within a domain operation.
* eventual consistency across Search, Notifications, analytics, and some cross-domain Projections.

A successfully created Incident may not appear in Search immediately.

The authoritative Incident endpoint should reflect the committed state.

---

## Request Correlation

Every request must have a Correlation Identifier.

Recommended header:

```http
X-Correlation-ID: client-or-server-generated-id
```

If the client does not provide one, Sentinel generates it.

The response should return it.

Correlation identifiers should propagate through:

* Logs.
* traces.
* domain commands.
* domain events.
* background jobs.
* Agent Executions.
* Plugin Executions.
* Workflow Instances.
* Audit Records.

---

## Request Identifier

Every API request receives a unique request identifier.

Recommended response header:

```http
X-Request-ID: generated-request-id
```

The request identifier identifies one HTTP request.

The correlation identifier may connect multiple related requests and asynchronous operations.

---

## Observability

API telemetry should include:

* Endpoint.
* method.
* status code.
* latency.
* request size.
* response size.
* Organization identifier where safely permitted.
* identity type.
* rate-limit result.
* error code.
* request and correlation identifiers.

Telemetry must not include:

* credentials.
* raw tokens.
* unrestricted payloads.
* private Agent prompts.
* sensitive evidence.
* personal data without purpose.

---

## Audit

Protected API operations may produce Audit Records.

Examples:

* Membership changes.
* Role changes.
* credential operations.
* Incident severity changes.
* Knowledge publication.
* Plugin installation.
* Agent activation.
* protected Workflow Actions.
* retention changes.
* data exports.

Routine reads may not require full Audit Records unless the Resource is sensitive.

---

## Security Requirements

The API must enforce:

* TLS.
* secure authentication.
* explicit Organization scope.
* authorization for every protected operation.
* input validation.
* output filtering.
* rate limits.
* request-size limits.
* secure error handling.
* replay protection where required.
* credential redaction.
* Audit for sensitive operations.

Cross-Organization access attempts must be rejected.

---

## API Documentation

The API should be described using an OpenAPI specification.

The specification should define:

* Paths.
* methods.
* authentication.
* request schemas.
* response schemas.
* errors.
* pagination.
* idempotency.
* concurrency.
* examples.
* deprecation status.

The generated specification must be reviewed as a contract, not treated as documentation produced automatically from controllers without oversight.

---

## Client SDKs

Future SDKs may be generated or maintained for:

* PHP.
* JavaScript or TypeScript.
* Python.
* Go.
* other supported environments.

SDKs must not hide:

* Organization context.
* idempotency behavior.
* asynchronous operation state.
* concurrency conflicts.
* authorization failures.

The HTTP API remains the authoritative external contract.

---

## Prohibited Practices

The following are prohibited:

* Accessing tenant Resources without Organization scope.
* trusting Organization identifiers without identity validation.
* placing business logic in controllers.
* exposing database models directly.
* returning internal stack traces.
* using Resource identifiers as authorization.
* silently ignoring unsupported fields or filters.
* silent last-write-wins updates.
* retrying non-idempotent commands without protection.
* returning unauthorized counts or relationship data.
* exposing internal domain events directly as webhook contracts.
* creating unbounded collection responses.
* making long-running requests synchronous without justification.
* logging credentials or sensitive request bodies.
* changing published API meaning without versioning.

---

## Business Rules

* Every tenant API operation resolves exactly one Organization context.
* Authentication and authorization remain separate.
* Resource identifiers do not grant access.
* Important lifecycle changes use explicit commands.
* Protected mutable updates use optimistic concurrency.
* Retryable creation and command requests use idempotency.
* Large collections use cursor pagination.
* Unsupported filters and fields are rejected.
* Bulk operations preserve per-Resource authorization.
* Long-running operations return asynchronous operation state.
* Search and other Projections may be eventually consistent.
* Every request receives request and correlation identifiers.
* Sensitive information must not appear in errors or telemetry.
* Public API contracts are versioned independently from implementation.
* Domain logic remains independent from HTTP and Laravel.
* Cross-Organization access is denied by default.

---

## Success Criteria

The API architecture is successful when:

* Clients can interact with Sentinel predictably.
* Organization isolation is preserved.
* authorization remains consistent.
* retries do not create duplicate effects.
* concurrent changes do not overwrite each other.
* errors are stable and actionable.
* large collections remain efficient.
* asynchronous work remains observable.
* API evolution can remain backward compatible.
* domain logic remains framework-independent.
* Plugins, Agents, and external clients use governed contracts.
* API activity remains traceable through request and correlation identifiers.

---

## Related Documents

* `docs/api/01-api-conventions.md`
* `docs/api/02-authentication.md`
* `docs/api/03-errors.md`
* `docs/api/04-pagination-filtering.md`
* `docs/api/05-idempotency-concurrency.md`
* `docs/api/06-organizations-api.md`
* `docs/api/07-projects-api.md`
* `docs/api/08-applications-api.md`
* `docs/api/09-environments-api.md`
* `docs/api/10-events-api.md`
* `docs/api/11-alerts-api.md`
* `docs/api/12-incidents-api.md`
* `docs/api/13-knowledge-api.md`
* `docs/api/14-agents-api.md`
* `docs/api/15-plugins-api.md`
* `docs/api/16-notifications-api.md`
* `docs/api/17-search-api.md`
* `docs/api/18-workflows-api.md`
* `docs/api/19-webhooks.md`
* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/02-identity-access-schema.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/functional/16-public-api.md`
* `docs/architecture/03-component-architecture.md`
* `docs/architecture/04-data-flow.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-009-core-is-framework-agnostic.md`
