# Authentication and Authorization

## Purpose

Authentication and Authorization protect Project Sentinel by establishing identity and controlling access to protected Resources and operations.

Authentication answers:

> Who or what is accessing Sentinel?

Authorization answers:

> Is that identity allowed to perform this operation on this Resource?

The capability must protect:

* Organization isolation.
* User access.
* API access.
* Agent access.
* Plugin access.
* Service access.
* sensitive Resources.
* privileged operations.
* administrative operations.

Authentication does not grant access by itself.

Every protected operation must also be authorized.

---

## Objectives

Authentication and Authorization enable Sentinel to:

* Authenticate human and non-human identities.
* Manage Organization membership.
* manage Sessions and credentials.
* enforce Role-Based Access Control.
* enforce Resource Scope.
* enforce least privilege.
* protect sensitive data.
* support Multi-Factor Authentication.
* support enterprise Single Sign-On.
* support API Clients and Service Identities.
* govern Agent and Plugin identities.
* revoke access quickly.
* support temporary and emergency access.
* preserve access history.
* prevent privilege escalation.
* enforce deny-by-default access.

---

## Responsibilities

The capability is responsible for:

* Managing User identities.
* managing Organization Memberships.
* authenticating identities.
* managing Sessions.
* managing authentication methods.
* managing Multi-Factor Authentication.
* integrating external identity providers.
* managing Roles.
* managing permissions.
* managing Resource Scope.
* managing API credentials.
* managing Service Identities.
* managing Agent and Plugin identities.
* evaluating Authorization Requests.
* managing temporary access.
* managing access revocation.
* publishing identity and access events.
* preserving authentication and authorization history.

---

## Functional Boundaries

Authentication and Authorization do not:

* Own Organization business settings.
* own Projects, Applications, Alerts, Incidents, or Knowledge.
* define domain lifecycle rules.
* bypass Organization Policies.
* grant access because a Resource identifier is known.
* allow identities to increase their own privileges.
* permit cross-Organization access by default.
* replace external identity providers.
* trust external identity claims without validation.
* modify historical audit records.

The owning capability defines the protected operation.

Authentication and Authorization decide whether the requesting identity may perform it.

---

## Position in the Platform

```text
Identity Presents Authentication Evidence

↓

Authentication Validated

↓

Identity Established

↓

Organization Context Resolved

↓

Roles, Permissions, and Scope Resolved

↓

Policies Evaluated

↓

Operation Allowed or Denied
```

---

## Core Concepts

| Concept                | Description                                        |
| ---------------------- | -------------------------------------------------- |
| Identity               | A human or non-human actor recognized by Sentinel  |
| User                   | A human identity                                   |
| Membership             | A User’s relationship with an Organization         |
| Session                | A time-bounded authenticated interaction           |
| Authentication Method  | A mechanism used to prove identity                 |
| Role                   | A named collection of permissions                  |
| Permission             | Authority to perform a business operation          |
| Resource Scope         | The Resources to which access applies              |
| API Client             | A programmatic consumer identity                   |
| Service Identity       | A non-human workload identity                      |
| Agent Identity         | The identity used by an Agent                      |
| Plugin Identity        | The identity used by a Plugin Installation         |
| Authorization Decision | The result of evaluating an access request         |
| Temporary Access       | Time-bounded access granted for a specific purpose |
| Privileged Access      | Access to sensitive or high-impact operations      |

---

## Identity Types

Sentinel may support:

* User Identities.
* API Client Identities.
* Service Identities.
* Agent Identities.
* Plugin Identities.
* Integration Identities.
* Platform Identities.
* Support Identities.
* Emergency Identities.

Every identity must remain distinct and attributable.

Non-human identities must not use human credentials.

---

## User Identity

A User represents one human identity across Sentinel.

A User may belong to multiple Organizations.

Organization-specific access is represented through Membership.

This allows the same User to have different Roles and permissions in different Organizations.

---

## Organization Membership

Membership connects a User to one Organization.

A Membership should define:

* User.
* Organization.
* status.
* assigned Roles.
* Team membership.
* invitation details.
* activation time.
* expiration where applicable.
* suspension state.
* removal time.

