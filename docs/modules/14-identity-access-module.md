# Identity and Access Module

## Purpose

The Identity and Access module owns authentication identities, Organization Memberships, Roles, permissions, credentials, Sessions, and access lifecycle.

It establishes who is acting.

Owning modules remain responsible for authorizing operations on their Resources.

---

## Owns

The module owns authoritative state for:

* User.
* Identity.
* Organization Membership.
* Role.
* Permission assignment.
* API Client.
* Service Identity.
* Session.
* authentication credential metadata.
* MFA enrollment.
* privileged-access session.

Agent and Plugin identities are registered through this module but remain associated with their owning Agent or Plugin modules.

---

## Responsibilities

The module is responsible for:

* Creating and managing identities.
* authenticating human and non-human actors.
* managing Organization Membership lifecycle.
* assigning Roles and permissions.
* issuing and revoking Sessions.
* managing credential metadata.
* exposing trusted identity references.
* resolving authentication strength.
* invalidating authorization caches after access changes.
* recording security-sensitive identity changes.

---

## Does Not Own

The module does not own:

* Organization lifecycle.
* Resource-specific authorization rules.
* Alert, Incident, Knowledge, or Workflow permissions in context.
* Plugin capability declarations.
* Agent execution Policy.
* external provider secrets.
* tenant-owned business Resources.

A permission grant does not independently authorize a domain operation.

---

## Core Resources

### Identity

Represents one authenticated actor.

Important attributes:

```text
id
type
status
display_name
created_at
disabled_at
```

Supported identity types include:

```text
user
api_client
service
agent
plugin
platform
```

### User

Represents a human account linked to one Identity.

### Membership

Connects a User identity to one Organization.

Important attributes:

```text
organization_id
identity_id
status
joined_at
suspended_at
removed_at
```

### Role

Groups stable permissions for assignment within an Organization.

### Session

Represents one revocable authenticated User session.

### Credential

Stores credential metadata and a secure hash or secret reference.

Plaintext credentials are never authoritative module state.

---

## Membership Lifecycle

Suggested Membership states:

```text
invited
active
suspended
removed
```

Only an `active` Membership grants normal Organization access.

Suspending or removing a Membership must invalidate affected access promptly.

---

## Identity Lifecycle

Suggested Identity states:

```text
active
suspended
disabled
compromised
```

Inactive identities must not authenticate or execute protected operations.

Historical attribution must remain available after deactivation.

---

## Commands

Primary commands include:

```text
RegisterUser
InviteOrganizationMember
AcceptOrganizationInvitation
SuspendMembership
RestoreMembership
RemoveMembership
CreateRole
UpdateRolePermissions
AssignRole
RevokeRole
CreateApiClient
RotateCredential
RevokeCredential
StartSession
RevokeSession
RevokeAllSessions
EnrollMfa
RemoveMfa
StartPrivilegedAccess
EndPrivilegedAccess
SuspendIdentity
```

Commands must preserve Organization scope and Audit requirements.

---

## Queries

Primary queries include:

```text
GetCurrentIdentity
GetIdentity
ListOrganizationMembers
GetMembership
ListRoles
GetRole
ListSessions
ListApiClients
GetAuthenticationContext
GetEffectivePermissionAssignments
```

Effective permission queries provide input to Policy evaluation but do not replace Resource-specific authorization.

---

## Published Events

The module publishes events such as:

```text
identity.created
identity.suspended
identity.compromised
membership.invited
membership.activated
membership.suspended
membership.removed
role.created
role.permissions_updated
role.assigned
role.revoked
credential.created
credential.rotated
credential.revoked
session.started
session.revoked
mfa.enrolled
mfa.removed
privileged_access.started
privileged_access.ended
```

Events must exclude credentials, tokens, and authentication secrets.

---

## Consumed Events

The module may consume:

```text
organization.created
organization.suspended
organization.archived
agent.activated
agent.suspended
plugin.installed
plugin.suspended
plugin.uninstalled
```

Examples:

* Creating an Organization may create an initial owner Membership.
* Suspending an Organization disables normal tenant access.
* Activating an Agent may register its Agent Identity.
* Uninstalling a Plugin revokes its Plugin Identity and credentials.

Consumption must remain idempotent.

---

## Authentication Contract

The module exposes a trusted Authentication Context containing:

```text
identity_id
identity_type
authentication_method
authentication_strength
session_id
credential_id
authenticated_at
```

This context proves identity only.

Organization access must still be resolved through Membership or the non-human identity’s Organization binding.

---

## Authorization Input

The module may provide:

* Active Membership state.
* assigned Roles.
* granted permissions.
* identity status.
* authentication strength.
* privileged-access scope.

The owning business module combines this information with:

* Tenant Context.
* Resource ownership.
* Resource state.
* Resource scope.
* operation-specific Policy.

---

## Permission Model

Permissions use stable capability names.

Examples:

```text
organization.member.manage
application.create
event.ingest
alert.acknowledge
incident.resolve
knowledge.publish
plugin.install
agent.execute
workflow.approval.decide
```

Roles group permissions.

Domain logic must not depend directly on Role names.

---

## Administrative Recovery Capability

