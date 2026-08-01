# Plugin Module

## Purpose

The Plugin module owns Plugin definitions, Versions, Installations, capability grants, synchronization state, and execution history.

Plugins connect Sentinel to external systems without bypassing domain ownership or security boundaries.

---

## Owns

* Plugin Definition.
* Plugin Version.
* Plugin Installation.
* Installation status.
* capability grant.
* configuration metadata.
* credential reference.
* synchronization cursor.
* Plugin execution.
* external Resource mapping.

---

## Does Not Own

* External provider data.
* Events, Alerts, or Incidents.
* Notification delivery.
* Agent execution.
* provider secret values.
* Organization or Application lifecycle.

Plugins interact with owning modules through approved contracts.

---

## Core Resources

### Plugin Definition

Describes a supported integration type.

```text
id
name
provider
status
declared_capabilities
configuration_schema
```

### Plugin Version

Represents an immutable implementation Version.

```text
id
plugin_id
version
runtime_contract
released_at
status
```

### Plugin Installation

Represents one Organization-scoped installation.

```text
id
organization_id
plugin_version_id
status
configuration
credential_reference
installed_at
version
```

Primary identifiers use UUIDv7.

---

## Installation Lifecycle

Suggested states:

```text
pending
active
suspended
failed
uninstalled
```

Typical transitions:

```text
pending → active
pending → failed
active → suspended
suspended → active
active → uninstalled
suspended → uninstalled
```

Uninstalled Installations cannot execute normally.

---

## Commands

```text
RegisterPluginDefinition
PublishPluginVersion
InstallPlugin
UpdatePluginConfiguration
GrantPluginCapability
RevokePluginCapability
ActivatePluginInstallation
SuspendPluginInstallation
UninstallPlugin
StartPluginSynchronization
RetryPluginExecution
RotatePluginCredential
```

Commands require Tenant Context, authorization, and lifecycle validation.

---

## Queries

```text
GetPluginDefinition
ListPluginDefinitions
GetPluginVersion
GetPluginInstallation
ListPluginInstallations
GetPluginExecution
ListPluginExecutions
GetSynchronizationStatus
```

Queries must preserve Organization and sensitivity scope.

---

## Published Events

```text
plugin.registered
plugin.version_published
plugin.installed
plugin.activated
plugin.suspended
plugin.uninstalled
plugin.configuration_updated
plugin.capability_granted
plugin.capability_revoked
plugin.synchronization_started
plugin.synchronization_completed
plugin.synchronization_failed
```

Events must exclude secrets and unrestricted provider payloads.

---

## Consumed Events

The module may consume:

```text
organization.suspended
organization.archived
application.archived
environment.disabled
credential.revoked
```

These events may suspend or restrict Plugin execution.

Consumers must remain idempotent.

---

## Capability Model

Each Plugin Version declares required capabilities.

An Installation receives only explicitly approved capabilities.

Effective access is:

```text
Declared capability
∩ Organization grant
∩ Resource scope
∩ runtime Policy
```

Example capabilities:

```text
event.ingest
incident.read
notification.request
application.read
webhook.receive
```

Plugins cannot modify their own capability grants.

---

## Plugin Identity

Every Installation uses a distinct non-human identity.

The identity must be bound to:

```text
organization_id
plugin_installation_id
granted_permissions
resource_scope
status
```

The Identity and Access module manages identity and credential lifecycle.

---

## Configuration

Plugin configuration may include:

* Provider endpoint options.
* synchronization scope.
* mapping rules.
* enabled features.
* schedule.
* retry settings.

Configuration must not contain plaintext credentials.

Secrets use secure references.

---

## Synchronization

Synchronization should be:

* Incremental where possible.
* bounded.
* idempotent.
* resumable.
* rate-limited.
* observable.

A synchronization cursor belongs to one Installation and must never be shared across Organizations.

---

## External Resource Mapping

External provider Resources may map to Sentinel Resources.

A mapping should include:

```text
organization_id
plugin_installation_id
provider_resource_type
provider_resource_id
sentinel_resource_type
sentinel_resource_id
```

