# API Authentication

## Purpose

This document defines how clients authenticate to Project Sentinel APIs.

Authentication proves the identity of the caller.

Authorization is evaluated separately after authentication succeeds.

---

## Supported Identities

API requests may be performed by:

* Human Users.
* API Clients.
* Internal Services.
* Agents.
* Plugins.
* Workflows.
* trusted platform processes.

The authenticated identity type must remain available throughout:

* Authorization.
* domain commands.
* background jobs.
* Audit Records.
* domain events.

Non-human identities must not impersonate Human Users.

---

## Transport Security

All API authentication requires HTTPS.

Unencrypted HTTP requests must be rejected.

Credentials must never appear in:

* URLs.
* query parameters.
* Logs.
* error messages.
* domain events.

---

## Authorization Header

Bearer credentials use:

```http
Authorization: Bearer {access_token}
```

Missing, expired, malformed, revoked, or invalid credentials return:

```http
401 Unauthorized
```

---

## User Sessions

Interactive Users authenticate through a secure Session.

Session tokens must be:

* Random and unguessable.
* stored only as secure hashes.
* transmitted through secure cookies or approved bearer mechanisms.
* time-limited.
* revocable.
* rotated after sensitive authentication events.

Browser cookies should use:

```text
Secure
HttpOnly
SameSite
```

Session authentication must include CSRF protection when cookies are used.

---

## API Clients

API Clients represent Organization-owned programmatic consumers.

Each API Client must have:

* One Organization owner.
* one non-human identity.
* explicit permissions.
* optional Resource Scope.
* credential status.
* expiration where required.

API Client credentials must not inherit unrestricted permissions from the User who created them.

---

## MVP Event Source Credentials

An MVP Event Source is the first supported `api_client` identity for Event ingestion. A session-authenticated Human User issues it through the Event Source API; the bearer credential authenticates only that non-human source and never the issuing Human User.

The credential is `esc_{credential_id}.{secret}`. Its public UUIDv7 credential identifier supports indexed lookup, while only a one-way hash of the random secret is persisted. The plaintext bearer credential is returned in the initial issuance response only; it cannot be retrieved later, is never logged, and does not appear in Audit metadata. An active credential resolves only within its Organization and to its active Event Source; it is scoped to that source's Project, Application, and Environment.

One active credential is permitted per Event Source. Rotation, replacement, retrieval, and additional Event Source credential types are deferred.

---

## Personal Access Tokens

Personal Access Tokens may be supported for approved developer workflows.

Each token must be:

* Owned by one User.
* scoped to one Organization.
* assigned explicit permissions.
* stored only as a secure hash.
* revocable.
* optionally expiring.

Token values should be displayed only once when created.

Personal Access Tokens should not be used for shared production integrations.

---

## Service Authentication

Internal Services must use dedicated Service Identities.

Acceptable mechanisms may include:

* Short-lived signed tokens.
* workload identity.
* mutual TLS.
* platform-issued service credentials.

Network location alone is not authentication.

Service credentials must not be shared between unrelated workloads.

---

## Agent Authentication

Each active Agent Version uses a distinct Agent Identity.

Agent requests must preserve:

* Organization.
* Agent Definition.
* Agent Version.
* Agent Execution.
* granted permissions.
* approved Resource Scope.

Agents cannot inherit the full permissions of the initiating User.

---

## Plugin Authentication

Each Plugin Installation uses a distinct Plugin Identity.

Plugin authentication must preserve:

* Organization.
* Plugin Installation.
* Plugin Version.
* granted capabilities.
* permitted Resource Scope.

A Plugin Installation cannot use another Organization’s credentials.

---

## Workflow Identity

A Workflow may coordinate operations but does not automatically receive unrestricted access.

Workflow Actions must preserve:

* Workflow Instance.
* initiating identity.
* current execution identity.
* required permission.
* approval reference where applicable.

Protected Actions require authorization again at execution time.

---

## Webhook Authentication

Inbound webhooks must be authenticated using an approved mechanism such as:

* HMAC signature.
* provider signature.
* mutual TLS.
* signed token.

Webhook verification should include:

* Signature validation.
* timestamp validation.
* replay protection.
* payload integrity.
* endpoint ownership.
* Plugin Installation or Channel resolution.

Invalid webhook authentication must not create authoritative domain data.

---

## Authentication Context

Successful authentication creates an Authentication Context containing:

```text
identity_id
identity_type
organization_id
authentication_method
authentication_strength
session_id
credential_id
request_id
correlation_id
```

`organization_id` may be resolved after authentication when a User belongs to multiple Organizations.

The selected Organization must be validated against the identity.

---

## Authentication Strength

Authentication strength may influence access to sensitive operations.

