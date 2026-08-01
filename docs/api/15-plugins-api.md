# Plugins API

## Purpose

This document defines the API for discovering, installing, configuring, and operating Plugins.

Plugins integrate Sentinel with external systems while remaining tenant-scoped and capability-controlled.

---

## Resources

### Plugin Definition

A platform-supported integration.

```json
{
  "id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "type": "plugin_definition",
  "attributes": {
    "key": "github",
    "name": "GitHub",
    "plugin_type": "bidirectional",
    "status": "active",
    "latest_version": "1.4.0"
  }
}
```

### Plugin Installation

An Organization-specific installation.

```json
{
  "id": "0198f3ca-9e18-71ab-9d98-17f6d76d38a1",
  "type": "plugin_installation",
  "attributes": {
    "plugin_definition_id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
    "name": "Production GitHub",
    "installed_version": "1.4.0",
    "status": "active",
    "version": 3
  }
}
```

Installation statuses:

* `pending_configuration`
* `validating`
* `active`
* `degraded`
* `suspended`
* `failed`
* `archived`

---

## Endpoints

| Method  | Endpoint                                                                                 | Purpose                      |
| ------- | ---------------------------------------------------------------------------------------- | ---------------------------- |
| `GET`   | `/plugin-definitions`                                                                    | List available Plugins       |
| `GET`   | `/plugin-definitions/{plugin_definition_id}`                                             | Read Plugin details          |
| `GET`   | `/organizations/{organization_id}/plugin-installations`                                  | List installations           |
| `POST`  | `/organizations/{organization_id}/plugin-installations`                                  | Install a Plugin             |
| `GET`   | `/organizations/{organization_id}/plugin-installations/{installation_id}`                | Read an installation         |
| `PATCH` | `/organizations/{organization_id}/plugin-installations/{installation_id}`                | Update installation metadata |
| `POST`  | `/organizations/{organization_id}/plugin-installations/{installation_id}/configurations` | Create configuration Version |
| `POST`  | `/organizations/{organization_id}/plugin-installations/{installation_id}/activate`       | Activate installation        |
| `POST`  | `/organizations/{organization_id}/plugin-installations/{installation_id}/validate`       | Validate configuration       |
| `POST`  | `/organizations/{organization_id}/plugin-installations/{installation_id}/synchronize`    | Request synchronization      |
| `POST`  | `/organizations/{organization_id}/plugin-installations/{installation_id}/upgrade`        | Upgrade Plugin Version       |
| `POST`  | `/organizations/{organization_id}/plugin-installations/{installation_id}/suspend`        | Suspend installation         |
| `POST`  | `/organizations/{organization_id}/plugin-installations/{installation_id}/archive`        | Archive installation         |
| `GET`   | `/organizations/{organization_id}/plugin-executions`                                     | List executions              |
| `GET`   | `/organizations/{organization_id}/plugin-executions/{execution_id}`                      | Read execution               |

---

## List Plugin Definitions

```http
GET /api/v1/plugin-definitions
```

Supported filters:

* `plugin_type`
* `provider`
* `status`
* `capability`

Only Plugins available to the current platform deployment should be returned.

---

## Install Plugin

```http
POST /api/v1/organizations/{organization_id}/plugin-installations
Idempotency-Key: {key}
```

Request:

```json
{
  "plugin_definition_id": "0198f3b6-48ae-7a44-9192-6f8029df5f10",
  "plugin_version": "1.4.0",
  "name": "Production GitHub"
}
```

Response:

```http
201 Created
```

A new installation begins in `pending_configuration`.

Installation creates a dedicated Plugin Identity.

---

## Configure Installation

```http
POST /api/v1/organizations/{organization_id}/plugin-installations/{installation_id}/configurations
If-Match: "1"
Idempotency-Key: {key}
```

Request:

```json
{
  "configuration": {
    "repository_scope": [
      "acme/checkout-api"
    ],
    "sync_interval_minutes": 15
  },
  "capabilities": [
    "deployment.read",
    "event.ingest"
  ]
}
```

Configuration must not contain secret values.

Credentials are supplied through a dedicated secret-management flow and stored as references.

Configuration Versions are immutable after activation.

---

## Validate Installation

```http
POST /api/v1/organizations/{organization_id}/plugin-installations/{installation_id}/validate
Idempotency-Key: {key}
```