Mappings must preserve tenant ownership and provider attribution.

---

## Event Integration

Plugins may submit telemetry through the Event module.

Plugin data must still pass:

* Identity validation.
* capability checks.
* tenant validation.
* canonical normalization.
* schema validation.
* ingestion idempotency.

Plugins must not write Event tables directly.

---

## Domain Actions

Plugins may request approved Actions through owning modules.

Examples:

```text
Create Event
Request Notification
Attach external reference
Trigger Workflow
```

The owning module performs current authorization and business-rule validation.

---

## Provider Callbacks

Inbound callbacks must verify:

* Signature.
* payload integrity.
* timestamp freshness.
* replay protection.
* expected provider.
* Installation ownership.

Invalid callbacks must not create authoritative state.

---

## Execution

Every Plugin execution should record:

```text
organization_id
plugin_installation_id
plugin_version_id
execution_type
status
started_at
completed_at
correlation_id
safe_failure_class
```

The exact Plugin Version must remain attributable.

---

## Failure Handling

Transient failures may be retried with bounded backoff.

Permanent failures include:

* Invalid configuration.
* revoked credentials.
* capability denial.
* unsupported payload.
* tenant mismatch.
* provider authorization rejection.

Repeated failures may suspend the Installation or create an operational Alert.

---

## Upgrade

Plugin upgrades must:

1. Validate Version compatibility.
2. validate configuration migration.
3. preserve credential references.
4. preserve tenant scope.
5. update the active Version.
6. retain execution history.
7. support rollback where practical.

A Plugin Version is immutable after publication.

---

## Repository Interfaces

```text
PluginDefinitionRepository
PluginVersionRepository
PluginInstallationRepository
PluginExecutionRepository
PluginMappingRepository
PluginSynchronizationRepository
```

Other modules must not modify Plugin persistence directly.

---

## Authorization

Typical permissions:

```text
plugin.install
plugin.read
plugin.configure
plugin.capability.manage
plugin.execute
plugin.suspend
plugin.uninstall
```

Installation, capability changes, and credential changes require Audit recording.

---

## Tenant Isolation

Every Installation, mapping, execution, configuration, and credential reference belongs to one Organization.

Plugins must not:

* Switch Organization context.
* reuse another tenant’s credentials.
* access another Installation’s cursor.
* create cross-tenant mappings.
* share execution context across Organizations.

---

## Audit Requirements

Audit Records are required for:

* Installation.
* configuration changes.
* capability grants and revocations.
* credential-reference changes.
* activation and suspension.
* Version upgrades.
* manual execution.
* uninstallation.

Routine synchronization results remain in Plugin execution history.

---

## Prohibited Practices

The Plugin module must not:

* Store plaintext credentials.
* allow direct database access.
* let Plugins grant their own capabilities.
* trust provider payloads without validation.
* reuse identities across Installations.
* write another module’s tables.
* perform unlimited retries.
* mix tenant synchronization state.
* hide repeated execution failures.

---

## Business Rules

* Every Plugin Installation belongs to one Organization.
* every Installation uses a distinct identity and credential binding.
* Plugin Versions are immutable.
* capabilities are explicit and least-privileged.
* provider input is untrusted.
* Plugins interact with domain modules through approved contracts.
* synchronization is idempotent, bounded, and resumable.
* external mappings preserve provider and tenant attribution.
* unsafe or failing Installations can be suspended immediately.
* Plugin lifecycle and capability changes are auditable.

---

## Related Documents

* `docs/modules/06-event-module.md`
* `docs/modules/10-agent-module.md`
* `docs/modules/12-notification-module.md`
* `docs/modules/14-identity-access-module.md`
* `docs/modules/17-workflow-module.md`
* `docs/database/10-plugin-schema.md`
* `docs/api/15-plugins-api.md`
* `docs/security/05-secrets-credentials.md`
* `docs/security/07-agent-plugin-security.md`
* `docs/architecture/06-plugin-system.md`