Every active Organization must retain at least one active human Membership with effective `organization.member.manage`. This is evaluated from active Membership, non-revoked and unexpired Role assignment, and non-revoked permission grant; a Role name alone does not qualify.

Membership suspension or removal and Role-assignment revocation must serialize per Organization within their transaction, verify the post-mutation capability, and roll back when it would be lost. This protects administrative recovery only and does not define ownership transfer or a last-owner Role rule.

---

## Non-Human Identities

API Clients, Services, Agents, and Plugins must use dedicated identities.

Each non-human identity must have:

```text
organization_id
identity_type
status
granted_permissions
resource_scope
credential_binding
```

They must not inherit unrestricted permissions from their creator.

---

## Credential Boundary

The module owns credential lifecycle metadata.

Secret values are handled through approved hashing or secret-management adapters.

Credential records may contain:

```text
credential_type
secret_reference_or_hash
status
created_at
expires_at
rotated_at
revoked_at
last_used_at
```

Existing plaintext credentials must never be retrievable.

---

## Session Rules

Sessions must be:

* Cryptographically secure.
* revocable.
* time-limited.
* rotated after sensitive authentication changes.
* associated with one User identity.
* invalidated after relevant Membership or identity suspension.

Sensitive operations may require recent or stronger authentication.

---

## Privileged Access

Cross-tenant support or emergency access requires a dedicated privileged-access session.

It must include:

```text
acting_identity_id
purpose
approved_scope
target_organization_id
started_at
expires_at
approved_by
```

All privileged Actions must remain attributable and audited.

---

## Tenant Isolation

Memberships, Roles, assignments, API Clients, and non-human identities must remain Organization-scoped.

The module must reject:

* Cross-Organization Role assignments.
* Membership access to another Organization.
* credential reuse across Organizations.
* Agent or Plugin identity switching.
* tenant access based only on a route identifier.

Platform identities require explicit privileged scope.

---

## Repository Interfaces

The module owns repository contracts such as:

```text
IdentityRepository
UserRepository
MembershipRepository
RoleRepository
SessionRepository
CredentialRepository
PrivilegedAccessRepository
```

Other modules should use public identity and permission contracts rather than modifying these repositories directly.

---

## Policy Integration

The module provides identity facts to the central Policy layer.

It does not decide whether an Alert may be acknowledged or an Incident resolved.

Example:

```text
Identity and Access Module
→ confirms active identity, Membership, and permission

Alert Module
→ evaluates alert.acknowledge against Alert scope and state
```

---

## Cache Invalidation

Access-related changes must increment or invalidate an authorization Version.

Examples:

* Membership suspension.
* Role assignment.
* permission change.
* credential revocation.
* identity suspension.
* privileged-access expiration.

High-risk operations should evaluate current authoritative access state.

---

## Audit Requirements

Audit Records are required for:

* Membership changes.
* Role and permission changes.
* credential creation, rotation, and revocation.
* MFA changes.
* identity suspension.
* Session revocation.
* privileged-access lifecycle.
* administrative access overrides.

Secrets and raw tokens must never be included.

---

## Security Rules

The module must enforce:

* Deny by default.
* secure password hashing where passwords are supported.
* brute-force protection.
* Session revocation.
* MFA for privileged identities.
* credential expiration and rotation.
* non-disclosing authentication errors.
* separation of human and non-human identities.

---

## Testing Requirements

Tests must cover:

* Membership lifecycle.
* cross-Organization assignment rejection.
* inactive identity authentication.
* permission assignment and revocation.
* Session invalidation.
* credential rotation and revocation.
* MFA requirements.
* non-human identity isolation.
* privileged-access expiration.
* authorization cache invalidation.
* idempotent event handling.

---

## Prohibited Practices

The following are prohibited:

* Using Role names as domain authorization rules.
* treating authentication as authorization.
* sharing credentials between identities.
* inheriting creator permissions for Agents or Plugins.
* loading Memberships without Organization scope.
* storing plaintext passwords or tokens.
* allowing suspended identities to retain active Sessions.
* direct access-table mutation by other modules.
* permanent unscoped support access.
* placing Resource lifecycle Policy inside this module.

---

## Business Rules

* Every actor has one attributable Identity.
* human and non-human identities remain distinct.
* Organization access requires an Active Membership or explicit tenant-bound non-human identity.
* permissions are grouped through Roles but evaluated as capabilities.
* Resource-specific authorization remains owned by the relevant module.
* credentials and Sessions are revocable.
* access changes take effect promptly.
* privileged access is scoped, time-limited, and audited.
* secrets never appear in events, Logs, or API responses.
* identity history remains available after deactivation.

---

## Related Documents

* `docs/modules/00-module-architecture.md`
* `docs/modules/01-shared-kernel.md`
* `docs/modules/02-organization-module.md`
* `docs/modules/10-agent-module.md`
* `docs/modules/11-plugin-module.md`
* `docs/modules/18-laravel-runtime-adapter.md`
* `docs/database/02-identity-access-schema.md`
* `docs/api/02-authentication.md`
* `docs/security/03-authentication-security.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/security/05-secrets-credentials.md`
* `docs/adr/ADR-008-security-policy-layer.md`