A User has no Organization access without an active Membership or another explicitly governed access model.

---

## Membership Lifecycle

```text
Invited

↓

Pending

↓

Active

↓

Suspended

↓

Removed
```

A Membership may also become:

* Declined.
* Expired.

Only Active Memberships grant normal Organization access.

---

## Invitations

An Organization invitation should define:

* Intended recipient.
* Organization.
* proposed Roles.
* inviter.
* expiration.
* required authentication method.
* required security conditions.

Invitations must be:

* Time-bounded.
* single-purpose.
* attributable.
* revocable.

Accepting an invitation must not grant permissions beyond the invitation and current Organization Policy.

---

## Active Membership

An Active Membership permits access according to:

* Assigned Roles.
* permissions.
* Team membership.
* Resource Scope.
* authentication strength.
* Organization Policies.

Active Membership does not imply unrestricted Organization access.

---

## Suspended Membership

A Membership may be Suspended because of:

* Security concern.
* policy violation.
* compromised credentials.
* administrative review.
* inactive employment or contract status.
* failed security requirements.

Suspension should revoke active Organization access and relevant Sessions.

Historical activity remains attributable to the Member.

---

## Removed Membership

Removing a Membership:

* Revokes future Organization access.
* revokes active Organization Sessions.
* preserves historical attribution.
* preserves audit records.
* may require ownership reassignment.

Removal must not delete historical actions performed by the Member.

---

## Authentication

Authentication validates evidence proving identity.

Supported methods may include:

* Password.
* Passkey.
* Security key.
* authenticator application.
* one-time code.
* Single Sign-On.
* API Key.
* client certificate.
* signed request.
* workload identity.
* short-lived Service credential.

Available methods may depend on Identity Type and Organization Policy.

---

## Authentication Rules

* Credentials must be validated securely.
* authentication failure must not reveal whether an account exists.
* revoked or expired credentials must be rejected.
* credentials must be scoped to their intended identity.
* stronger authentication may be required for privileged operations.
* successful authentication does not bypass authorization.
* authentication activity must remain auditable.

---

## Multi-Factor Authentication

Multi-Factor Authentication may be required:

* For all Organization Members.
* for privileged Roles.
* for Production access.
* for sensitive Resources.
* before high-impact operations.
* after suspicious activity.

MFA enrollment and removal are sensitive operations.

They should require authentication and produce an audit record.

---

## Single Sign-On

Organizations may integrate an external identity provider using an approved federation protocol.

Single Sign-On may support:

* Centralized authentication.
* domain-based access.
* group-to-Role mapping.
* Team mapping.
* managed deprovisioning.
* stronger enterprise access policies.

External authentication does not replace Sentinel authorization.

---

## Identity Provider Validation

External identity assertions must be validated for:

* Signature.
* issuer.
* audience.
* expiration.
* subject.
* Organization.
* replay protection.
* required claims.
* authentication strength where available.

Invalid or ambiguous assertions must be rejected.

---

## Session Management

A Session represents one authenticated interaction.

A Session should record:

* Identity.
* Organization context.
* authentication method.
* authentication strength.
* creation time.
* last activity.
* expiration.
* lifecycle status.
* revocation state.

Sessions must be time-bounded and revocable.

---

## Session Lifecycle

```text
Created

↓

Active

↓

Expired
```

A Session may also become:

* Revoked.
* Restricted.
* Terminated.

---

## Session Expiration

Session lifetime may depend on:

* Identity Type.
* Role privilege.
* authentication method.
* inactivity.
* Organization Policy.
* risk.
* accessed Environment.

Privileged Sessions should have shorter lifetimes where appropriate.

---

## Session Revocation

Sessions may be revoked because of:

* Logout.
* Membership suspension or removal.
* credential change.
* MFA change.
* Role change.
* security concern.
* administrative action.
* Organization suspension.
* suspicious activity.

Revocation should take effect quickly across protected capabilities.

---

## Re-Authentication

Sensitive operations may require recent re-authentication.

Examples include:

