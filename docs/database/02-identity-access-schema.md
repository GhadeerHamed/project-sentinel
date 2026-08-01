# Identity and Access Schema

## Purpose

This document defines the database model for identity, Organization Membership, Roles, permissions, Sessions, and non-human identities.

The schema supports:

* Human Users.
* Organization Memberships.
* Teams.
* Role-Based Access Control.
* Resource-scoped access.
* API Clients.
* Service Identities.
* Agent Identities.
* Plugin Identities.
* Sessions.
* Multi-Factor Authentication.
* external identity providers.
* temporary and privileged access.

Authentication and Authorization remain responsible for access decisions.

The database stores identity and access state but does not replace runtime Policy evaluation.

---

## Design Principles

* A User may belong to multiple Organizations.
* Access is granted through Organization-specific Membership.
* Roles and permissions are scoped to one Organization unless platform-owned.
* Human and non-human identities remain distinct.
* Credentials are stored separately from identity records.
* Secret values are never stored as plaintext.
* Sessions and credentials are revocable.
* Access history remains attributable after Membership removal.
* Tenant-owned records include `organization_id`.
* Protected operations follow deny-by-default behavior.

---

## Schema Overview

```text
users
  └── organization_memberships
        ├── membership_roles
        ├── team_members
        └── temporary_access_grants

organizations
  ├── teams
  ├── roles
  │     └── role_permissions
  ├── api_clients
  ├── service_identities
  ├── agent_identities
  ├── plugin_identities
  └── identity_provider_connections

identities
  ├── sessions
  ├── credentials
  ├── mfa_methods
  └── identity_role_assignments
```

---

## Identity Model

Sentinel uses a shared identity model for authentication and attribution.

### `identities`

Represents any human or non-human actor.

| Column          | Type        | Rules       |
| --------------- | ----------- | ----------- |
| `id`            | UUID        | Primary key |
| `identity_type` | VARCHAR     | Required    |
| `status`        | VARCHAR     | Required    |
| `display_name`  | VARCHAR     | Required    |
| `created_at`    | TIMESTAMPTZ | Required    |
| `updated_at`    | TIMESTAMPTZ | Required    |
| `version`       | BIGINT      | Required    |

Supported `identity_type` values include:

* `user`
* `api_client`
* `service`
* `agent`
* `plugin`
* `platform`
* `support`
* `emergency`

Supported status values include:

* `pending`
* `active`
* `suspended`
* `revoked`
* `archived`

The `identities` table contains common identity information.

Type-specific details are stored in dedicated tables.

---

## Users

### `users`

Represents one human identity.

| Column                  | Type        | Rules                       |
| ----------------------- | ----------- | --------------------------- |
| `identity_id`           | UUID        | Primary key and foreign key |
| `email`                 | VARCHAR     | Required                    |
| `normalized_email`      | VARCHAR     | Required                    |
| `name`                  | VARCHAR     | Required                    |
| `locale`                | VARCHAR     | Optional                    |
| `timezone`              | VARCHAR     | Optional                    |
| `email_verified_at`     | TIMESTAMPTZ | Optional                    |
| `last_authenticated_at` | TIMESTAMPTZ | Optional                    |
| `created_at`            | TIMESTAMPTZ | Required                    |
| `updated_at`            | TIMESTAMPTZ | Required                    |

Constraints:

```text
UNIQUE (normalized_email)
```

A User is platform-wide.

Organization access is defined through Memberships.

---

## Organization Memberships

### `organization_memberships`

Connects a User to one Organization.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `user_identity_id`       | UUID        | Required    |
| `status`                 | VARCHAR     | Required    |
| `invited_by_identity_id` | UUID        | Optional    |
| `invited_at`             | TIMESTAMPTZ | Optional    |
| `activated_at`           | TIMESTAMPTZ | Optional    |
| `suspended_at`           | TIMESTAMPTZ | Optional    |
| `removed_at`             | TIMESTAMPTZ | Optional    |
| `expires_at`             | TIMESTAMPTZ | Optional    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `updated_at`             | TIMESTAMPTZ | Required    |
| `version`                | BIGINT      | Required    |

Supported status values:

* `invited`
* `pending`
* `active`
* `suspended`
* `removed`
* `declined`
* `expired`

Constraints:

```text
UNIQUE (organization_id, user_identity_id)
```

Only Active Memberships grant normal Organization access.

