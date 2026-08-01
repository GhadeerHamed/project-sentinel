# System Context

**Version:** 0.1  
**Status:** Draft (In Progress)  
**Document:** `architecture/01-system-context.md`  
**Project:** Project Sentinel  
**Owner:** Founders  
**Last Updated:** July 31, 2026

---

# Purpose

This document defines the system context of Project Sentinel.

It identifies external users, external systems, deployment boundaries, and communication interfaces.

The purpose of this document is to establish the operational environment in which Sentinel operates.

This document intentionally avoids internal implementation details.

---

# Scope

The System Context describes:

- Human actors
- External software systems
- Runtime integrations
- Infrastructure dependencies
- Communication boundaries

Internal software architecture is documented separately.

---

# Context Overview

Sentinel operates as the central Operational Intelligence Platform within an organization's engineering ecosystem.

Rather than replacing existing engineering tools, Sentinel integrates with them to provide unified operational understanding.

```text
                    Engineering Team
                           │
                           ▼
                    Project Sentinel
        ┌─────────────┼──────────────┐
        │             │              │
        ▼             ▼              ▼
 Applications   Monitoring Tools   CI/CD Systems
        │             │              │
        └─────────────┼──────────────┘
                      ▼
               Operational Events
```

Sentinel receives operational information from multiple sources, processes it internally, and provides engineers with a unified investigation experience.

---

# Primary Actors

The following human actors interact directly with Sentinel.

## Platform Administrator

Responsibilities:

- Configure Sentinel
- Manage organizations
- Install plugins
- Configure integrations
- Manage system settings

---

## Software Engineer

Responsibilities:

- Investigate incidents
- Review operational context
- Record findings
- Resolve incidents
- Search historical knowledge

---

## Engineering Manager

Responsibilities:

- Monitor operational health
- Review incidents
- Analyze trends
- Evaluate operational performance

---

## DevOps Engineer

Responsibilities:

- Configure integrations
- Monitor deployments
- Manage infrastructure connections
- Configure notifications

---

# External Systems

Sentinel integrates with numerous external systems.

---

## Monitored Applications

Examples:

- Laravel
- Symfony
- Node.js
- Go
- Python
- .NET

Responsibilities:

- Produce operational events.
- Authenticate with Sentinel.
- Send telemetry through supported plugins.

---

## Monitoring Platforms

Examples:

- Prometheus
- Grafana
- Datadog
- New Relic

Purpose:

Provide monitoring information consumed by Sentinel.

Sentinel does not replace these systems.

---

## Error Tracking Systems

Examples:

- Sentry
- Bugsnag

Purpose:

Provide exception information.

---

## CI/CD Platforms

Examples:

- GitHub Actions
- GitLab CI
- Jenkins

Purpose:

Provide deployment and pipeline events.

---

## Communication Platforms

Examples:

- Slack
- Microsoft Teams
- Discord
- Email

Purpose:

Receive notifications from Sentinel.

---

## Identity Providers

Examples:

- Google
- Microsoft
- GitHub
- LDAP
- SAML

Purpose:

Authenticate users.

---

# Infrastructure Dependencies

Sentinel depends upon the following infrastructure.

## Database

Primary persistence.

Examples:

- PostgreSQL

---

## Cache

Transient storage.

Examples:

- Redis

---

## Queue

Background processing.

Initially:

- Redis Queues

Future options:

- RabbitMQ
- NATS
- Kafka

---

## Object Storage

Stores attachments and exported artifacts.

Examples:

- Local Storage
- Amazon S3
- MinIO

---

# Communication Interfaces

The following interfaces connect Sentinel with external systems.

| Interface | Direction | Purpose |
|------------|-----------|----------|
| REST API | Bidirectional | Platform API |
| Webhooks | Outbound | Notifications |
| Plugin SDK | Inbound | Runtime integrations |
| WebSocket | Outbound | Live dashboard updates |
| CLI | Bidirectional | Administrative operations |

---

# Trust Boundaries

The architecture defines the following trust boundaries.

```text
Internet
      │
      ▼
API Gateway
      │
      ▼
Application Boundary
      │
      ▼
Domain Boundary
      │
      ▼
Persistence Boundary
```

Every boundary requires explicit authentication and authorization.

---

# Context Principles

The following principles apply throughout the system context.

## External Systems Are Independent

Sentinel does not own external systems.

Failures of external systems shall not compromise Sentinel.

---

## Loose Integration

Integrations should occur through stable APIs, plugins, and events.

Direct dependencies on proprietary technologies should be minimized.

---

## Runtime Independence

The platform shall support multiple application runtimes without architectural modification.

---

## Single Source of Operational Understanding

Sentinel becomes the central platform for operational investigations.

It does not become the system of record for logs, metrics, or deployments.

---

# Context Assumptions

The following assumptions apply.

- Organizations already operate production applications.
- Applications can communicate with Sentinel.
- External integrations may be unavailable temporarily.
- Network failures are expected.
- Plugins may evolve independently of the core platform.

---

# Related Documents

- `architecture/00-architecture-overview.md`
- `docs/07-domain-model.md`
- `docs/08-software-requirements.md`
- `architecture/02-container-architecture.md`