* Role administration.
* API credential creation.
* Organization ownership transfer.
* SSO configuration changes.
* disabling MFA.
* sensitive data export.
* production automation approval.
* emergency access activation.

Re-authentication does not replace permission checks.

---

## Roles

A Role is a named collection of permissions.

Example Roles may include:

* Organization Owner.
* Administrator.
* Engineer.
* Viewer.
* Incident Commander.
* Security Responder.
* Knowledge Reviewer.
* Plugin Administrator.
* Agent Administrator.
* Billing Administrator.

Roles may be platform-defined or Organization-defined where supported.

---

## Role Definition

A Role should define:

* Name.
* Organization.
* description.
* permissions.
* assignable Identity Types.
* Resource Scope.
* privileged status.
* lifecycle status.
* owner.

Role changes are auditable.

---

## Role Assignment

Roles may be assigned to:

* Members.
* API Clients.
* Service Identities.
* Agent Identities.
* Plugin Identities.
* Integration Identities.

Role assignment must be authorized.

An identity must not assign a Role beyond its own delegation authority.

---

## Permissions

Permissions represent stable business operations.

Examples include:

* View Application.
* manage Environment.
* view Event.
* acknowledge Alert.
* manage Incident.
* publish Knowledge.
* execute Agent.
* install Plugin.
* manage Notification Channel.
* search sensitive Resources.
* manage Members.
* view audit records.

Permissions should describe business capability, not implementation detail.

---

## Resource Scope

Resource Scope limits where a permission applies.

Scope may include:

* Entire Organization.
* selected Projects.
* selected Applications.
* selected Environments.
* selected Services.
* selected Plugin Installations.
* selected Agents.
* selected Resource Types.
* selected sensitivity levels.

Permission and Resource Scope must both match.

---

## Authorization Request

An Authorization Request should include:

* Identity.
* Organization.
* operation.
* target Resource.
* Resource Type.
* Resource ownership.
* Resource Scope.
* authentication strength.
* Roles.
* permissions.
* applicable Policies.
* request context.

Missing authorization information results in denial.

---

## Authorization Decision

An Authorization Decision may be:

| Decision                  | Meaning                                      |
| ------------------------- | -------------------------------------------- |
| Allow                     | The operation may proceed                    |
| Deny                      | The operation must not proceed               |
| Require Approval          | Another approval is required                 |
| Require Re-Authentication | Stronger or newer authentication is required |
| Restrict                  | The operation may proceed with reduced scope |
| Challenge                 | Additional identity verification is required |

The owning capability must enforce the result.

---

## Authorization Flow

```text
Protected Operation Requested

↓

Identity Validated

↓

Organization Ownership Verified

↓

Roles and Permissions Resolved

↓

Resource Scope Evaluated

↓

Authentication Strength Evaluated

↓

Policies Evaluated

↓

Decision Returned
```

---

## Deny by Default

Protected operations follow deny-by-default behavior.

Access is allowed only when:

* Identity is valid.
* Organization relationship is valid.
* required permission exists.
* Resource Scope matches.
* authentication requirements are satisfied.
* applicable Policies permit the operation.
* the owning capability accepts the operation.

An absent rule or incomplete evaluation must not result in Allow.

---

## Authorization Precedence

Authorization should follow this precedence:

```text
Platform Safety Restrictions

↓

Organization Isolation

↓

Explicit Denials

↓

Resource Ownership and Scope

↓

Authentication Requirements

↓

Organization Policies

↓

Roles and Permissions

↓

Temporary Grants
```

A lower-level grant cannot override platform safety or tenant isolation.

---

## Field-Level Authorization

An identity may be allowed to access a Resource while selected fields remain restricted.

Protected fields may include:

* Security evidence.
* customer information.
* personal data.
* billing information.
* confidential Incident notes.
* infrastructure details.
* Agent context.
* audit details.

The owning capability should return only authorized fields.

---

## Action-Level Authorization

Different actions on one Resource require separate permissions.

For example:

* View Incident.
* update Incident.
* assign Incident.
* change severity.
* resolve Incident.
* close Incident.
* export Incident.
* view restricted evidence.

Read access does not imply update or lifecycle-transition access.

---

## Environment-Based Access

