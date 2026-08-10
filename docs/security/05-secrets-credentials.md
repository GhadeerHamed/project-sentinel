# Secrets and Credentials

## Purpose

This document defines how Project Sentinel stores, accesses, rotates, and revokes secrets and credentials.

---

## Scope

Secrets include:

* API keys.
* passwords.
* access tokens.
* refresh tokens.
* webhook signing secrets.
* encryption keys.
* provider credentials.
* database credentials.
* private certificates.

---

## Core Rules

* Plaintext secrets must not be stored in application tables.
* Secrets must use an approved secret-management system.
* Database records store only secret references and metadata.
* Access follows least privilege.
* Secrets must be rotatable and revocable.
* Secret values must not appear in Logs, Audit Records, URLs, or error messages.

---

## Secret References

Application records should store:

```text
secret_reference
credential_type
status
created_at
expires_at
rotated_at
revoked_at
```

Example:

```json
{
  "credential_type": "github_token",
  "secret_reference": "secrets/org-123/plugins/github/token",
  "status": "active"
}
```

The secret reference must not itself contain the secret.

---

## Secret Ownership

Every tenant-owned secret must be associated with:

* One Organization.
* one identity, Plugin Installation, Channel, or integration.
* one intended purpose.
* an explicit access scope.

Sharing secrets across Organizations is prohibited.

---

## Storage

Approved storage may include:

* Cloud secret managers.
* dedicated vault systems.
* encrypted platform-managed secret storage.

Environment variables may be used for limited deployment-level configuration, but not as the primary tenant-secret store.

---

## Access

Secret access must be limited to the exact runtime component that needs it.

Access must validate:

* Organization context.
* identity or workload.
* secret purpose.
* operation.
* current credential status.

Secrets should be retrieved only when needed and kept in memory briefly.

---

## Credential Lifecycle

Supported statuses:

* `active`
* `expiring`
* `expired`
* `revoked`
* `compromised`
* `disabled`

Inactive credentials must not be used.

---

## MVP Event Source Bearer Credentials

An Event Source bearer credential is verifier-backed rather than stored in a secret-reference system: the public credential UUID and a one-way secret hash are retained, while the plaintext `esc_{credential_id}.{secret}` value is returned only at issuance. It must never be written to application data, Logs, Audit Records, domain events, or idempotency records. The public identifier is safe to use for indexed credential lookup; the secret is verified only against the retrieved hash.

MVP revocation changes the one active Event Source credential from `active` to `revoked` and records its revocation time. Authentication rejects revoked credentials immediately. Rotation, replacement, expiration, disabling, and multiple active credentials are not MVP capabilities.

---

## Rotation

Rotation should:

1. Create a new secret.
2. validate it.
3. activate it.
4. update the secret reference where needed.
5. revoke the previous secret.
6. record the operation.

A short overlap period may be allowed when required by an external provider.

---

## Revocation

Credentials must be revocable immediately when:

* Compromise is suspected.
* an identity is disabled.
* a Plugin is uninstalled.
* a Membership is removed.
* an integration is archived.
* privileged access ends.

Revocation should invalidate related Sessions or cached credentials where applicable.

---

## Secret Creation

New secret values should be:

* Generated using cryptographically secure randomness.
* displayed only once when user retrieval is required.
* transmitted over HTTPS.
* excluded from later API responses.

Sentinel must not provide an endpoint to retrieve an existing plaintext secret.

---

## Logging and Audit

Logs and Audit Records may include:

* Credential identifier.
* credential type.
* owner.
* status change.
* rotation or revocation time.

They must not include:

* Secret values.
* token fragments that materially weaken security.
* authorization headers.
* private keys.

---

## Plugins and Notifications

Plugin and Notification provider credentials must use secret references.

Configuration documents must not contain credentials.

Each Plugin Installation or Channel should use its own credential binding where practical.

---

## Agent Providers

AI provider credentials must be isolated by deployment or Organization according to product Policy.

Agent prompts, outputs, and execution history must not expose provider credentials.

---

## Development and Testing

Production secrets must never be used in local development or automated tests.

Development environments should use:

* Test credentials.
* local secret stores.
* mocked providers.
* short-lived sandbox credentials.

Secrets must not be committed to source control.

---

## Monitoring

Sentinel should monitor:

* Failed secret access.
* use of revoked credentials.
* unusual access frequency.
* secrets nearing expiration.
* failed rotation.
* cross-Organization access attempts.
* secret-manager availability.

High-risk events should create security Alerts.

---

## Incident Response

Suspected secret compromise requires:

1. Immediate revocation.
2. replacement credential creation.
3. affected identity and scope review.
4. Session or token invalidation.
5. Audit and Log investigation.
6. provider-side revocation where applicable.
7. security Incident tracking.

---

## Prohibited Practices

The following are prohibited:

* Plaintext secret storage.
* secrets in source control.
* secrets in URLs.
* credentials inside JSON configuration.
* shared credentials across unrelated identities.
* logging authorization headers.
* returning stored secrets through APIs.
* keeping compromised credentials active.
* using production secrets in development.

---

## Business Rules

* Secrets are stored only through approved secret management.
* Application tables contain references, not plaintext values.
* Every tenant secret belongs to one Organization.
* Secret access is purpose-limited and attributable.
* Credentials support expiration, rotation, and revocation.
* Secret values are never returned after creation.
* Compromised credentials are revoked immediately.
* Production and development credentials remain separate.

---

## Related Documents

* `docs/security/00-security-overview.md`
* `docs/security/03-authentication-security.md`
* `docs/security/04-authorization-enforcement.md`
* `docs/security/06-data-protection.md`
* `docs/security/07-agent-plugin-security.md`
* `docs/database/02-identity-access-schema.md`
* `docs/database/10-plugin-schema.md`
* `docs/database/11-notification-schema.md`
