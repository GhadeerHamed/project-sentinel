<?php

declare(strict_types=1);

namespace Tests\Feature\Event;

use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use App\Infrastructure\Persistence\Event\Records\EventRecord;
use App\Infrastructure\Persistence\Event\Records\EventSourceCredentialRecord;
use App\Infrastructure\Persistence\Event\Records\EventSourceRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class IngestEventEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_active_event_source_durably_accepts_a_canonical_event_with_server_derived_scope_and_outbox_publication(): void
    {
        $fixture = $this->fixture();

        $response = $this->postIngest($fixture, [
            'event_type' => 'application.error',
            'schema_version' => '1',
            'occurred_at' => '2020-08-01T18:30:00+02:00',
            'external_event_id' => 'evt-98421',
            'severity' => 'high',
            'payload' => ['message' => 'Checkout request failed', 'status_code' => 500],
            'attributes' => ['route' => '/checkout', 'retry' => false],
        ], 'accept-canonical-event');

        $response->assertAccepted()
            ->assertJsonPath('data.type', 'event')
            ->assertJsonPath('data.attributes.project_id', $fixture->projectId)
            ->assertJsonPath('data.attributes.application_id', $fixture->applicationId)
            ->assertJsonPath('data.attributes.environment_id', $fixture->environmentId)
            ->assertJsonPath('data.attributes.event_source_id', $fixture->eventSourceId)
            ->assertJsonPath('data.attributes.external_event_id', 'evt-98421')
            ->assertJsonPath('data.attributes.event_type', 'application.error')
            ->assertJsonPath('data.attributes.category', 'error')
            ->assertJsonPath('data.attributes.severity', 'high')
            ->assertJsonPath('data.attributes.schema_version', '1')
            ->assertJsonPath('data.attributes.occurred_at', '2020-08-01T16:30:00+00:00')
            ->assertJsonMissingPath('data.attributes.payload')
            ->assertJsonMissingPath('data.attributes.attributes');

        $eventId = $response->json('data.id');
        self::assertIsString($eventId);
        self::assertMatchesRegularExpression('/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/', $eventId);
        self::assertNotNull($response->headers->get('X-Correlation-ID'));
        $this->assertDatabaseHas('events', [
            'id' => $eventId,
            'organization_id' => $fixture->organizationId,
            'project_id' => $fixture->projectId,
            'application_id' => $fixture->applicationId,
            'environment_id' => $fixture->environmentId,
            'event_source_id' => $fixture->eventSourceId,
            'external_event_id' => 'evt-98421',
            'event_type' => 'application.error',
            'category' => 'error',
            'severity' => 'high',
            'schema_version' => '1',
        ]);
        $event = EventRecord::query()->find($eventId);
        self::assertNotNull($event);
        self::assertSame('2020-08-01T16:30:00+00:00', $event->occurred_at->format(DATE_ATOM));
        self::assertNotSame($event->occurred_at->format(DATE_ATOM), $event->received_at->format(DATE_ATOM));
        self::assertSame($event->received_at->format(DATE_ATOM), $event->created_at->format(DATE_ATOM));
        self::assertSame(['message' => 'Checkout request failed', 'status_code' => 500], $event->payload);
        self::assertSame(['retry' => false, 'route' => '/checkout'], $event->attributes);

        $this->assertDatabaseHas('outbox_messages', [
            'organization_id' => $fixture->organizationId,
            'event_id' => $eventId,
            'event_type' => 'event.accepted',
            'aggregate_type' => 'event',
            'aggregate_id' => $eventId,
        ]);
        $this->assertDatabaseMissing('outbox_messages', ['event_type' => 'event.ingested']);
        $outboxJson = json_encode(DB::table('outbox_messages')->get(), JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString($fixture->bearerCredential(), $outboxJson);
        self::assertStringNotContainsString($fixture->secret, $outboxJson);
        $this->assertDatabaseCount('audit_records', 0);
    }

    #[Test]
    public function unknown_inactive_and_cross_organization_bearer_credentials_cannot_ingest_events(): void
    {
        $fixture = $this->fixture();
        $payload = $this->validPayload();
        $unknownCredential = 'esc_'.$this->uuid().'.'.str_repeat('b', 64);

        $this->postIngest($fixture, $payload, 'unknown-credential', $unknownCredential)
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentication.invalid_credential');

        EventSourceCredentialRecord::query()->whereKey($fixture->credentialId)->update(['status' => 'revoked', 'revoked_at' => now()]);
        $this->postIngest($fixture, $payload, 'revoked-credential')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentication.invalid_credential');

        EventSourceCredentialRecord::query()->whereKey($fixture->credentialId)->update(['status' => 'active', 'revoked_at' => null]);
        EventSourceRecord::query()->whereKey($fixture->eventSourceId)->update(['status' => 'inactive']);
        $this->postIngest($fixture, $payload, 'inactive-source')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentication.invalid_credential');

        EventSourceRecord::query()->whereKey($fixture->eventSourceId)->update(['status' => 'active']);
        $otherOrganizationId = $this->organization($this->uuid());
        $this->postIngest($fixture, $payload, 'cross-organization-source', null, $otherOrganizationId)
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'authentication.invalid_credential');
        $this->assertDatabaseCount('events', 0);
    }

    #[Test]
    public function archived_parents_and_suspended_or_archived_organizations_reject_ingestion(): void
    {
        foreach ([
            ['organization' => 'active', 'project' => 'archived', 'application' => 'active', 'environment' => 'active'],
            ['organization' => 'active', 'project' => 'active', 'application' => 'archived', 'environment' => 'active'],
            ['organization' => 'active', 'project' => 'active', 'application' => 'active', 'environment' => 'archived'],
            ['organization' => 'suspended', 'project' => 'active', 'application' => 'active', 'environment' => 'active'],
            ['organization' => 'archived', 'project' => 'active', 'application' => 'active', 'environment' => 'active'],
        ] as $index => $states) {
            $fixture = $this->fixture(
                organizationStatus: $states['organization'],
                projectStatus: $states['project'],
                applicationStatus: $states['application'],
                environmentStatus: $states['environment'],
            );

            $this->postIngest($fixture, $this->validPayload(), 'inactive-hierarchy-'.$index)
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'request.invalid_state');
        }

        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseCount('outbox_messages', 0);
    }

    #[Test]
    #[DataProvider('eventTypes')]
    public function every_supported_event_type_maps_to_its_canonical_category(string $eventType, string $category): void
    {
        $fixture = $this->fixture();
        $payload = $this->validPayload();
        $payload['event_type'] = $eventType;

        $this->postIngest($fixture, $payload, 'event-type-'.$eventType)
            ->assertAccepted()
            ->assertJsonPath('data.attributes.event_type', $eventType)
            ->assertJsonPath('data.attributes.category', $category);
    }

    /** @return array<string, array{string, string}> */
    public static function eventTypes(): array
    {
        return [
            'application error' => ['application.error', 'error'],
            'health failed' => ['application.health_failed', 'availability'],
            'health recovered' => ['application.health_recovered', 'availability'],
            'deployment completed' => ['deployment.completed', 'deployment'],
            'custom operational event' => ['custom.operational_event', 'platform'],
        ];
    }

    #[Test]
    public function unsupported_schema_types_non_object_payloads_and_server_owned_fields_are_rejected(): void
    {
        $fixture = $this->fixture();

        $unsupportedSchema = $this->validPayload();
        $unsupportedSchema['schema_version'] = '2';
        $this->postIngest($fixture, $unsupportedSchema, 'unsupported-schema')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'request.validation_failed');

        $unsupportedType = $this->validPayload();
        $unsupportedType['event_type'] = 'deployment.started';
        $this->postIngest($fixture, $unsupportedType, 'unsupported-type')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'request.validation_failed');

        $arrayPayload = $this->validPayload();
        $arrayPayload['payload'] = ['not', 'an', 'object'];
        $this->postIngest($fixture, $arrayPayload, 'array-payload')
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.payload.0', 'The payload must be a JSON object.');

        $serverOwned = $this->validPayload();
        $serverOwned['id'] = $this->uuid();
        $serverOwned['organization_id'] = $this->uuid();
        $serverOwned['project_id'] = $this->uuid();
        $serverOwned['application_id'] = $this->uuid();
        $serverOwned['environment_id'] = $this->uuid();
        $serverOwned['event_source_id'] = $this->uuid();
        $serverOwned['category'] = 'availability';
        $serverOwned['received_at'] = '2020-08-01T16:30:01Z';
        $serverOwned['created_at'] = '2020-08-01T16:30:01Z';
        $this->postIngest($fixture, $serverOwned, 'server-owned-fields')
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.id.0', 'The field is not supported.');

        $this->assertDatabaseCount('events', 0);
    }

    #[Test]
    public function payload_and_attributes_enforce_the_canonical_64_kib_limit(): void
    {
        $fixture = $this->fixture();
        $atBoundary = $this->validPayload();
        $atBoundary['payload'] = ['message' => str_repeat('a', 65_520)];

        $this->postIngest($fixture, $atBoundary, 'payload-boundary')->assertAccepted();

        $oversized = $this->validPayload();
        $oversized['payload'] = ['message' => str_repeat('a', 65_521)];
        $this->postIngest($fixture, $oversized, 'payload-oversized')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'request.validation_failed');
        $this->assertDatabaseCount('events', 1);
    }

    #[Test]
    public function an_empty_json_object_payload_is_accepted_and_persisted_as_a_json_object(): void
    {
        $fixture = $this->fixture();
        $payload = $this->validPayload();
        $payload['payload'] = new \stdClass;

        $eventId = $this->postIngest($fixture, $payload, 'empty-object-payload')
            ->assertAccepted()
            ->json('data.id');
        self::assertIsString($eventId);
        $shape = DB::selectOne('SELECT jsonb_typeof(payload) AS payload_type, jsonb_typeof(attributes) AS attributes_type FROM events WHERE id = ?', [$eventId]);
        self::assertNotNull($shape);
        self::assertSame('object', $shape->payload_type);
        self::assertSame('object', $shape->attributes_type);
    }

    #[Test]
    public function optional_event_fields_are_normalized_and_persisted(): void
    {
        $fixture = $this->fixture();
        $correlationId = $this->uuid();
        $causationId = $this->uuid();
        $payload = $this->validPayload();
        $payload['severity'] = 'informational';
        $payload['external_event_id'] = 'external-optional-event';
        $payload['attributes'] = ['attempt' => 3, 'retry' => false, 'route' => '/checkout'];
        $payload['correlation_id'] = $correlationId;
        $payload['causation_id'] = $causationId;
        $payload['trace_id'] = '4bf92f3577b34da6a3ce929d0e0e4736';

        $eventId = $this->postIngest($fixture, $payload, 'optional-event-fields')
            ->assertAccepted()
            ->json('data.id');
        self::assertIsString($eventId);
        $event = EventRecord::query()->find($eventId);
        self::assertNotNull($event);
        self::assertSame('informational', $event->severity);
        self::assertEquals(['attempt' => 3, 'retry' => false, 'route' => '/checkout'], $event->attributes);
        self::assertSame($correlationId, $event->correlation_id);
        self::assertSame($causationId, $event->causation_id);
        self::assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $event->trace_id);
    }

    #[Test]
    public function matching_idempotent_retries_return_the_same_event_and_changed_retries_conflict(): void
    {
        $fixture = $this->fixture();
        $payload = $this->validPayload();

        $first = $this->postIngest($fixture, $payload, 'idempotent-event')->assertAccepted();
        $second = $this->postIngest($fixture, $payload, 'idempotent-event')->assertAccepted();

        self::assertSame($first->json('data.id'), $second->json('data.id'));
        $changed = $payload;
        $changed['payload'] = ['message' => 'Changed'];
        $this->postIngest($fixture, $changed, 'idempotent-event')
            ->assertConflict()
            ->assertJsonPath('error.code', 'idempotency.key_conflict');
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('idempotency_records', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
    }

    #[Test]
    public function matching_source_duplicates_replay_the_original_event_and_changed_external_ids_conflict(): void
    {
        $fixture = $this->fixture();
        $payload = $this->validPayload();
        $payload['external_event_id'] = 'producer-event-1';

        $first = $this->postIngest($fixture, $payload, 'source-duplicate-first')->assertAccepted();
        $second = $this->postIngest($fixture, $payload, 'source-duplicate-second')->assertAccepted();

        self::assertSame($first->json('data.id'), $second->json('data.id'));
        $changed = $payload;
        $changed['payload'] = ['message' => 'Changed producer Event'];
        $this->postIngest($fixture, $changed, 'source-duplicate-conflict')
            ->assertConflict()
            ->assertJsonPath('error.code', 'event.external_id_conflict');
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseCount('idempotency_records', 2);
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'event_type' => 'application.error',
            'schema_version' => '1',
            'occurred_at' => '2020-08-01T16:30:00Z',
            'payload' => ['message' => 'Checkout request failed'],
        ];
    }

    private function fixture(
        string $organizationStatus = 'active',
        string $projectStatus = 'active',
        string $applicationStatus = 'active',
        string $environmentStatus = 'active',
    ): IngestEventFixture {
        $identityId = $this->uuid();
        $organizationId = $this->organization($identityId, $organizationStatus);
        $projectId = $this->project($organizationId, $identityId, $projectStatus);
        $applicationId = $this->application($organizationId, $projectId, $identityId, $applicationStatus);
        $environmentId = $this->environment($organizationId, $applicationId, $identityId, $environmentStatus);
        $eventSourceId = $this->uuid();
        $credentialId = $this->uuid();
        $secret = str_repeat('a', 64);
        $now = now();
        EventSourceRecord::query()->create([
            'id' => $eventSourceId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'application_id' => $applicationId,
            'environment_id' => $environmentId,
            'source_type' => 'api_client',
            'name' => 'Ingestion API Client',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'version' => 1,
        ]);
        EventSourceCredentialRecord::query()->create([
            'id' => $credentialId,
            'organization_id' => $organizationId,
            'event_source_id' => $eventSourceId,
            'secret_hash' => password_hash($secret, PASSWORD_BCRYPT),
            'status' => 'active',
            'created_at' => $now,
            'revoked_at' => null,
            'expires_at' => null,
        ]);

        return new IngestEventFixture(
            organizationId: $organizationId,
            projectId: $projectId,
            applicationId: $applicationId,
            environmentId: $environmentId,
            eventSourceId: $eventSourceId,
            credentialId: $credentialId,
            secret: $secret,
        );
    }

    private function organization(string $identityId, string $status = 'active'): string
    {
        $organizationId = $this->uuid();
        OrganizationRecord::query()->create([
            'id' => $organizationId,
            'name' => 'Ingestion Organization',
            'slug' => 'ingestion-organization-'.$organizationId,
            'timezone' => 'UTC',
            'created_by_identity_id' => $identityId,
            'status' => $status,
            'version' => 1,
        ]);

        return $organizationId;
    }

    private function project(string $organizationId, string $identityId, string $status): string
    {
        $projectId = $this->uuid();
        $now = now();
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => 'Ingestion Project',
            'slug' => 'ingestion-project-'.$projectId,
            'description' => null,
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => $status === 'archived' ? $now : null,
            'version' => $status === 'archived' ? 2 : 1,
        ]);

        return $projectId;
    }

    private function application(string $organizationId, string $projectId, string $identityId, string $status): string
    {
        $applicationId = $this->uuid();
        $now = now();
        ApplicationRecord::query()->create([
            'id' => $applicationId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'name' => 'Ingestion Application',
            'slug' => 'ingestion-application-'.$applicationId,
            'description' => null,
            'criticality' => 'high',
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => $status === 'archived' ? $now : null,
            'version' => $status === 'archived' ? 2 : 1,
        ]);

        return $applicationId;
    }

    private function environment(string $organizationId, string $applicationId, string $identityId, string $status): string
    {
        $environmentId = $this->uuid();
        $now = now();
        EnvironmentRecord::query()->create([
            'id' => $environmentId,
            'organization_id' => $organizationId,
            'application_id' => $applicationId,
            'name' => 'Ingestion Environment',
            'slug' => 'ingestion-environment-'.$environmentId,
            'environment_type' => 'production',
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => $status === 'archived' ? $now : null,
            'version' => $status === 'archived' ? 2 : 1,
        ]);

        return $environmentId;
    }

    /** @param array<string, mixed> $payload */
    private function postIngest(IngestEventFixture $fixture, array $payload, string $idempotencyKey, ?string $bearerCredential = null, ?string $organizationId = null): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->withHeader('Authorization', 'Bearer '.($bearerCredential ?? $fixture->bearerCredential()))
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/organizations/'.($organizationId ?? $fixture->organizationId).'/events', $payload);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}

final readonly class IngestEventFixture
{
    public function __construct(
        public string $organizationId,
        public string $projectId,
        public string $applicationId,
        public string $environmentId,
        public string $eventSourceId,
        public string $credentialId,
        public string $secret,
    ) {}

    public function bearerCredential(): string
    {
        return 'esc_'.$this->credentialId.'.'.$this->secret;
    }
}