Authorization may differ by Environment.

Examples include:

* Read access in all Environments.
* write access only in Development.
* Production access restricted to selected Teams.
* Production changes requiring approval.
* sensitive Production evidence requiring stronger authentication.

Environment context must be evaluated where relevant.

---

## Sensitive Access

Resources may use sensitivity classifications such as:

* Internal.
* Confidential.
* Restricted.
* Security-Sensitive.
* Regulated.

Sensitive access may require:

* Additional permission.
* stronger authentication.
* explicit purpose.
* narrower Resource Scope.
* approval.
* enhanced audit.

---

## API Clients

An API Client represents a programmatic consumer.

Every Organization-specific API Client belongs to one Organization.

An API Client should define:

* Name.
* owner.
* purpose.
* credentials.
* API Scopes.
* Resource Scope.
* lifecycle status.
* expiration or review requirement.

API Clients must remain independently attributable.

---

## API Credentials

API credentials may include:

* API Keys.
* Access Tokens.
* client certificates.
* signed assertions.
* workload credentials.

Credentials must be:

* Protected.
* scoped.
* revocable.
* auditable.
* rotatable.
* excluded from Logs.

Secret values should not be retrievable after creation where one-time disclosure is supported.

---

## MVP Event Source Issuance

Issuing an Event Source requires the effective Organization permission `event_source.create`. The caller must be a session-authenticated Human User with an active Membership in the resolved Tenant Context; Membership alone does not grant issuance. Authorization evaluates effective permissions, not the `organization_owner` Role name.

The built-in `organization_owner` Role receives `event_source.create` through the same Core-permission provisioning and backfill path as other built-in permissions. The issued `api_client` is a separately authenticated non-human identity with only `event.ingest` for its own active Organization → Project → Application → Environment scope; it does not inherit the issuing User's permissions.

---

## Service Identities

Service Identities represent background processes and workloads.

Examples include:

* Event processors.
* Notification workers.
* scheduled jobs.
* ingestion services.
* internal platform services.

Service Identities must:

* Use non-human credentials.
* have explicit permissions.
* have explicit Resource Scope.
* remain attributable.
* be revocable.
* avoid shared credentials.

---

## Agent Identities

Every Agent uses a distinct Agent Identity.

Agent Identity defines:

* Organization.
* Agent and Version.
* permissions.
* Resource Scope.
* Tool access.
* sensitivity access.
* applicable Policies.

Agents must not inherit unrestricted permissions from the User who requested execution.

---

## Plugin Identities

Every Plugin Installation uses a distinct Plugin Identity.

Plugin Identity defines:

* Organization.
* Plugin Installation.
* Plugin Version.
* declared capabilities.
* permissions.
* Resource Scope.
* Environment Scope.
* applicable Policies.

Installing a Plugin does not grant unrestricted access.

---

## Temporary Access

Temporary Access grants limited authority for a defined period.

It may support:

* Incident responders.
* contractors.
* auditors.
* support engineers.
* migration specialists.

A Temporary Access Grant should define:

* Identity.
* permissions.
* Resource Scope.
* purpose.
* approver.
* start time.
* expiration.
* authentication requirements.

Temporary Access must expire automatically.

---

## Privileged Access

Privileged Access includes operations such as:

* Role administration.
* security configuration.
* SSO configuration.
* API credential administration.
* sensitive export.
* production automation.
* emergency controls.
* audit access.
* Organization ownership.

Privileged Access may require:

* Strong MFA.
* re-authentication.
* approval.
* separation of duties.
* shorter Sessions.
* mandatory reason.
* enhanced audit.

---

## Emergency Access

Emergency Access provides temporary authority during urgent operational conditions.

Emergency Access should require:

* Explicit reason.
* limited scope.
* limited duration.
* strongest available authentication.
* automatic expiration.
* immediate audit.
* notification.
* post-use review.

Emergency Access must not disable authorization or auditability.

---

## Support Access

Platform support access may be granted for troubleshooting or recovery.

Support Access must be:

* Organization-scoped.
* time-bounded.
* purpose-limited.
* approved where required.
* strongly authenticated.
* auditable.
* revocable.

