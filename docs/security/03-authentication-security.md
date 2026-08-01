# Authentication Security

## Purpose

This document defines the security requirements for authenticating Users and non-human identities in Project Sentinel.

Authentication proves identity.

Authorization is evaluated separately.

---

## Supported Identity Types

Sentinel distinguishes:

* Users.
* API Clients.
* Services.
* Agents.
* Plugins.
* privileged support or emergency identities.

Each identity type must use dedicated credentials and remain attributable.

Non-human identities must not impersonate Users.

---

## Transport Security

Authentication is allowed only over HTTPS.

Credentials must never appear in:

* URLs.
* query parameters.
* Logs.
* error messages.
* Audit payloads.
* domain events.

---

## Password Security

When password authentication is supported:

* Passwords must be hashed using an approved adaptive algorithm.
* plaintext passwords must never be stored.
* password strength must be validated.
* known-compromised passwords should be rejected.
* password reset tokens must be single-use and short-lived.
* password changes should revoke affected Sessions.

Hash configuration must be reviewed as hardware and security guidance evolve.

---

## User Sessions

Session identifiers must be:

* Cryptographically random.
* stored only as secure hashes.
* revocable.
* time-limited.
* rotated after authentication or privilege changes.

Browser cookies should use:

```text
Secure
HttpOnly
SameSite
```

Cookie-based authentication requires CSRF protection.

---

## Session Expiration

Sessions should support:

* Idle expiration.
* absolute expiration.
* revocation.
* device or Session listing.
* logout from one or all Sessions.

Sensitive operations may require recent reauthentication.

---

## Multi-Factor Authentication

MFA should be required for:

* Platform administrators.
* privileged Organization administrators.
* credential management.
* security Policy changes.
* sensitive exports.
* emergency access.

Preferred methods:

* Passkeys.
* security keys.
* TOTP.
* approved enterprise identity providers.

Recovery methods must be limited and audited.

---

## API Credentials

API credentials must be:

* Scoped to one identity.
* scoped to one Organization where tenant-owned.
* assigned explicit permissions.
* revocable.
* optionally expiring.
* displayed only once when created.

Only a secure hash or secret reference may be stored.

Shared production credentials are prohibited.

---

## Short-Lived Tokens

Short-lived access tokens are preferred for:

* Internal Services.
* workload identities.
* external identity-provider Sessions.
* privileged operations.

Long-lived credentials require stronger controls, including:

* Narrow permissions.
* rotation.
* expiration.
* usage monitoring.
* rapid revocation.

---

## Single Sign-On

Enterprise authentication may use:

* OpenID Connect.
* SAML.

External authentication must map to one Sentinel User.

Organization access still requires an Active Membership.

SSO configuration changes are privileged and auditable.

---

## Webhook Authentication

Inbound Webhooks must verify:

* Signature.
* raw payload integrity.
* timestamp freshness.
* replay protection.
* endpoint ownership.
* expected provider or Plugin Installation.

Invalid requests must not create authoritative data.

---

## Credential Lifecycle

Credential statuses may include:

* `active`
* `expiring`
* `expired`
* `revoked`
* `compromised`
* `disabled`

Expired, revoked, compromised, or disabled credentials must fail immediately.

Rotation may allow a short overlap only when operationally required.

---

## Revocation Triggers

Sessions and credentials should be revoked when:

* A User signs out.
* a Membership is suspended or removed.
* an identity is suspended.
* compromise is suspected.
* credentials are reset.
* privileged access expires.
* an administrator explicitly revokes access.

Authorization-sensitive changes should invalidate cached access promptly.

---

## Brute-Force Protection

Authentication endpoints require protection against:

* Password guessing.
* credential stuffing.
* token guessing.
* MFA abuse.
* recovery-flow abuse.
* replay attacks.

Controls may include:

* Rate limiting.
* progressive delay.
* account or credential lockout.
* suspicious-activity detection.
* security Alerts.

Responses must not unnecessarily reveal whether an account exists.

---

## Authentication Context

Successful authentication creates a trusted context containing:

```text
identity_id
identity_type
authentication_method
authentication_strength
session_id
credential_id
request_id
correlation_id
```

Organization context is added only after validating the identity’s access to that Organization.

---

## Authentication Strength

Sensitive operations may require stronger authentication.

Suggested levels:

* `basic`
* `multi_factor`
* `phishing_resistant`
* `service_verified`
* `privileged`

Authorization Policy decides the required strength for each operation.

---

## Audit Requirements

Audit Records should be created for:

* Privileged authentication.
* significant failed-authentication patterns.
* credential creation.
* credential rotation.
* credential revocation.
* MFA enrollment or removal.
* SSO configuration changes.
* Session revocation.
* emergency-access authentication.

Secrets and raw tokens must never be audited.

---

## Monitoring

Sentinel should monitor:

* Repeated login failures.
* unusual credential use.
* impossible or suspicious Session activity.
* expired or revoked credential usage.
* MFA failures.
* unexpected privileged authentication.
* abnormal API Client behavior.
* repeated webhook signature failures.

High-risk activity should create security Alerts.

---

## Prohibited Practices

The following are prohibited:

* Plaintext password or token storage.
* credentials in URLs.
* shared Service or Plugin credentials.
* trusting network location as authentication.
* unlimited authentication attempts.
* long-lived unrestricted tokens.
* logging raw credentials.
* allowing revoked credentials to remain valid.
* using authentication success as authorization.

---

## Business Rules

* Every authenticated request has one attributable identity.
* Human and non-human identities remain distinct.
* Authentication occurs before protected Resource access.
* Authentication does not grant authorization automatically.
* Credentials are scoped, revocable, and protected.
* Sensitive operations may require stronger authentication.
* User Organization access requires an Active Membership.
* Revocation must take effect promptly.
* Authentication failures must not expose sensitive details.
* Credential and privileged-authentication events are audited.

---

## Related Documents

* `docs/security/00-security-overview.md`
* `docs/security/01-threat-model.md`
* `docs/security/02-tenant-isolation.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/security/05-secrets-credentials.md`
* `docs/api/02-authentication.md`
* `docs/database/02-identity-access-schema.md`
