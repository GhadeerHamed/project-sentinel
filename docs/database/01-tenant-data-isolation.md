# Tenant Data Isolation

## Purpose

This document defines how Project Sentinel isolates data between Organizations.

Every Organization is a tenant.

Tenant isolation must prevent one Organization from:

* Reading another Organization’s data.
* modifying another Organization’s data.
* receiving another Organization’s events.
* searching another Organization’s records.
* executing Agents or Plugins against another Organization.
* accessing another Organization through background jobs, caches, exports, or derived data.

Tenant isolation is a mandatory platform invariant.

It must be enforced throughout the full request and processing lifecycle.

---

## Objectives

Tenant isolation must ensure:

* Every tenant-owned record has an Organization owner.
* Every data operation runs within an Organization context.
* Authorization includes Organization ownership.
* database queries include tenant scope.
* background jobs preserve tenant context.
* domain events preserve tenant context.
* caches and Search Projections preserve tenant boundaries.
* external integrations remain tenant-scoped.
* administrative access remains governed.
* isolation failures are rejected and auditable.

---

## Isolation Model

Sentinel initially uses a shared-database, shared-schema tenancy model.

```text
One PostgreSQL Database

↓

Shared Tables

↓

organization_id on Tenant-Owned Records

↓

Application and Database Enforcement
```

This model provides:

* Lower operational cost.
* simpler migrations.
* easier development.
* easier transactional consistency.
* efficient early-stage operation.
* a clear path to future partitioning.

The architecture must still allow selected Organizations to move to dedicated storage later if justified.

---

## Organization as Tenant Boundary

The `organizations` table defines the primary tenant boundary.

Every tenant-owned Resource must belong to exactly one Organization.

Examples include:

* Projects.
* Applications.
* Environments.
* Events.
* Signals.
* Alerts.
* Incidents.
* Knowledge.
* Agent Definitions.
* Plugin Installations.
* Notifications.
* Workflow Instances.
* API Clients.

Organization ownership should normally be represented by:

```sql
organization_id UUID NOT NULL
```

---

## Tenant-Owned and Platform-Owned Data

Data must be classified as either:

### Tenant-Owned Data

Belongs to one Organization.

Examples:

* Organization Memberships.
* Applications.
* Alerts.
* Incidents.
* Plugin Installations.
* Agent Executions.
* Workflow Instances.

Tenant-owned data requires `organization_id`.

### Platform-Owned Data

Owned by Sentinel rather than one Organization.

Examples:

* Platform Feature Definitions.
* supported Plugin Definitions.
* supported AI Provider Definitions.
* global system configuration.
* platform migration records.

Platform-owned data must not contain tenant-specific information unless it is stored in a separate tenant-owned record.

---

## Ownership Rules

* Every tenant-owned record must include `organization_id`.
* Organization ownership is assigned when the record is created.
* Organization ownership must not normally change.
* Child records must belong to the same Organization as their parent.
* Cross-Organization parent-child relationships are prohibited.
* Resource identifiers do not prove Organization ownership.
* Missing Organization context must result in rejection.

---

## Tenant Context

Tenant Context represents the Organization under which an operation executes.

Tenant Context should include:

```text
organization_id
acting_identity_id
identity_type
request_id
correlation_id
authorization_context
```

It may also include:

```text
project_scope
application_scope
environment_scope
```

Tenant Context must be established before accessing tenant-owned data.

---

## Tenant Context Sources

Tenant Context may originate from:

* An authenticated User Membership.
* An API Client.
* A Service Identity.
* An Agent Identity.
* A Plugin Identity.
* A Workflow Instance.
* A trusted platform process.
* A verified domain event.

Tenant Context must not be accepted directly from an untrusted request field without validation.

---

## Request Processing

A tenant-scoped request should follow this sequence:

```text
Request Received

↓

Identity Authenticated

↓

Organization Context Resolved

↓

Membership or Identity Ownership Verified

↓

Authorization Evaluated

↓

Tenant-Scoped Query Executed

↓

Result Ownership Revalidated
```

The Organization identifier in the route, token, header, or payload must be validated against the authenticated identity.

---

## Database Query Rules

Every query against tenant-owned data must include Organization scope.

Correct:

```sql
SELECT *
FROM incidents
WHERE organization_id = :organization_id
  AND id = :incident_id;
```

Incorrect:

```sql
SELECT *
FROM incidents
WHERE id = :incident_id;
```

A globally unique identifier does not remove the need for Organization scope.

---

## Write Operations

Tenant ownership must be included in:

* Create operations.
* update operations.
* delete or archive operations.
* lifecycle transitions.
* bulk operations.
* relationship creation.
* background processing.
* imports.
* synchronization.

Example:

```sql
UPDATE alerts
SET status = :status,
    version = version + 1
WHERE organization_id = :organization_id
  AND id = :alert_id
  AND version = :expected_version;
```

---

## Insert Rules

When inserting a tenant-owned record:

* `organization_id` must come from trusted Tenant Context.
* It must not be copied directly from untrusted payload input.
* Related Resources must be checked for the same Organization.
* Organization ownership must be written within the same transaction.
* Required tenant-aware uniqueness rules must be enforced.

Example:

```sql
INSERT INTO applications (
    id,
    organization_id,
    project_id,
    name,
    created_at
)
VALUES (
    :id,
    :organization_id,
    :project_id,
    :name,
    :created_at
);
```

---

## Tenant-Aware Foreign Keys

Important parent-child relationships should enforce matching Organization ownership.

Example design:

```sql
UNIQUE (organization_id, id)
```

Then child tables may reference both values:

```sql
FOREIGN KEY (organization_id, project_id)
REFERENCES projects (organization_id, id)
```

This prevents a child record from referencing a parent belonging to another Organization.

Composite tenant-aware foreign keys should be used where they provide meaningful protection.

---

## Tenant-Aware Uniqueness

Business uniqueness should normally be scoped by Organization.

Examples:

```sql
UNIQUE (organization_id, slug)
UNIQUE (organization_id, email)
UNIQUE (organization_id, project_id, name)
UNIQUE (organization_id, plugin_installation_id, external_id)
```

Global uniqueness should be reserved for truly platform-global values.

---

## Row-Level Security

PostgreSQL Row-Level Security may be introduced as an additional defense layer.

Possible policy concept:

```sql
organization_id = current_setting('sentinel.organization_id')::uuid
```

Row-Level Security can help protect against missing application query scope.

However, it must not replace:

* Application authorization.
* domain validation.
* tenant-aware foreign keys.
* testing.
* audit controls.

Row-Level Security should be enabled only after connection management, background jobs, migrations, and administrative access are designed safely.

---

## Connection Context

When database-level tenant context is used:

* Tenant Context must be set per transaction or request.
* pooled connections must not retain a previous tenant.
* context must be cleared reliably.
* background workers must set context explicitly.
* administrative processes must use separate controlled roles.
* failed requests must not leak context to the next operation.

Connection-level tenant context must be tested under connection pooling.

---

## Repository Rules

Every tenant-owned Repository operation must require Tenant Context.

Preferred interface:

```php
public function find(
    OrganizationId $organizationId,
    IncidentId $incidentId
): ?Incident;
```

Avoid interfaces that allow tenant-owned Resources to be fetched by Resource identifier alone.

Repository methods should make tenant scope explicit.

---

## Global Scopes

Framework-level global query scopes may provide convenience but must not be the only tenant-isolation control.

Risks include:

* Scopes being disabled.
* raw queries bypassing scopes.
* background jobs missing context.
* administrative queries behaving unexpectedly.
* relationships loading without correct scope.

Laravel global scopes may be used as an additional protection layer, not as the architectural boundary.

---

## Domain Rules

Domain objects should carry Organization ownership where relevant.

Examples:

```text
Alert.organizationId
Incident.organizationId
KnowledgeEntry.organizationId
WorkflowInstance.organizationId
```

Commands should also include Organization identity.

Example:

```text
ResolveIncident
- organizationId
- incidentId
- expectedVersion
- resolution
```

The Application Layer must verify that all referenced Resources belong to the same Organization.

---

## Cross-Domain Operations

Cross-domain operations must preserve Organization identity.

Example:

```text
Create Incident from Alert
```

Required validation:

1. Alert belongs to the Organization.
2. Incident command uses the same Organization.
3. referenced Application and Environment belong to the same Organization.
4. resulting domain event contains the Organization identifier.
5. downstream consumers enforce the same Organization.

Cross-domain operations must never infer Organization ownership only from a foreign Resource identifier.

---

## Domain Events

Every tenant-scoped domain event must include:

```text
event_id
event_type
organization_id
aggregate_type
aggregate_id
occurred_at
correlation_id
causation_id
```

Consumers must validate `organization_id` before processing.

An event must not modify a Resource belonging to another Organization.

---

## Outbox Isolation

Outbox records for tenant-owned events must include `organization_id`.

Example:

```sql
CREATE TABLE outbox_messages (
    id UUID PRIMARY KEY,
    organization_id UUID,
    event_type VARCHAR(255) NOT NULL,
    aggregate_type VARCHAR(100) NOT NULL,
    aggregate_id UUID NOT NULL,
    payload JSONB NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    published_at TIMESTAMPTZ
);
```

Platform-owned events may have a nullable Organization identifier only when their meaning is truly global.

---

## Background Jobs

Every tenant-scoped background job must include:

* Organization identifier.
* acting Identity or process.
* target Resource.
* Correlation Identifier.
* required authorization context where applicable.

Workers must:

1. Restore Tenant Context.
2. validate Organization status.
3. reload current authorization where required.
4. access only tenant-scoped records.
5. clear Tenant Context after processing.

A job must not depend on request-local state that no longer exists.

---

## Scheduled Jobs

Scheduled processing may operate across multiple Organizations.

Examples:

* Retention processing.
* Knowledge review reminders.
* Workflow timeout checks.
* Plugin health checks.

Safe pattern:

```text
Platform Scheduler

↓

Select Eligible Organizations

↓

Create One Tenant-Scoped Job per Organization

↓

Process Each Organization Independently
```

Large cross-tenant transactions should be avoided.

---

## Queue Isolation

Queue messages must preserve Organization ownership.

Queue isolation may be implemented through:

* Tenant identifiers in messages.
* tenant-aware worker middleware.
* separate queues for high-risk workloads.
* workload limits per Organization.
* Organization-specific rate controls.

Queue names alone must not be treated as the security boundary.

---

## Cache Isolation

Every tenant-owned cache key must include Organization scope.

Correct:

```text
organization:{organization_id}:incident:{incident_id}
```

Incorrect:

```text
incident:{incident_id}
```

Cache entries must not contain cross-Organization collections unless they are platform-owned and contain no tenant-sensitive data.

---

## Authorization Cache

Authorization cache keys should include:

* Organization.
* Identity.
* Membership Version.
* Role Version.
* Policy Version.
* Resource Scope.

Example:

```text
authz:{organization_id}:{identity_id}:{authorization_version}
```

Membership suspension, Role changes, and permission revocation must invalidate or expire relevant cache entries rapidly.

---

## Search Isolation

Every Search Projection must include:

```text
organization_id
resource_type
resource_id
source_version
sensitivity
```

Every Search query must filter by Organization before ranking or returning results.

Search must also protect:

* Result counts.
* Facets.
* suggestions.
* highlights.
* semantic vectors.
* cached queries.

Unauthorized Resources must not be revealed indirectly through Search metadata.

---

## Semantic Search Isolation

Semantic representations must remain tenant-scoped.

Possible isolation approaches include:

* Organization field filtering.
* Organization-specific collections.
* Organization-specific namespaces.
* dedicated indexes for selected tenants.

Semantic similarity must never search across Organizations unless an explicit platform-owned public Knowledge source is involved.

---

## Object Storage Isolation

Files and large evidence stored outside the relational database must preserve tenant ownership.

Object paths should include Organization scope.

Example:

```text
organizations/{organization_id}/incidents/{incident_id}/evidence/{file_id}
```

Access must use:

* Authorized application endpoints.
* short-lived signed access.
* Organization validation.
* restricted storage credentials.

Storage paths must not be treated as authorization.

---

## File Metadata

File metadata should include:

```text
id
organization_id
resource_type
resource_id
storage_provider
storage_key
content_type
size
checksum
sensitivity
created_at
```

The storage object and database metadata must belong to the same Organization.

---

## Plugin Isolation

Every Plugin Installation belongs to one Organization.

Plugin operations must be scoped by:

* Plugin Installation.
* Plugin Identity.
* Organization.
* declared capability.
* Resource Scope.
* Environment Scope.

Plugin credentials must not be reused across Organizations unless the provider integration explicitly uses a governed platform-owned credential model.

Provider callbacks must be mapped to the correct Plugin Installation and Organization.

---

## Agent Isolation