Support identities must remain distinct from customer Members.

---

## Separation of Duties

Organization Policies may prevent one identity from controlling incompatible actions.

Examples include:

* Requesting and approving the same privileged grant.
* authoring and publishing high-risk Knowledge.
* proposing and approving critical automation.
* creating and approving a Plugin installation.
* initiating and approving ownership transfer.

Separation-of-duties conflicts must result in denial or additional review.

---

## Delegation

An identity may delegate limited authority where Policy permits.

Delegation should define:

* Delegating identity.
* receiving identity.
* permissions.
* Resource Scope.
* purpose.
* start time.
* expiration.
* approval.
* whether further delegation is prohibited.

Delegated authority must not exceed the delegating identity’s own authority.

---

## Access Revocation

Access may be revoked from:

* Memberships.
* Roles.
* Temporary Access Grants.
* Sessions.
* API credentials.
* Service Identities.
* Agent Identities.
* Plugin Identities.

Revocation may be triggered by:

* Membership removal.
* Role removal.
* credential compromise.
* Policy change.
* security event.
* expiration.
* administrative action.
* Organization suspension.

Revocation should take effect according to the risk of continued access.

---

## Deprovisioning

Deprovisioning should include:

* Membership suspension or removal.
* Session revocation.
* credential revocation.
* Temporary Access removal.
* ownership reassignment.
* pending approval reassignment.
* Team membership removal.

Deprovisioning must preserve historical attribution.

---

## Ownership Reassignment

When an identity loses access, owned operational responsibilities may require reassignment.

Examples include:

* Active Incidents.
* Knowledge Entries.
* Plugin Installations.
* Agent Definitions.
* Saved Searches.
* Notification Channels.
* Workflow tasks.

Access removal should not leave critical Resources without an accountable owner.

---

## Authorization Caching

Authorization data may be cached for performance.

Caching must consider:

* Identity.
* Organization.
* Role version.
* permission version.
* Resource Scope.
* Policy version.
* Membership status.
* Session status.
* expiration.

Sensitive access changes and revocations require rapid cache invalidation.

---

## Security Events

Authentication and Authorization may produce security events such as:

* Repeated authentication failure.
* suspicious login.
* MFA removal.
* credential creation.
* privileged Role assignment.
* emergency access.
* support access.
* cross-Organization access attempt.
* denied privileged action.
* credential compromise.
* unusual Agent or Plugin access.

Security events may contribute to Alerts or Incidents.

---

## Access Reviews

Organizations may periodically review:

* Active Members.
* assigned Roles.
* privileged permissions.
* Temporary Access.
* API Clients.
* Service Identities.
* Agent permissions.
* Plugin permissions.
* inactive identities.

Review outcomes may:

* Retain access.
* reduce access.
* revoke access.
* require recertification.

---

## Search and Discovery

Authorized administrators may search identities and access records by:

* Identity Type.
* Membership status.
* Role.
* Team.
* permission.
* privileged status.
* MFA status.
* last authentication.
* API Client status.
* Service Identity status.
* Agent or Plugin identity.
* temporary access.
* suspension status.

Search must respect administrative scope and sensitivity.

---

## Auditability

The following activities are auditable:

* Authentication success and failure.
* Session creation and revocation.
* Membership invitation and changes.
* Role creation and assignment.
* permission changes.
* API credential creation and revocation.
* MFA enrollment and removal.
* SSO configuration changes.
* Temporary Access.
* privileged access.
* emergency access.
* support access.
* Authorization denials for sensitive operations.
* cross-Organization access attempts.

An access audit record may include:

* Organization.
* Identity.
* Identity Type.
* operation.
* target Resource.
* decision.
* Role.
* permission.
* Resource Scope.
* authentication strength.
* applicable Policy.
* time.
* Correlation Identifier.

---

## Retention

Identity and access records are retained according to platform, Organization, security, legal, and compliance requirements.

Retention applies to:

* User records.
* Membership history.
* Role assignments.
* permission grants.
* Sessions.
* credential metadata.
* authentication events.
* Authorization Decisions.
* Temporary Access.
* support and emergency access.
* audit history.

