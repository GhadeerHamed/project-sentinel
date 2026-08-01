# Tenant Isolation

## Purpose

This document defines how Project Sentinel prevents data and operations from crossing Organization boundaries.

Organization is Sentinel’s primary tenant boundary.

---

## Core Invariant

Every tenant-owned Resource belongs to exactly one:

```text
organization_id
```

Every read, write, relationship, background job, cache entry, Search document, and external operation must preserve that ownership.

Resource identifiers alone never establish access.

---

## Tenant Context

Every operation must resolve one trusted Tenant Context containing:

```text
organization_id
acting_identity_id
acting_identity_type
request_id
correlation_id
```

Tenant Context must be established before accessing tenant-owned data.

Client-supplied Organization identifiers must be validated against the authenticated identity.

---

## Database Isolation

Tenant-owned tables must include:

```sql
organization_id UUID NOT NULL
```

Typical lookup:

```sql
SELECT *
FROM incidents
WHERE organization_id = :organization_id
  AND id = :incident_id;
```

Prohibited lookup:

```sql
SELECT *
FROM incidents
WHERE id = :incident_id;
```

Composite tenant-aware relationships should be used where practical:

```sql
FOREIGN KEY (organization_id, application_id)
REFERENCES applications (organization_id, id);
```

---

## Repository Rules

Application repositories must require Tenant Context for tenant-owned operations.

Preferred:

```php
$incident = $repository->find(
    organizationId: $context->organizationId,
    incidentId: $incidentId,
);
```

Prohibited:

```php
$incident = $repository->find($incidentId);
```

Generic unscoped tenant repositories should not be exposed to normal application code.

---

## Authorization

Tenant ownership validation occurs before Resource-level permission checks.

Required order:

1. Authenticate identity.
2. resolve Organization.
3. verify identity access to Organization.
4. load Resource within that Organization.
5. evaluate operation permission.
6. execute the command or query.

A Resource belonging to another Organization should normally appear unavailable.

---

## Relationships

Relationships between tenant-owned Resources must preserve matching Organization ownership.

Examples:

* Project and Application.
* Application and Environment.
* Alert and Incident.
* Incident and evidence.
* Workflow and target Resource.
* Agent execution and context.
* Plugin Installation and external mapping.

Cross-Organization relationships must be rejected even when both identifiers exist.

---

## Background Jobs

Every tenant background job must carry:

```text
organization_id
operation identifier
acting identity or system identity
correlation_id
```

Workers must restore Tenant Context before:

* Loading Resources.
* evaluating Policy.
* writing data.
* calling Plugins.
* executing Agents.
* publishing tenant events.

Queue names alone are not sufficient tenant isolation.

---

## Domain Events

Tenant domain events must include:

```text
organization_id
resource_type
resource_id
event_id
correlation_id
```

Consumers must verify Organization scope before updating Projections or triggering Actions.

Internal event identifiers do not grant access.

---

## Cache Isolation

Tenant cache keys must include Organization scope.

Example:

```text
incident:{organization_id}:{incident_id}
```

Authorization-sensitive caches may also include:

```text
identity_id
authorization_version
```

Prohibited:

```text
incident:{incident_id}
```

Cache invalidation must preserve the same tenant key structure.

---

## Search Isolation

Every tenant Search document and query must include:

```text
organization_id
```

Search isolation applies to:

* Results.
* counts.
* facets.
* suggestions.
* highlights.
* semantic similarity.
* related previews.

Search must never reveal the existence of another Organization’s Resources.

---

## File and Object Storage

Tenant files must use Organization-scoped ownership metadata and storage paths.

Example:

```text
organizations/{organization_id}/incidents/{incident_id}/evidence/{file_id}
```

Storage paths are organizational aids, not authorization.

Every upload and download still requires Policy evaluation.

---

## Plugins

Every Plugin Installation belongs to one Organization and uses a distinct Plugin Identity.

Plugin execution must not:

* Use another Organization’s credentials.
* map external Resources across Organizations.
* reuse another tenant’s synchronization cursor.
* perform Actions outside its granted scope.

Provider callbacks must resolve to exactly one Installation and Organization.

---

## Agents

Agent context must be assembled only from Resources belonging to the execution Organization.

Agent isolation applies to:

* Prompts.
* evidence.
* Knowledge.
* Tool calls.
* provider requests.
* outputs.
* embeddings.
* execution Logs.

Provider-side context reuse between Organizations is prohibited.

---

## Workflows

Workflow Definitions, Instances, Tasks, approvals, and Actions must belong to the same Organization as their referenced Resources.

A Workflow cannot:

* Assign Tasks to another Organization.
* request approval from another Organization.
* target another tenant’s Resource.
* launch cross-tenant Agent or Plugin executions.

---

## Notifications and Webhooks

Notification recipients, Channels, Templates, and source Resources must belong to the same Organization unless an explicit external-recipient Policy permits otherwise.

Webhook endpoints and deliveries must remain Organization-scoped.

Webhook payloads must not contain data from multiple Organizations.

---

## Logging and Telemetry

Logs and traces may record Organization identifiers where operationally required.

They must not include unnecessary tenant payloads or secrets.

Shared telemetry systems must enforce:

* Restricted access.
* retention controls.
* safe field selection.
* tenant-aware investigation procedures.

---

## Platform-Wide Operations

Operations spanning multiple Organizations are allowed only for governed platform functions such as:

* Billing.
* infrastructure health.
* migration.
* security investigation.
* retention scheduling.

Each tenant must still be processed independently.

Platform-wide access requires:

* Dedicated privileged identity.
* explicit purpose.
* restricted scope.
* complete Audit recording.

---

## Testing Requirements

Automated tests must verify:

* Resource IDs cannot cross Organization boundaries.
* nested Resource ownership is validated.
* caches do not leak between tenants.
* Search results and counts remain isolated.
* background jobs restore Tenant Context.
* Agent and Plugin operations remain scoped.
* bulk operations validate every item.
* exports contain only one authorized Organization.

Tenant isolation tests are required for every new capability.

---

## Incident Handling

A suspected tenant-isolation failure is a critical security Incident.

Required response includes:

1. Stop the affected operation.
2. preserve evidence.
3. identify impacted Organizations.
4. revoke affected credentials where required.
5. contain the defect.
6. notify according to legal and contractual obligations.
7. correct leaked Projections, caches, or files.
8. complete a Post-Incident Review.

---

## Prohibited Practices

The following are prohibited:

* Loading tenant Resources by ID alone.
* trusting route Organization identifiers.
* unscoped cache keys.
* cross-tenant Search or embeddings.
* background jobs without Tenant Context.
* direct Plugin database access.
* shared tenant credentials.
* unscoped object-storage access.
* multi-tenant exports without explicit platform governance.
* using UI filtering as a security control.

---

## Business Rules

* Organization is the primary tenant boundary.
* Every tenant-owned Resource includes `organization_id`.
* Tenant Context is required before tenant data access.
* Resource identifiers never grant access.
* Relationships must preserve Organization ownership.
* asynchronous processing follows the same isolation rules as HTTP requests.
* caches, Search, files, Agents, Plugins, and Workflows remain tenant-scoped.
* platform-wide operations require privileged, audited access.
* suspected tenant leakage is treated as a critical security Incident.

---

## Related Documents

* `docs/security/00-security-overview.md`
* `docs/security/01-threat-model.md`
* `docs/security/03-authentication-security.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-003-multi-tenancy.md`