Possible levels:

* `basic`
* `multi_factor`
* `phishing_resistant`
* `service_verified`
* `privileged`

Sensitive operations may require:

* Recent authentication.
* Multi-Factor Authentication.
* phishing-resistant authentication.
* privileged-access approval.

---

## Multi-Factor Authentication

MFA should be required for:

* Platform administrators.
* privileged Organization administrators.
* credential management.
* sensitive exports.
* security Policy changes.
* emergency access.

Supported methods may include:

* TOTP.
* passkeys.
* security keys.
* approved external identity providers.

Recovery mechanisms must be limited, auditable, and protected.

---

## Single Sign-On

Enterprise authentication may support:

* OpenID Connect.
* SAML.
* approved directory integration.

An external identity must map to one Sentinel User.

Organization access still requires a valid Organization Membership.

Successful SSO authentication does not bypass Sentinel Authorization.

---

## Credential Storage

Sentinel must store only:

* Credential hash.
* public identifier.
* secret reference.
* credential type.
* owner.
* status.
* creation time.
* expiration.
* rotation time.
* revocation time.

Plaintext secrets must not be stored in the application database.

---

## Credential Lifecycle

Credential statuses may include:

* `active`
* `expiring`
* `expired`
* `revoked`
* `compromised`
* `disabled`

Revoked, expired, compromised, or disabled credentials must fail immediately.

Credential rotation should allow a controlled overlap period only when required.

---

## Token Expiration

Access tokens should be short-lived where practical.

Long-lived access should use:

* Secure refresh mechanisms.
* credential rotation.
* explicit revocation.
* narrow scopes.

Expiration behavior must be documented for each authentication method.

---

## Session Revocation

Sessions should be revoked when:

* The User signs out.
* the Membership is suspended or removed.
* the User is suspended.
* credentials are reset.
* suspicious activity is detected.
* privileged access expires.
* an administrator revokes access.

Authorization-sensitive changes should invalidate affected Sessions or cached access rapidly.

---

## Organization Selection

A User with multiple Memberships must explicitly operate within one Organization context.

The API must verify:

1. The User is authenticated.
2. The Membership exists.
3. The Membership is Active.
4. The requested Organization matches the Membership.
5. Required Authorization succeeds.

The Organization identifier supplied in the route cannot be trusted by itself.

---

## Authentication Failures

Authentication errors should use stable codes.

Examples:

```text
authentication.required
authentication.invalid_credential
authentication.expired
authentication.revoked
authentication.mfa_required
authentication.session_expired
authentication.webhook_signature_invalid
```

Responses must not reveal whether a hidden identity or Resource exists.

---

## Rate Limiting

Authentication endpoints must be protected against:

* Brute-force attempts.
* credential stuffing.
* token guessing.
* replay attacks.
* excessive MFA attempts.
* excessive password recovery attempts.

Rate limits may apply by:

* IP address.
* identity.
* credential identifier.
* Organization.
* device or session.

Suspicious activity should produce security telemetry.

---

## Audit Requirements

Audit Records should be created for:

* Successful privileged authentication.
* failed authentication patterns.
* Session revocation.
* credential creation.
* credential rotation.
* credential revocation.
* MFA enrollment or removal.
* SSO configuration changes.
* privileged-access authentication.
* webhook signature failures where significant.

Secrets and raw tokens must never appear in Audit Records.

---

## Security Rules

* Authentication must occur before tenant Resource access.
* Credentials must be transmitted only over HTTPS.
* Raw credentials must not be logged.
* Passwords and tokens must be hashed or externally secured.
* Non-human identities use dedicated credentials.
* Authentication does not grant Authorization automatically.
* Revocation must take effect promptly.
* Webhooks require signature and replay validation.
* Sensitive operations may require stronger authentication.
* Cross-Organization credentials are prohibited.

---

## Business Rules

* Every authenticated request has one acting identity.
* Human and non-human identities remain distinct.
* Every Organization-owned credential belongs to one Organization.
* User Organization access requires an Active Membership.
* API Clients, Agents, and Plugins receive explicit permissions.
* Network location is not sufficient authentication.
* Token values are not stored in plaintext.
* Revoked or expired credentials cannot be used.
* Authentication strength may restrict sensitive operations.
* Authentication failures must not expose sensitive details.
* Authorization is evaluated after authentication.

---

## Related Documents

* `docs/api/00-api-overview.md`
* `docs/api/01-api-conventions.md`
* `docs/api/03-errors.md`
* `docs/database/02-identity-access-schema.md`
* `docs/functional/13-authentication-authorization.md`
* `docs/architecture/08-security-architecture.md`
* `docs/security/03-authentication-security.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/security/05-secrets-credentials.md`