---

## Membership Invitations

### `membership_invitations`

Stores pending Organization invitations.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `email`                  | VARCHAR     | Required    |
| `normalized_email`       | VARCHAR     | Required    |
| `invited_by_identity_id` | UUID        | Required    |
| `token_hash`             | VARCHAR     | Required    |
| `status`                 | VARCHAR     | Required    |
| `expires_at`             | TIMESTAMPTZ | Required    |
| `accepted_at`            | TIMESTAMPTZ | Optional    |
| `revoked_at`             | TIMESTAMPTZ | Optional    |
| `created_at`             | TIMESTAMPTZ | Required    |

Invitation tokens must be stored as secure hashes.

An invitation may be:

* `pending`
* `accepted`
* `declined`
* `expired`
* `revoked`

---

## Teams

### `teams`

Represents an Organization-specific operational Team.

| Column            | Type        | Rules       |
| ----------------- | ----------- | ----------- |
| `id`              | UUID        | Primary key |
| `organization_id` | UUID        | Required    |
| `name`            | VARCHAR     | Required    |
| `slug`            | VARCHAR     | Required    |
| `description`     | TEXT        | Optional    |
| `status`          | VARCHAR     | Required    |
| `created_at`      | TIMESTAMPTZ | Required    |
| `updated_at`      | TIMESTAMPTZ | Required    |
| `version`         | BIGINT      | Required    |

Constraints:

```text
UNIQUE (organization_id, slug)
UNIQUE (organization_id, id)
```

---

### `team_members`

Connects an Organization Membership to a Team.

| Column            | Type        | Rules    |
| ----------------- | ----------- | -------- |
| `organization_id` | UUID        | Required |
| `team_id`         | UUID        | Required |
| `membership_id`   | UUID        | Required |
| `team_role`       | VARCHAR     | Optional |
| `joined_at`       | TIMESTAMPTZ | Required |
| `removed_at`      | TIMESTAMPTZ | Optional |

Primary key:

```text
PRIMARY KEY (organization_id, team_id, membership_id)
```

The Team and Membership must belong to the same Organization.

---

## Roles

### `roles`

Represents an Organization-specific or platform-provided Role.

| Column            | Type        | Rules                       |
| ----------------- | ----------- | --------------------------- |
| `id`              | UUID        | Primary key                 |
| `organization_id` | UUID        | Nullable for platform Roles |
| `name`            | VARCHAR     | Required                    |
| `slug`            | VARCHAR     | Required                    |
| `description`     | TEXT        | Optional                    |
| `is_system_role`  | BOOLEAN     | Required                    |
| `is_privileged`   | BOOLEAN     | Required                    |
| `status`          | VARCHAR     | Required                    |
| `created_at`      | TIMESTAMPTZ | Required                    |
| `updated_at`      | TIMESTAMPTZ | Required                    |
| `version`         | BIGINT      | Required                    |

Constraints for Organization Roles:

```text
UNIQUE (organization_id, slug)
```

Platform Roles must not contain Organization-specific configuration.

---

## Permissions

### `permissions`

Defines stable platform business permissions.

| Column        | Type        | Rules               |
| ------------- | ----------- | ------------------- |
| `id`          | UUID        | Primary key         |
| `code`        | VARCHAR     | Required and unique |
| `capability`  | VARCHAR     | Required            |
| `action`      | VARCHAR     | Required            |
| `description` | TEXT        | Required            |
| `risk_level`  | VARCHAR     | Required            |
| `created_at`  | TIMESTAMPTZ | Required            |

Examples:

```text
alert.view
alert.acknowledge
incident.manage
knowledge.publish
plugin.install
agent.execute
organization.manage_members
audit.view
```

Permissions are platform-defined.

Organizations combine them into Roles.

---

## Role Permissions

### `role_permissions`

Connects Roles to permissions.

| Column                   | Type        | Rules    |
| ------------------------ | ----------- | -------- |
| `role_id`                | UUID        | Required |
| `permission_id`          | UUID        | Required |
| `granted_at`             | TIMESTAMPTZ | Required |
| `granted_by_identity_id` | UUID        | Optional |

Primary key:

```text
PRIMARY KEY (role_id, permission_id)
```

A Role must not grant permissions prohibited by platform safety rules.

---

## Identity Role Assignments

### `identity_role_assignments`