Response:

```http
202 Accepted
```

Validation may test:

* Configuration schema.
* credentials.
* provider connectivity.
* declared scopes.
* required capabilities.
* Plugin Version compatibility.

The operation result must not expose secret values.

---

## Activate Installation

```http
POST /api/v1/organizations/{organization_id}/plugin-installations/{installation_id}/activate
If-Match: "2"
```

Request:

```json
{
  "configuration_version_id": "0198f3d2-973d-7365-ad09-baf69f18258c"
}
```

Activation requires a valid configuration and approved capability grants.

---

## Synchronize Plugin

```http
POST /api/v1/organizations/{organization_id}/plugin-installations/{installation_id}/synchronize
Idempotency-Key: {key}
```

Optional request:

```json
{
  "sync_type": "incremental",
  "scope_key": "acme/checkout-api"
}
```

Response:

```http
202 Accepted
```

The response references a `plugin_execution`.

---

## Upgrade Plugin

```http
POST /api/v1/organizations/{organization_id}/plugin-installations/{installation_id}/upgrade
If-Match: "3"
Idempotency-Key: {key}
```

Request:

```json
{
  "plugin_version": "1.5.0"
}
```

Upgrade validation must check:

* Version compatibility.
* configuration compatibility.
* requested capability changes.
* migration requirements.
* rollback support.

Upgrades are explicit and never applied silently.

---

## Suspend Installation

```http
POST /api/v1/organizations/{organization_id}/plugin-installations/{installation_id}/suspend
If-Match: "4"
```

Request:

```json
{
  "reason": "Provider credentials are being reviewed."
}
```

Suspension prevents new normal executions while preserving configuration and history.

---

## Plugin Execution

```json
{
  "id": "0198f3d2-973d-7365-ad09-baf69f18258c",
  "type": "plugin_execution",
  "attributes": {
    "plugin_installation_id": "0198f3ca-9e18-71ab-9d98-17f6d76d38a1",
    "execution_type": "synchronization",
    "status": "completed",
    "requested_at": "2026-08-01T16:30:00Z",
    "completed_at": "2026-08-01T16:30:12Z"
  }
}
```

Execution statuses:

* `requested`
* `queued`
* `running`
* `completed`
* `failed`
* `rejected`
* `cancelled`
* `timed_out`

Raw provider payloads and credentials must not be returned by default.

---

## Capabilities

Plugins receive only explicitly approved capabilities.

Examples:

```text
event.ingest
incident.read
deployment.read
notification.send
workflow.trigger
```

A Plugin cannot:

* Grant itself capabilities.
* access another Organization.
* write directly to authoritative domain tables.
* perform protected Actions without normal Authorization.

---

## Authorization

Typical permissions:

```text
plugin.read
plugin.install
plugin.configure
plugin.activate
plugin.execute
plugin.upgrade
plugin.suspend
plugin.archive
plugin.execution.read
plugin.capability.grant
```

Capability grants and credential changes may require stronger administrative permissions.

---

## Errors

Common errors:

```text
plugin.not_found
plugin.version_not_found
plugin.installation_not_found
plugin.configuration_invalid
plugin.credentials_invalid
plugin.capability_not_granted
plugin.not_active
plugin.upgrade_incompatible
plugin.provider_unavailable
plugin.execution_failed
authorization.denied
concurrency.version_conflict
```

---

## Business Rules

* Every Plugin Installation belongs to one Organization.
* Every Installation has a distinct Plugin Identity.
* Plugin Versions are immutable.
* Configuration and credentials remain separate.
* Credentials are stored only as secret references.
* Capabilities must be explicitly granted.
* Plugins cannot bypass domain ownership or Authorization.
* Installation changes use optimistic concurrency.
* Retryable operations use idempotency.
* Upgrades are explicit.
* Suspended installations cannot begin normal executions.
* Cross-Organization access is prohibited.

---

## Related Documents

* `docs/api/14-agents-api.md`
* `docs/api/16-notifications-api.md`
* `docs/api/18-workflows-api.md`
* `docs/api/19-webhooks.md`
* `docs/database/10-plugin-schema.md`
* `docs/architecture/06-plugin-system.md`
* `docs/adr/ADR-002-plugin-architecture.md`
