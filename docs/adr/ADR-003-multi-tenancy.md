# ADR-003 — Multi-Tenancy

**Status:** Accepted

**Date:** August 1, 2026

**Decision Makers:** Founders

---

# Context

Project Sentinel is designed as a multi-tenant Operational Intelligence Platform.

Multiple organizations may operate on the same platform while maintaining complete isolation of operational data, configurations, integrations, and security boundaries.

Tenant isolation must be guaranteed by the architecture rather than relying solely on deployment topology or operational procedures.

The platform must also support self-hosted deployments where only a single Organization exists without requiring architectural changes.

---

# Decision

Project Sentinel adopts **Organization** as the primary tenant boundary.

Every protected business resource belongs to exactly one Organization.

Organization ownership is enforced consistently throughout the Domain Model, Application Layer, APIs, Plugins, Agents, Event Processing, and Security Architecture.

Tenant isolation is a Core architectural responsibility.

---

# Rationale

Using Organization as the primary security boundary provides a single, consistent ownership model across the platform.

Every authorization decision, policy evaluation, and business operation can determine ownership using the same architectural rule.

This avoids special cases and reduces implementation complexity.

---

# Organization Ownership

Every protected resource belongs to one Organization.

Examples include:

- Projects
- Applications
- Environments
- Events
- Alerts
- Incidents
- Knowledge Entries
- Integrations
- Plugin Configuration
- Agent Configuration
- API Credentials
- Audit Records

Ownership is established when the resource is created.

---

# Security Boundary

Organizations are isolated at every architectural layer.

Isolation applies to:

- Business operations
- Data access
- Search results
- Event processing
- Background workers
- Plugins
- Agents
- Audit logs
- Configuration

No component may bypass Organization boundaries.

---

# Authorization Model

Authorization evaluates:

1. Identity
2. Organization Membership
3. Resource Ownership
4. Permissions
5. Organizational Policies

Only after every stage succeeds may the requested business operation continue.

---

# Event Isolation

Every Domain Event contains Organization context.

Event consumers process only Events belonging to the appropriate Organization.

Cross-organization Event processing is prohibited.

---

# Plugin Isolation

Plugins execute within Organization boundaries.

Plugins receive Organization context through approved platform contracts.

Plugins shall not access resources belonging to other Organizations.

---

# Agent Isolation

Agents analyze operational data only within the Organization that triggered execution.

Recommendations, findings, and automation requests remain scoped to that Organization.

Agents never combine operational evidence from different Organizations.

---

# Data Ownership

The following principles apply:

- Data ownership is explicit.
- Ownership never depends on deployment topology.
- Ownership is preserved throughout the data lifecycle.
- Ownership is auditable.

Organization ownership is treated as immutable under normal platform operation.

---

# Consequences

Positive consequences include:

- Strong tenant isolation.
- Simplified authorization.
- Consistent ownership model.
- Improved security.
- Enterprise readiness.
- Simplified compliance.
- Support for both SaaS and self-hosted deployments.

Trade-offs include:

- Every protected resource requires Organization context.
- APIs and Events must propagate Organization identifiers.
- Cross-organization reporting requires explicitly designed platform capabilities.

These trade-offs are accepted because they produce a simpler, safer, and more predictable architecture.

---

# Alternatives Considered

## Database-Level Isolation Only

Use separate databases to isolate customers.

Rejected because deployment topology should not define business ownership.

Database isolation may be used as an operational strategy but is not the architectural tenant boundary.

---

## Project as Tenant

Treat Projects as tenants.

Rejected because Organizations may own multiple Projects.

Projects represent business organization, not security boundaries.

---

## Environment as Tenant

Treat Environments as tenants.

Rejected because Environments belong to Applications, which belong to Projects, which belong to Organizations.

Environment-level ownership would duplicate the ownership model.

---

# Architectural Principles

The Multi-Tenancy Architecture follows these principles.

- Organization is the primary tenant boundary.
- Every protected resource belongs to exactly one Organization.
- Tenant isolation is enforced throughout the platform.
- Authorization always evaluates Organization ownership.
- Events, Plugins, and Agents remain Organization-aware.
- Deployment topology does not define tenant boundaries.

---

# Related Documents

- `architecture/01-system-context.md`
- `architecture/02-domain-architecture.md`
- `architecture/08-security-architecture.md`

---

# Related ADRs

- `ADR-001-product-positioning.md`
- `ADR-002-plugin-architecture.md`
- `ADR-004-operational-processing-model.md`