Assigns a Role to an identity within one Organization.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `identity_id`             | UUID        | Required    |
| `role_id`                 | UUID        | Required    |
| `scope_type`              | VARCHAR     | Required    |
| `scope_id`                | UUID        | Optional    |
| `assigned_by_identity_id` | UUID        | Required    |
| `assigned_at`             | TIMESTAMPTZ | Required    |
| `expires_at`              | TIMESTAMPTZ | Optional    |
| `revoked_at`              | TIMESTAMPTZ | Optional    |
| `revoked_by_identity_id`  | UUID        | Optional    |

Possible scope values:

* `organization`
* `project`
* `application`
* `environment`
* `service`
* `resource`

Constraints:

```text
UNIQUE (
    organization_id,
    identity_id,
    role_id,
    scope_type,
    scope_id
)
```

Expired or Revoked assignments do not grant access.

---

## Resource Access Grants

### `resource_access_grants`

Stores explicit Resource-level access that cannot be represented cleanly through a normal Role assignment.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `id`                     | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `identity_id`            | UUID        | Required    |
| `resource_type`          | VARCHAR     | Required    |
| `resource_id`            | UUID        | Required    |
| `permission_id`          | UUID        | Required    |
| `granted_by_identity_id` | UUID        | Required    |
| `reason`                 | TEXT        | Required    |
| `granted_at`             | TIMESTAMPTZ | Required    |
| `expires_at`             | TIMESTAMPTZ | Optional    |
| `revoked_at`             | TIMESTAMPTZ | Optional    |

Explicit grants should be used sparingly.

Role-based access remains the default model.

---

## API Clients

### `api_clients`

Represents one Organization-specific programmatic consumer.

| Column                | Type        | Rules       |
| --------------------- | ----------- | ----------- |
| `identity_id`         | UUID        | Primary key |
| `organization_id`     | UUID        | Required    |
| `name`                | VARCHAR     | Required    |
| `description`         | TEXT        | Optional    |
| `owner_membership_id` | UUID        | Required    |
| `status`              | VARCHAR     | Required    |
| `last_used_at`        | TIMESTAMPTZ | Optional    |
| `expires_at`          | TIMESTAMPTZ | Optional    |
| `created_at`          | TIMESTAMPTZ | Required    |
| `updated_at`          | TIMESTAMPTZ | Required    |

Constraints:

```text
UNIQUE (organization_id, name)
```

Every API Client belongs to exactly one Organization.

---

## Service Identities

### `service_identities`

Represents an internal workload or background process.

| Column            | Type        | Rules                          |
| ----------------- | ----------- | ------------------------------ |
| `identity_id`     | UUID        | Primary key                    |
| `organization_id` | UUID        | Nullable for platform services |
| `name`            | VARCHAR     | Required                       |
| `service_type`    | VARCHAR     | Required                       |
| `owner`           | VARCHAR     | Required                       |
| `status`          | VARCHAR     | Required                       |
| `created_at`      | TIMESTAMPTZ | Required                       |
| `updated_at`      | TIMESTAMPTZ | Required                       |

Organization-specific Services must include `organization_id`.

Shared platform Services must not receive unrestricted tenant access.

---

## Agent Identities

### `agent_identities`

Represents the non-human identity used by an Agent Version.

| Column                | Type        | Rules       |
| --------------------- | ----------- | ----------- |
| `identity_id`         | UUID        | Primary key |
| `organization_id`     | UUID        | Required    |
| `agent_definition_id` | UUID        | Required    |
| `agent_version_id`    | UUID        | Required    |
| `status`              | VARCHAR     | Required    |
| `created_at`          | TIMESTAMPTZ | Required    |
| `revoked_at`          | TIMESTAMPTZ | Optional    |

Constraints:

```text
UNIQUE (organization_id, agent_version_id)
```

Agent permissions must be assigned to the Agent Identity, not inherited automatically from the requesting User.

---

## Plugin Identities

### `plugin_identities`

Represents the non-human identity used by a Plugin Installation.

| Column                   | Type        | Rules       |
| ------------------------ | ----------- | ----------- |
| `identity_id`            | UUID        | Primary key |
| `organization_id`        | UUID        | Required    |
| `plugin_installation_id` | UUID        | Required    |
| `plugin_version_id`      | UUID        | Required    |
| `status`                 | VARCHAR     | Required    |
| `created_at`             | TIMESTAMPTZ | Required    |
| `revoked_at`             | TIMESTAMPTZ | Optional    |