Every Organization-specific Agent Execution belongs to one Organization.

Agent context must be limited to:

* Authorized Organization data.
* approved Resource Scope.
* approved Tools.
* permitted sensitivity.
* execution purpose.

Agent prompts, provider requests, embeddings, and results must not combine tenant data.

Provider caching and retention settings must follow Organization Policies.

---

## Notification Isolation

Notifications must resolve recipients only within their Organization unless an explicitly approved external destination is used.

Notification isolation applies to:

* Templates.
* recipient groups.
* Channels.
* provider credentials.
* delivery attempts.
* acknowledgement.
* Notification history.

A Team or Member identifier from another Organization must be rejected.

---

## Workflow Isolation

Every Workflow Instance belongs to one Organization.

All related:

* Tasks.
* Steps.
* approvals.
* evidence references.
* Actions.
* Agent Executions.
* Plugin Executions.

must belong to the same Organization unless referencing a platform-owned definition.

Workflow context must never merge data from multiple Organizations.

---

## Audit Isolation

Tenant audit records must include `organization_id`.

Organization administrators may view only audit records authorized for their Organization.

Platform operators may access broader audit data only through privileged, purpose-limited, and audited access.

Platform-wide Audit storage must still support efficient Organization filtering and retention.

---

## Exports

Every export must have one explicit Organization scope.

Export processing must:

* Revalidate authorization.
* filter every source by Organization.
* preserve sensitivity restrictions.
* store the output under Organization scope.
* use time-limited access.
* record an Audit entry.
* remove expired export files.

Cross-Organization exports are prohibited except for governed platform operations.

---

## Imports

Imports must:

* Execute within one Organization.
* validate every referenced Resource.
* reject cross-Organization identifiers.
* apply tenant-aware uniqueness.
* preserve source information.
* isolate failures.
* produce an import report.

One Organization’s invalid data must not affect another Organization.

---

## Analytics Isolation

Tenant analytics must be grouped and filtered by Organization.

Derived aggregates must include Organization ownership unless they are intentionally anonymized platform-wide metrics.

Platform analytics must not expose:

* Organization identity.
* customer data.
* operational details.
* sensitive Resource counts.

without explicit authorization and purpose.

---

## Observability Isolation

Operational telemetry may include Organization identifiers for diagnosis and impact analysis.

Telemetry access must prevent one Organization from viewing another Organization’s data.

Metrics must avoid unsafe tenant identifiers in uncontrolled high-cardinality dimensions.

Logs and traces must not include unrestricted tenant payloads.

---

## Administrative Access

Platform administrators may require controlled access for:

* Support.
* security response.
* migration.
* recovery.
* legal obligations.

Administrative tenant access must be:

* Explicit.
* time-bounded where possible.
* purpose-limited.
* strongly authenticated.
* authorized.
* auditable.
* revocable.

Administrative access must not rely on hidden tenant-scope bypasses.

---

## Support Access

Support access should require:

* Support identity.
* target Organization.
* reason.
* approved scope.
* start time.
* expiration.
* approval where required.
* Audit recording.

Support access must not create a normal Organization Membership unless explicitly intended.

---

## Organization Suspension

Suspending an Organization may restrict:

* User Sessions.
* API access.
* Plugin execution.
* Agent execution.
* Workflow execution.
* new ingestion.

Suspension must not:

* move data into another tenant context.
* remove Organization ownership.
* delete audit history.
* allow another Organization to claim its identifiers.
* bypass retention rules.

---

## Organization Deletion

Organization deletion is a controlled lifecycle, not a simple cascade.

Possible stages:

```text
Active

↓

Suspended

↓

Pending Deletion

↓

Retention Hold

↓

Deletion Processing

↓

Deleted
```

Deletion processing must consider:

* Legal Holds.
* contractual retention.
* operational evidence.
* object storage.
* Search Projections.
* caches.
* backups.
* provider data.
* Audit requirements.

---

## Organization Migration

A future Organization migration may move tenant data to:

* A dedicated database.
* another region.
* another storage cluster.
* an enterprise deployment.

Migration must preserve:

* Resource identifiers.
* Organization ownership.
* relationships.
* historical evidence.
* Outbox state.
* Workflow state.
* encryption requirements.
* auditability.

Application contracts must not depend on the original physical database location.

---

## Dedicated Tenant Storage

Dedicated storage may be introduced for selected Organizations because of:

