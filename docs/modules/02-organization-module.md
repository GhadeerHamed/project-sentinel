# Organization Module

## Purpose

The Organization module owns the lifecycle and authoritative configuration of Sentinel tenant Organizations.

An Organization is the primary tenant boundary.

---

## Owns

The module owns:

* Organization.
* Organization status.
* Organization profile.
* Organization settings.
* Organization plan reference.
* Organization lifecycle history.
* Organization-level operational limits.
* Organization-level feature configuration.

Memberships, Roles, identities, and Sessions are owned by the Identity and Access module.

---

## Responsibilities

The module is responsible for:

* Creating Organizations.
* maintaining Organization identity and profile.
* managing lifecycle transitions.
* defining Organization-wide settings.
* exposing trusted Organization status.
* enforcing Organization-level limits.
* publishing Organization lifecycle events.
* preventing operations against inactive Organizations.

---

## Does Not Own

The module does not own:

* Users or Memberships.
* Roles and permissions.
* Projects.
* Applications.
* billing transactions.
* domain Resources belonging to other modules.
* platform-wide administrative access.
* authentication.

It must not modify Resources owned by dependent modules directly.

---

## Organization Resource

Important attributes include:

```text
id
name
slug
status
plan_code
settings
created_at
updated_at
suspended_at
archived_at
version
```

The primary identifier uses UUIDv7.

The slug is human-friendly and must not be treated as an authorization boundary.

---

## Lifecycle

Suggested Organization states:

```text
active
suspended
archived
```

Possible transitions:

```text
active → suspended
suspended → active
active → archived
suspended → archived
```

Archived Organizations cannot return to normal operation without an explicit recovery policy.

---

## Active Organization

An Active Organization may:

* Authenticate authorized Members.
* create and manage tenant Resources.
* ingest Events.
* run Workflows.
* execute Agents and Plugins.
* receive Notifications.

All actions remain subject to permissions and plan limits.

---

## Suspended Organization

A Suspended Organization:

* Cannot perform normal tenant operations.
* must reject new ingestion and protected writes.
* may allow limited authorized administrative access.
* retains authoritative data.
* retains Audit and security history.
* may continue mandatory retention or security processing.

Suspension must not delete data automatically.

---

## Archived Organization

An Archived Organization:

* Is unavailable for normal operation.
* cannot create or modify tenant Resources.
* retains data according to retention and Legal Hold policies.
* may be accessed only through governed administrative procedures.
* must not appear as an active tenant.

Archival is a lifecycle transition, not immediate physical deletion.

---

## Commands

Primary commands include:

```text
CreateOrganization
RenameOrganization
ChangeOrganizationSlug
UpdateOrganizationProfile
UpdateOrganizationSettings
ChangeOrganizationPlan
SuspendOrganization
ReactivateOrganization
ArchiveOrganization
UpdateOrganizationLimits
```

Every command must preserve optimistic concurrency where applicable.

---

## Queries

Primary queries include:

```text
GetOrganization
GetOrganizationBySlug
GetOrganizationStatus
GetOrganizationSettings
GetOrganizationLimits
ListAccessibleOrganizations
```

`ListAccessibleOrganizations` requires identity information from the Identity and Access module.

---

## Published Events

The module publishes events such as:

```text
organization.created
organization.renamed
organization.settings_updated
organization.plan_changed
organization.suspended
organization.reactivated
organization.archived
organization.limits_updated
```

Events must include:

```text
organization_id
event_id
occurred_at
correlation_id
causation_id
event_version
```

---

## Consumed Events

The Organization module may consume platform-governed events such as:

```text
subscription.changed
subscription.payment_failed
subscription.terminated
platform.organization_restriction_requested
```

Billing-related events may affect plan or lifecycle state only through explicit Organization commands and policy.

External events must never mutate Organization state directly.

---

## Identity and Access Integration

After Organization creation, the Identity and Access module may create the initial owner Membership.

Preferred flow:

```text
CreateOrganization
↓
organization.created
↓
Create initial owner Membership
```

Organization creation and Membership creation may be coordinated synchronously when both must succeed for onboarding.

The Organization module does not write Membership tables.

---

## Plan and Limits

The module may expose Organization-level limits such as:

```text
member_limit
application_limit
event_ingestion_limit
storage_limit
agent_execution_limit
plugin_installation_limit
```

Limits are configuration inputs to owning modules.

Each module remains responsible for enforcing limits during its own operations.