Constraints:

```text
UNIQUE (organization_id, plugin_installation_id)
```

Every Plugin Installation uses one distinct Plugin Identity.

---

## Credentials

### `identity_credentials`

Stores credential metadata and secure secret references.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `identity_id`       | UUID        | Required    |
| `organization_id`   | UUID        | Optional    |
| `credential_type`   | VARCHAR     | Required    |
| `secret_reference`  | VARCHAR     | Required    |
| `public_identifier` | VARCHAR     | Optional    |
| `status`            | VARCHAR     | Required    |
| `created_at`        | TIMESTAMPTZ | Required    |
| `expires_at`        | TIMESTAMPTZ | Optional    |
| `last_used_at`      | TIMESTAMPTZ | Optional    |
| `rotated_at`        | TIMESTAMPTZ | Optional    |
| `revoked_at`        | TIMESTAMPTZ | Optional    |

Supported credential types may include:

* `password`
* `api_key`
* `access_token`
* `client_certificate`
* `signed_assertion`
* `workload_credential`
* `oauth_refresh_token`

Actual secrets must be stored in an approved secret-management system.

---

## Sessions

### `sessions`

Represents an authenticated interaction.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `identity_id`             | UUID        | Required    |
| `organization_id`         | UUID        | Optional    |
| `session_token_hash`      | VARCHAR     | Required    |
| `authentication_method`   | VARCHAR     | Required    |
| `authentication_strength` | VARCHAR     | Required    |
| `status`                  | VARCHAR     | Required    |
| `created_at`              | TIMESTAMPTZ | Required    |
| `last_activity_at`        | TIMESTAMPTZ | Required    |
| `expires_at`              | TIMESTAMPTZ | Required    |
| `revoked_at`              | TIMESTAMPTZ | Optional    |
| `revocation_reason`       | VARCHAR     | Optional    |
| `ip_hash`                 | VARCHAR     | Optional    |
| `user_agent_hash`         | VARCHAR     | Optional    |

Constraints:

```text
UNIQUE (session_token_hash)
```

Raw Session tokens must never be stored.

---

## Multi-Factor Authentication

### `mfa_methods`

Stores MFA enrollment metadata.

| Column             | Type        | Rules       |
| ------------------ | ----------- | ----------- |
| `id`               | UUID        | Primary key |
| `user_identity_id` | UUID        | Required    |
| `method_type`      | VARCHAR     | Required    |
| `secret_reference` | VARCHAR     | Optional    |
| `display_name`     | VARCHAR     | Optional    |
| `status`           | VARCHAR     | Required    |
| `enrolled_at`      | TIMESTAMPTZ | Required    |
| `verified_at`      | TIMESTAMPTZ | Optional    |
| `last_used_at`     | TIMESTAMPTZ | Optional    |
| `revoked_at`       | TIMESTAMPTZ | Optional    |

Supported methods may include:

* `totp`
* `passkey`
* `security_key`
* `recovery_code`
* `external_provider`

Secret material must not be stored directly.

---

### `mfa_recovery_codes`

Stores hashed recovery codes.

| Column             | Type        | Rules       |
| ------------------ | ----------- | ----------- |
| `id`               | UUID        | Primary key |
| `user_identity_id` | UUID        | Required    |
| `code_hash`        | VARCHAR     | Required    |
| `created_at`       | TIMESTAMPTZ | Required    |
| `used_at`          | TIMESTAMPTZ | Optional    |
| `revoked_at`       | TIMESTAMPTZ | Optional    |

Each recovery code may be used once.

---

## External Identity Providers

### `identity_provider_connections`

Defines an Organization-specific SSO connection.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `provider_type`     | VARCHAR     | Required    |
| `name`              | VARCHAR     | Required    |
| `issuer`            | VARCHAR     | Required    |
| `client_identifier` | VARCHAR     | Required    |
| `secret_reference`  | VARCHAR     | Optional    |
| `status`            | VARCHAR     | Required    |
| `configuration`     | JSONB       | Required    |
| `created_at`        | TIMESTAMPTZ | Required    |
| `updated_at`        | TIMESTAMPTZ | Required    |
| `version`           | BIGINT      | Required    |

Possible provider types:

* `oidc`
* `saml`
* `directory`

Sensitive provider configuration must use secret references.