Credential secrets must not be retained in ordinary audit records.

---

## Capability Interactions

| Capability              | Relationship                                     |
| ----------------------- | ------------------------------------------------ |
| Organization Management | Provides Organization and Membership context     |
| Project Management      | Provides Project Resource Scope                  |
| Application Management  | Provides Application ownership                   |
| Environment Management  | Provides Environment restrictions                |
| Event Processing        | Protects Event ingestion and access              |
| Alert Management        | Protects Alert operations                        |
| Incident Management     | Protects Incident operations and evidence        |
| Knowledge Management    | Protects Knowledge access and publication        |
| Agent Platform          | Establishes Agent identities and permissions     |
| Plugin Platform         | Establishes Plugin identities and permissions    |
| Notification Platform   | Protects recipients and Channels                 |
| Search                  | Protects queries and Search Results              |
| Administration          | Manages privileged access configuration          |
| Observability           | Measures authentication and authorization health |
| Public API              | Authenticates API Clients                        |
| Operational Workflows   | Enforces approvals and separation of duties      |
| Policy Layer            | Applies contextual access rules                  |
| Audit                   | Preserves identity and access history            |

Authentication and Authorization remain authoritative for identities, Memberships, Sessions, Roles, permissions, credentials, Access Grants, and Authorization Decisions.

Each business capability remains authoritative for its protected Resources and operations.

---

## Business Rules

* Every protected operation must be associated with an Identity or explicit system process.
* Authentication does not grant authorization by itself.
* Organization Membership is separate from User Identity.
* Only Active Memberships grant normal Organization access.
* Protected operations follow deny-by-default behavior.
* Platform safety and tenant isolation cannot be overridden.
* Every permission must be evaluated with Resource Scope.
* Resource identifiers do not grant access.
* Read access does not imply write or transition access.
* Identities cannot increase their own authority.
* Delegation cannot exceed existing authority.
* Temporary and emergency access must expire automatically.
* Non-human actors must use distinct non-human identities.
* Agents and Plugins must not impersonate Members.
* API credentials belong to one Organization.
* Credentials and Sessions must be revocable.
* Membership removal must revoke future access.
* Historical actions remain attributable after access removal.
* Sensitive access may require stronger authentication or approval.
* Cross-Organization access is prohibited unless explicitly governed.
* Authorization failure must not reveal restricted Resource existence.
* When authorization cannot be determined safely, access is denied.
* Unauthorized operations are rejected and auditable.

---

## Success Criteria

Authentication and Authorization are successful when:

* Every protected action remains attributable.
* Organization isolation is preserved.
* Human and non-human identities remain distinguishable.
* Access follows least privilege.
* Roles reflect business responsibilities.
* Resource Scope prevents excessive access.
* sensitive Resources remain protected.
* privileged operations receive stronger controls.
* access can be revoked quickly.
* Membership changes affect access reliably.
* API Clients, Agents, Plugins, and Services remain independently governed.
* Authorization Decisions are consistent and explainable.
* emergency and support access remain controlled.
* authentication or authorization failure never silently grants access.

---

## Related Documents

* `docs/functional/00-platform-overview.md`
* `docs/functional/01-organization-management.md`
* `docs/functional/02-project-management.md`
* `docs/functional/03-application-management.md`
* `docs/functional/04-environment-management.md`
* `docs/functional/05-event-processing.md`
* `docs/functional/06-alert-management.md`
* `docs/functional/07-incident-management.md`
* `docs/functional/08-knowledge-management.md`
* `docs/functional/09-agent-platform.md`
* `docs/functional/10-plugin-platform.md`
* `docs/functional/11-notification-platform.md`
* `docs/functional/12-search.md`
* `docs/functional/14-administration.md`
* `docs/functional/15-observability.md`
* `docs/functional/16-public-api.md`
* `docs/functional/17-operational-workflows.md`
* `docs/architecture/08-security-architecture.md`
* `docs/adr/ADR-003-multi-tenancy.md`
* `docs/adr/ADR-008-security-policy-layer.md`
* `docs/adr/ADR-009-core-is-framework-agnostic.md`