* Data residency.
* enterprise contracts.
* scale.
* compliance.
* performance isolation.
* security requirements.

The logical data model should remain consistent across shared and dedicated storage.

Tenant routing must occur before Repository access.

---

## Testing Requirements

Tenant isolation testing must include:

* Cross-Organization reads.
* cross-Organization updates.
* cross-Organization deletes.
* relationship creation.
* Search queries.
* cache access.
* queue processing.
* background jobs.
* Agent context.
* Plugin callbacks.
* exports.
* file access.
* API bulk operations.
* administrative access.
* concurrent requests using pooled connections.

Every tenant-owned Repository must have negative isolation tests.

---

## Required Test Pattern

For each protected Resource:

1. Create Organization A.
2. Create Organization B.
3. Create the Resource for Organization A.
4. authenticate as an identity from Organization B.
5. attempt to read the Resource.
6. attempt to modify the Resource.
7. attempt to reference it from another Resource.
8. confirm every operation is rejected.
9. confirm the Resource remains unchanged.
10. confirm sensitive existence is not disclosed.

---

## Isolation Failure Handling

When a tenant-isolation violation is attempted:

* Reject the operation.
* avoid revealing restricted Resource details.
* record a security event where appropriate.
* preserve the Correlation Identifier.
* trigger Alert evaluation for suspicious patterns.
* avoid partial writes.
* invalidate unsafe cached data if necessary.

An isolation violation must never be converted into a normal not-scoped operation.

---

## Monitoring

Sentinel should monitor:

* Cross-Organization access attempts.
* missing Tenant Context.
* tenant query failures.
* mismatched foreign keys.
* Search ownership mismatches.
* cache-key collisions.
* queue messages without Organization context.
* Plugin callback mapping failures.
* Agent context violations.
* export scope violations.
* administrative tenant access.

Repeated or suspicious failures may create security Alerts.

---

## Prohibited Practices

The following are prohibited:

* Fetching tenant-owned data by identifier alone.
* trusting `organization_id` directly from request payloads.
* using hidden global state as the only Tenant Context.
* sharing cache keys across Organizations.
* processing queue jobs without Organization context.
* creating cross-Organization foreign relationships.
* reusing tenant credentials across Organizations without governance.
* combining tenant Agent context.
* returning unauthorized Search counts or suggestions.
* using storage paths as authorization.
* performing broad administrative access without Audit.
* relying only on Laravel global scopes.
* bypassing tenant filters for convenience.
* moving a Resource between Organizations by changing `organization_id`.

---

## Business Rules

* Every tenant-owned record belongs to exactly one Organization.
* Every tenant-owned table includes `organization_id`.
* Tenant Context must be established before data access.
* Tenant Context must originate from a trusted identity or process.
* Every tenant query includes Organization scope.
* Resource identifiers do not grant access.
* Parent and child Resources must belong to the same Organization.
* Tenant-aware uniqueness must include Organization scope.
* Background jobs must restore Tenant Context explicitly.
* Domain events must preserve Organization identity.
* Caches and Search Projections must preserve Organization scope.
* Agent and Plugin operations must remain tenant-scoped.
* Exports and files must belong to one Organization.
* Administrative access must remain governed and auditable.
* Organization ownership must not be changed to transfer Resources.
* Missing or conflicting Organization ownership results in rejection.
* Tenant-isolation failure must never result in partial success.
* Cross-Organization access is denied by default.

---

## Success Criteria

Tenant data isolation is successful when:

* One Organization cannot access another Organization’s data.
* every tenant-owned record has explicit ownership.
* every processing path preserves Tenant Context.
* database relationships prevent cross-tenant references.
* background processing remains isolated.
* caches and Search cannot leak tenant data.
* Agents and Plugins receive only authorized tenant context.
* exports and files remain tenant-scoped.
* administrative access remains controlled.
* isolation violations are detectable and auditable.
* shared infrastructure does not weaken tenant boundaries.
* future dedicated storage can be introduced without changing domain contracts.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/03-organization-project-schema.md`
* `docs/database/04-application-environment-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/database/14-search-projections.md`
* `docs/database/15-retention-archival.md`
* `docs/architecture/08-security-architecture.md`
* `docs/functional/01-organization-management.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/functional/16-public-api.md`
* `docs/security/02-tenant-isolation.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-008-security-policy-layer.md`