---

### `external_identity_links`

Connects a User with an external provider identity.

| Column                  | Type        | Rules       |
| ----------------------- | ----------- | ----------- |
| `id`                    | UUID        | Primary key |
| `organization_id`       | UUID        | Required    |
| `connection_id`         | UUID        | Required    |
| `user_identity_id`      | UUID        | Required    |
| `external_subject`      | VARCHAR     | Required    |
| `external_email`        | VARCHAR     | Optional    |
| `linked_at`             | TIMESTAMPTZ | Required    |
| `last_authenticated_at` | TIMESTAMPTZ | Optional    |
| `revoked_at`            | TIMESTAMPTZ | Optional    |

Constraints:

```text
UNIQUE (organization_id, connection_id, external_subject)
```

---

## Temporary Access

### `temporary_access_grants`

Represents time-bounded access for a specific purpose.

| Column                     | Type        | Rules       |
| -------------------------- | ----------- | ----------- |
| `id`                       | UUID        | Primary key |
| `organization_id`          | UUID        | Required    |
| `identity_id`              | UUID        | Required    |
| `role_id`                  | UUID        | Optional    |
| `permission_id`            | UUID        | Optional    |
| `scope_type`               | VARCHAR     | Required    |
| `scope_id`                 | UUID        | Optional    |
| `reason`                   | TEXT        | Required    |
| `requested_by_identity_id` | UUID        | Required    |
| `approved_by_identity_id`  | UUID        | Optional    |
| `starts_at`                | TIMESTAMPTZ | Required    |
| `expires_at`               | TIMESTAMPTZ | Required    |
| `revoked_at`               | TIMESTAMPTZ | Optional    |
| `created_at`               | TIMESTAMPTZ | Required    |

A Grant must contain either:

* `role_id`, or
* `permission_id`.

Temporary Access must expire automatically.

---

## Emergency and Support Access

### `privileged_access_sessions`

Records controlled support or emergency access.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `identity_id`             | UUID        | Required    |
| `access_type`             | VARCHAR     | Required    |
| `reason`                  | TEXT        | Required    |
| `approved_by_identity_id` | UUID        | Optional    |
| `scope`                   | JSONB       | Required    |
| `started_at`              | TIMESTAMPTZ | Required    |
| `expires_at`              | TIMESTAMPTZ | Required    |
| `ended_at`                | TIMESTAMPTZ | Optional    |
| `status`                  | VARCHAR     | Required    |

Supported access types:

* `support`
* `emergency`
* `security_response`
* `recovery`

Privileged access must be time-bounded and auditable.

---

## Access Review

### `access_reviews`

Represents a periodic Organization access review.

| Column              | Type        | Rules       |
| ------------------- | ----------- | ----------- |
| `id`                | UUID        | Primary key |
| `organization_id`   | UUID        | Required    |
| `review_type`       | VARCHAR     | Required    |
| `status`            | VARCHAR     | Required    |
| `owner_identity_id` | UUID        | Required    |
| `started_at`        | TIMESTAMPTZ | Required    |
| `due_at`            | TIMESTAMPTZ | Required    |
| `completed_at`      | TIMESTAMPTZ | Optional    |

---

### `access_review_items`

Represents one reviewed access assignment.

| Column                    | Type        | Rules       |
| ------------------------- | ----------- | ----------- |
| `id`                      | UUID        | Primary key |
| `organization_id`         | UUID        | Required    |
| `access_review_id`        | UUID        | Required    |
| `identity_id`             | UUID        | Required    |
| `assignment_type`         | VARCHAR     | Required    |
| `assignment_id`           | UUID        | Required    |
| `decision`                | VARCHAR     | Optional    |
| `reviewed_by_identity_id` | UUID        | Optional    |
| `reviewed_at`             | TIMESTAMPTZ | Optional    |
| `comment`                 | TEXT        | Optional    |

Possible decisions:

* `retain`
* `reduce`
* `revoke`
* `recertify`

---

## Authorization Versioning

Access-related changes should increment an Organization Authorization Version.

### `organization_authorization_versions`

| Column            | Type        | Rules       |
| ----------------- | ----------- | ----------- |
| `organization_id` | UUID        | Primary key |
| `version`         | BIGINT      | Required    |
| `updated_at`      | TIMESTAMPTZ | Required    |

The Version should increase when changes occur to:

* Membership status.
* Team membership.
* Role assignments.
* Role permissions.
* temporary access.
* Resource Access Grants.
* identity suspension.
* Policy-controlled access configuration.

Authorization caches may include this Version in their cache key.

---

## Tenant Isolation

All Organization-owned identity and access tables must include `organization_id`.

Tenant-aware relationships should prevent:

* Assigning a Role from another Organization.
* assigning a Membership to another Organization’s Team.
* granting access to another Organization’s Resource.
* linking an Agent or Plugin Identity across Organizations.
* using another Organization’s identity provider connection.

Cross-Organization access records are prohibited.

---

## Required Indexes

Recommended indexes include:

```text
organization_memberships (organization_id, status)
organization_memberships (user_identity_id, status)

teams (organization_id, status)
team_members (organization_id, membership_id)

roles (organization_id, status)
identity_role_assignments (organization_id, identity_id)
identity_role_assignments (organization_id, role_id)
identity_role_assignments (organization_id, expires_at)

resource_access_grants (organization_id, identity_id)
resource_access_grants (organization_id, resource_type, resource_id)

api_clients (organization_id, status)
identity_credentials (identity_id, status)
identity_credentials (organization_id, status, expires_at)

sessions (identity_id, status)
sessions (organization_id, status, expires_at)

temporary_access_grants (organization_id, identity_id, expires_at)
privileged_access_sessions (organization_id, status, expires_at)

external_identity_links (organization_id, connection_id, external_subject)
```

Indexes should support actual authorization and administration queries.

---

## Deletion and Retention

Identity and access records should normally use lifecycle states rather than physical deletion.

Records that must remain traceable include:

* Membership history.
* Role assignments.
* Temporary Access.
* privileged access.
* credential metadata.
* Session revocation.
* access-review decisions.

Physical deletion may apply to:

* Expired invitation tokens.
* expired Session data after required retention.
* unused MFA recovery codes.
* temporary security data after retention expires.

Historical attribution must remain intact.

---

## Security Requirements

* Passwords and tokens are stored only as secure hashes or secret references.
* Secret values must not appear in Logs, events, or API responses.
* Session tokens must be revocable.
* MFA removal requires strong authorization.
* Privileged access must be time-bounded.
* Membership removal must revoke active Sessions.
* Suspended identities cannot authenticate normally.
* Non-human identities must not use human credentials.
* Authorization data must not be cached without invalidation controls.

---

## Business Rules

* A User may belong to multiple Organizations.
* A User may have only one Membership record per Organization.
* Only Active Memberships grant normal access.
* Every Organization-specific Role belongs to one Organization.
* Permissions are stable platform-defined business operations.
* Role assignment does not bypass Resource Scope.
* Expired or Revoked assignments do not grant access.
* Human and non-human identities remain distinct.
* Every API Client belongs to one Organization.
* Every Agent Version uses a distinct Agent Identity.
* Every Plugin Installation uses a distinct Plugin Identity.
* Credentials belong to one identity.
* Secret values are never stored directly.
* Session tokens are stored only as hashes.
* Temporary Access expires automatically.
* Privileged access is purpose-limited and auditable.
* Access history remains available after identity removal.
* Cross-Organization assignments are prohibited.
* Missing or conflicting Organization ownership results in rejection.
* The database stores access state; runtime authorization makes the final access decision.

---

## Success Criteria

The identity and access schema is successful when:

* Human and non-human actors remain attributable.
* Users can belong to multiple Organizations safely.
* Organization access is controlled through Membership.
* Roles and permissions remain understandable.
* Resource-scoped access can be represented.
* credentials and Sessions can be revoked.
* temporary and privileged access expire safely.
* SSO and MFA can be supported.
* Agent and Plugin identities remain independently governed.
* access reviews can be performed.
* authorization caches can be invalidated reliably.
* historical actions remain attributable.
* tenant isolation is preserved.

---

## Related Documents

* `docs/database/00-data-architecture.md`
* `docs/database/01-tenant-data-isolation.md`
* `docs/database/03-organization-project-schema.md`
* `docs/database/09-agent-schema.md`
* `docs/database/10-plugin-schema.md`
* `docs/database/13-audit-schema.md`
* `docs/functional/01-organization-management.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/architecture/08-security-architecture.md`
* `docs/security/03-authentication-security.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/security/05-secrets-credentials.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-008-security-policy-layer.md`