---

## Settings

Organization settings may include:

* Locale.
* timezone.
* default retention preferences.
* default Notification preferences.
* approved integration settings.
* operational defaults.

Sensitive credentials must not be stored inside Organization settings.

---

## Feature Configuration

Organization-level feature availability may be controlled by:

* Product plan.
* rollout flags.
* explicit platform grants.
* temporary restrictions.

Feature availability does not replace authorization.

A feature being enabled does not grant permission to use it.

---

## Authorization

Typical permissions include:

```text
organization.read
organization.update
organization.settings.manage
organization.members.view
organization.plan.view
organization.suspend
organization.archive
```

High-risk lifecycle operations may require:

* Strong authentication.
* Organization Owner authority.
* platform approval.
* recent reauthentication.
* Audit recording.

---

## Tenant Boundary

Every tenant-owned Resource must reference one Organization.

The Organization module exposes trusted Organization existence and lifecycle state.

Other modules must reject operations when:

* The Organization does not exist.
* the Organization is suspended and the operation is not explicitly permitted.
* the Organization is archived.
* the Resource belongs to another Organization.

---

## Repository Interfaces

The module owns repository contracts such as:

```text
OrganizationRepository
OrganizationSettingsRepository
OrganizationLifecycleRepository
```

Other modules should use Organization contracts or status queries rather than modifying Organization persistence directly.

---

## Transactions

Organization lifecycle transitions must commit atomically with:

* Organization state.
* lifecycle history.
* required Audit metadata.
* Outbox Records.

Dependent module cleanup or suspension occurs asynchronously through published events.

---

## Suspension Propagation

When an Organization is suspended:

```text
organization.suspended
↓
Identity and Access blocks normal tenant access
Plugins stop normal execution
Agents stop normal execution
Workflows stop or pause according to policy
Event ingestion rejects new tenant traffic
Notifications restrict non-essential delivery
```

Consumers must process the event idempotently.

---

## Archival Propagation

Organization archival may trigger:

* Membership deactivation.
* credential revocation.
* Plugin suspension.
* Agent suspension.
* Workflow cancellation or archival.
* Search removal.
* retention evaluation.
* export or deletion workflows where required.

Each owning module performs its own state transition.

---

## Optimistic Concurrency

Organization updates should use a Resource Version.

Conflicting updates must return a concurrency conflict rather than silently overwrite newer settings.

This is especially important for:

* Lifecycle changes.
* settings.
* plan changes.
* operational limits.

---

## Audit Requirements

Audit Records are required for:

* Organization creation.
* profile and settings changes.
* plan changes.
* limit changes.
* suspension.
* reactivation.
* archival.
* privileged access to archived Organizations.

Audit Records should include safe before-and-after metadata where permitted.

---

## Testing Requirements

Tests must cover:

* Organization creation.
* unique slug handling.
* lifecycle transitions.
* invalid transition rejection.
* suspended Organization restrictions.
* archived Organization restrictions.
* optimistic concurrency.
* plan and limit changes.
* event publication.
* idempotent event consumption.
* cross-Organization access rejection.
* initial owner onboarding coordination.

---

## Prohibited Practices

The following are prohibited:

* Treating the slug as the tenant security boundary.
* deleting Organization data during suspension.
* allowing archived Organizations normal access.
* storing Memberships inside the Organization module.
* modifying dependent module tables during suspension.
* storing credentials in Organization settings.
* using feature flags as authorization.
* allowing lifecycle changes without Audit.
* silently overwriting concurrent settings changes.
* permitting normal operations when Organization status is unresolved.

---

## Business Rules

* Organization is Sentinel’s primary tenant boundary.
* every tenant-owned Resource belongs to one Organization.
* Organization lifecycle state affects all tenant capabilities.
* suspended Organizations retain data but cannot perform normal operations.
* archived Organizations are unavailable except through governed procedures.
* dependent modules react through explicit contracts or events.
* the Organization module does not own Memberships or permissions.
* plan and limit configuration is exposed centrally but enforced by owning modules.
* lifecycle transitions are versioned, auditable, and event-producing.
* Organization identifiers, not slugs, define authoritative ownership.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/01-shared-kernel.md`
* `docs/modules/14-identity-access-module.md`
* `docs/modules/03-project-module.md`
* `docs/database/03-organization-project-schema.md`
* `docs/api/06-organizations-api.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/adr/ADR-003-multi-tenancy.md`
