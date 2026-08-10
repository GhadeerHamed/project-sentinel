<?php

declare(strict_types=1);

namespace Tests\Unit\Project\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Project\Domain\Entity\Project;
use Sentinel\Project\Domain\Exception\InvalidProject;
use Sentinel\Project\Domain\Exception\InvalidProjectTransition;

final class ProjectTest extends TestCase
{
    #[Test]
    public function it_creates_an_active_project_with_canonical_server_owned_fields_and_event(): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        $project = Project::create(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f10',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f11',
            name: ' Commerce Platform ',
            slug: 'COMMERCE-PLATFORM',
            description: ' Customer-facing commerce services. ',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f12',
            at: $at,
            eventId: '0198f3b6-48ae-7a45-9192-6f8029df5f13',
            correlationId: '0198f3b6-48ae-7a45-9192-6f8029df5f14',
            causationId: '0198f3b6-48ae-7a45-9192-6f8029df5f15',
        );

        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f10', $project->id()->value());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f11', $project->organizationId()->value());
        self::assertSame('Commerce Platform', $project->name());
        self::assertSame('commerce-platform', $project->slug());
        self::assertSame('Customer-facing commerce services.', $project->description());
        self::assertSame('active', $project->status()->value);
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f12', $project->createdByIdentityId()->value());
        self::assertSame($at, $project->createdAt());
        self::assertSame($at, $project->updatedAt());
        self::assertNull($project->archivedAt());
        self::assertSame(1, $project->version()->value());

        $events = $project->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertSame('project.created', $events[0]->eventName());
        self::assertSame([
            'project_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f10',
            'actor_identity_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f12',
            'project_name' => 'Commerce Platform',
            'project_slug' => 'commerce-platform',
            'project_description' => 'Customer-facing commerce services.',
            'project_status' => 'active',
        ], $events[0]->payload());
    }

    #[Test]
    public function it_rejects_invalid_project_slugs(): void
    {
        $this->expectException(InvalidProject::class);

        Project::fromValues(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f20',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f21',
            name: 'Commerce Platform',
            slug: 'commerce platform',
            description: null,
            status: 'active',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f22',
            createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            archivedAt: null,
            version: 1,
        );
    }

    #[Test]
    public function it_archives_an_active_project_with_an_archival_timestamp_and_event(): void
    {
        $createdAt = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        $archivedAt = new DateTimeImmutable('2026-08-10T13:00:00+00:00');
        $project = Project::fromValues(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f30',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f31',
            name: 'Commerce Platform',
            slug: 'commerce-platform',
            description: null,
            status: 'active',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f32',
            createdAt: $createdAt,
            updatedAt: $createdAt,
            archivedAt: null,
            version: 1,
        );

        $archived = $project->archive(
            at: $archivedAt,
            actorIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f33',
            eventId: '0198f3b6-48ae-7a45-9192-6f8029df5f34',
            correlationId: '0198f3b6-48ae-7a45-9192-6f8029df5f35',
        );

        self::assertSame('archived', $archived->status()->value);
        self::assertSame($archivedAt, $archived->archivedAt());
        self::assertSame($archivedAt, $archived->updatedAt());
        self::assertSame(2, $archived->version()->value());
        $events = $archived->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertSame('project.archived', $events[0]->eventName());
        self::assertSame([
            'project_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f30',
            'actor_identity_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f33',
            'project_status' => 'archived',
        ], $events[0]->payload());
    }

    #[Test]
    public function it_rejects_archiving_an_already_archived_project(): void
    {
        $project = Project::fromValues(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f40',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f41',
            name: 'Commerce Platform',
            slug: 'commerce-platform',
            description: null,
            status: 'archived',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f42',
            createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-10T13:00:00+00:00'),
            archivedAt: new DateTimeImmutable('2026-08-10T13:00:00+00:00'),
            version: 2,
        );

        $this->expectException(InvalidProjectTransition::class);

        $project->archive(
            at: new DateTimeImmutable('2026-08-10T14:00:00+00:00'),
            actorIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f43',
            eventId: '0198f3b6-48ae-7a45-9192-6f8029df5f44',
            correlationId: '0198f3b6-48ae-7a45-9192-6f8029df5f45',
        );
    }
}
