<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Event;

use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use App\Infrastructure\Persistence\Event\Records\EventSourceRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Event\Application\Authentication\EventSourceIdentity;
use Sentinel\Event\Application\Command\IngestEventCommand;
use Sentinel\Event\Application\Handler\IngestEventHandler;
use Sentinel\Event\Domain\Entity\EventSource;
use Sentinel\Event\Domain\Repository\EventRepository;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRepository;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;
use Tests\TestCase;
use Throwable;

final class IngestEventConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL concurrency coverage requires DB_CONNECTION=pgsql.');
        }
    }

    #[Test]
    public function concurrent_identical_idempotent_requests_create_one_event_and_one_outbox_message(): void
    {
        $fixture = $this->fixture();
        [$firstPair, $secondPair] = $this->pairs();
        $firstPid = $this->forkIngest($firstPair, $fixture, 'concurrent-idempotency', null);
        $secondPid = $this->forkIngest($secondPair, $fixture, 'concurrent-idempotency', null);

        $results = $this->releaseAndCollect($firstPair, $secondPair, $firstPid, $secondPid);

        self::assertSame($results[0], $results[1]);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseCount('idempotency_records', 1);
    }

    #[Test]
    public function concurrent_matching_external_event_ids_create_one_event_and_one_outbox_message(): void
    {
        $fixture = $this->fixture();
        [$firstPair, $secondPair] = $this->pairs();
        $firstPid = $this->forkIngest($firstPair, $fixture, 'concurrent-external-first', 'producer-concurrent-1');
        $secondPid = $this->forkIngest($secondPair, $fixture, 'concurrent-external-second', 'producer-concurrent-1');

        $results = $this->releaseAndCollect($firstPair, $secondPair, $firstPid, $secondPid);

        self::assertSame($results[0], $results[1]);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseCount('idempotency_records', 2);
    }

    #[Test]
    public function failed_outbox_persistence_rolls_back_the_event_and_idempotency_record(): void
    {
        $fixture = $this->fixture();
        $handler = new IngestEventHandler(
            events: $this->app->make(EventRepository::class),
            organizations: $this->app->make(OrganizationRepository::class),
            projects: $this->app->make(ProjectLookupQuery::class),
            applications: $this->app->make(ApplicationRepository::class),
            environments: $this->app->make(EnvironmentRepository::class),
            idempotentOperation: new IdempotentOperation(
                $this->app->make(TransactionManager::class),
                $this->app->make(IdempotencyRepository::class),
                $this->app->make(UuidV7Generator::class),
                $this->app->make(Clock::class),
            ),
            uuidGenerator: $this->app->make(UuidV7Generator::class),
            outbox: new FailingEventOutboxMessageStore,
        );

        try {
            $handler->handle(
                IngestEventCommand::fromValues(
                    eventType: 'application.error',
                    schemaVersion: '1',
                    occurredAt: '2020-08-01T16:30:00Z',
                    payload: ['message' => 'Rollback Event'],
                    idempotencyKey: 'failed-event-outbox',
                ),
                $fixture->identity(),
            );
            self::fail('Expected Outbox persistence to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Outbox persistence failed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('idempotency_records', 0);
    }

    /** @return array{array{resource, resource}, array{resource, resource}} */
    private function pairs(): array
    {
        $firstPair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $secondPair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($firstPair);
        self::assertIsArray($secondPair);

        return [$firstPair, $secondPair];
    }

    /** @param array{resource, resource} $pair */
    private function forkIngest(array $pair, IngestConcurrencyFixture $fixture, string $idempotencyKey, ?string $externalEventId): int
    {
        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);

        if ($pid !== 0) {
            return $pid;
        }

        fclose($pair[0]);

        try {
            fgets($pair[1]);
            DB::purge();
            $result = $this->handler()->handle(
                IngestEventCommand::fromValues(
                    eventType: 'application.error',
                    schemaVersion: '1',
                    occurredAt: '2020-08-01T16:30:00Z',
                    payload: ['message' => 'Concurrent Event'],
                    idempotencyKey: $idempotencyKey,
                    externalEventId: $externalEventId,
                ),
                $fixture->identity(),
            );
            fwrite($pair[1], 'ok:'.$result->event()->id()->value()."\n");
            fclose($pair[1]);

            exit(0);
        } catch (Throwable $exception) {
            fwrite($pair[1], 'error:'.$exception::class.':'.$exception->getMessage()."\n");
            fclose($pair[1]);

            exit(1);
        }
    }

    /**
     * @param  array{resource, resource}  $firstPair
     * @param  array{resource, resource}  $secondPair
     * @return array{string, string}
     */
    private function releaseAndCollect(array $firstPair, array $secondPair, int $firstPid, int $secondPid): array
    {
        fclose($firstPair[1]);
        fclose($secondPair[1]);
        fwrite($firstPair[0], "go\n");
        fwrite($secondPair[0], "go\n");
        $firstResult = fgets($firstPair[0]);
        $secondResult = fgets($secondPair[0]);
        fclose($firstPair[0]);
        fclose($secondPair[0]);
        pcntl_waitpid($firstPid, $firstStatus);
        pcntl_waitpid($secondPid, $secondStatus);
        DB::purge();

        self::assertSame(0, pcntl_wexitstatus($firstStatus));
        self::assertSame(0, pcntl_wexitstatus($secondStatus));
        self::assertIsString($firstResult);
        self::assertIsString($secondResult);
        self::assertStringStartsWith('ok:', $firstResult);
        self::assertStringStartsWith('ok:', $secondResult);

        return [trim($firstResult), trim($secondResult)];
    }

    private function handler(): IngestEventHandler
    {
        return new IngestEventHandler(
            events: $this->app->make(EventRepository::class),
            organizations: $this->app->make(OrganizationRepository::class),
            projects: $this->app->make(ProjectLookupQuery::class),
            applications: $this->app->make(ApplicationRepository::class),
            environments: $this->app->make(EnvironmentRepository::class),
            idempotentOperation: new IdempotentOperation(
                $this->app->make(TransactionManager::class),
                $this->app->make(IdempotencyRepository::class),
                $this->app->make(UuidV7Generator::class),
                $this->app->make(Clock::class),
            ),
            uuidGenerator: $this->app->make(UuidV7Generator::class),
            outbox: $this->app->make(OutboxMessageStore::class),
        );
    }

    private function fixture(): IngestConcurrencyFixture
    {
        $organizationId = $this->uuid();
        $projectId = $this->uuid();
        $applicationId = $this->uuid();
        $environmentId = $this->uuid();
        $eventSourceId = $this->uuid();
        $identityId = $this->uuid();
        $now = new DateTimeImmutable('now');
        OrganizationRecord::query()->create(['id' => $organizationId, 'name' => 'Concurrent Event Organization', 'slug' => 'concurrent-event-organization-'.$organizationId, 'timezone' => 'UTC', 'created_by_identity_id' => $identityId, 'status' => 'active', 'version' => 1]);
        ProjectRecord::query()->create(['id' => $projectId, 'organization_id' => $organizationId, 'name' => 'Concurrent Event Project', 'slug' => 'concurrent-event-project-'.$projectId, 'description' => null, 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        ApplicationRecord::query()->create(['id' => $applicationId, 'organization_id' => $organizationId, 'project_id' => $projectId, 'name' => 'Concurrent Event Application', 'slug' => 'concurrent-event-application-'.$applicationId, 'description' => null, 'criticality' => 'high', 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        EnvironmentRecord::query()->create(['id' => $environmentId, 'organization_id' => $organizationId, 'application_id' => $applicationId, 'name' => 'Concurrent Event Environment', 'slug' => 'concurrent-event-environment-'.$environmentId, 'environment_type' => 'production', 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        EventSourceRecord::query()->create(['id' => $eventSourceId, 'organization_id' => $organizationId, 'project_id' => $projectId, 'application_id' => $applicationId, 'environment_id' => $environmentId, 'source_type' => 'api_client', 'name' => 'Concurrent Event Source', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now, 'version' => 1]);

        return new IngestConcurrencyFixture($organizationId, $projectId, $applicationId, $environmentId, $eventSourceId, $now);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}

final readonly class IngestConcurrencyFixture
{
    public function __construct(
        private string $organizationId,
        private string $projectId,
        private string $applicationId,
        private string $environmentId,
        private string $eventSourceId,
        private DateTimeImmutable $createdAt,
    ) {}

    public function identity(): EventSourceIdentity
    {
        return EventSourceIdentity::fromEventSource(EventSource::fromValues(
            id: $this->eventSourceId,
            organizationId: $this->organizationId,
            projectId: $this->projectId,
            applicationId: $this->applicationId,
            environmentId: $this->environmentId,
            sourceType: 'api_client',
            name: 'Concurrent Event Source',
            status: 'active',
            createdAt: $this->createdAt,
            updatedAt: $this->createdAt,
            version: 1,
        ));
    }
}

final class FailingEventOutboxMessageStore implements OutboxMessageStore
{
    public function append(OutboxMessage $message): void
    {
        throw new \RuntimeException('Outbox persistence failed.');
    }
}
