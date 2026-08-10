<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Event;

use App\Infrastructure\Event\Authentication\AuthenticateEventSourceBearer;
use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use App\Infrastructure\Persistence\Event\Records\EventSourceCredentialRecord;
use App\Infrastructure\Persistence\Event\Records\EventSourceRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Event\Application\Authentication\EventSourceAuthenticationFailed;
use Sentinel\Event\Application\Authentication\EventSourceBearerAuthenticator;
use Sentinel\Event\Application\Authentication\EventSourceCredentialIssuer;
use Sentinel\Event\Application\Authentication\EventSourceIdentity;
use Sentinel\Event\Application\Command\RegisterEventSourceCommand;
use Sentinel\Event\Application\Handler\RegisterEventSourceHandler;
use Sentinel\Event\Application\Policy\EventSourceRegistrationPolicy;
use Sentinel\Event\Application\Result\RegisterEventSourceResult;
use Sentinel\Event\Domain\Repository\EventSourceCredentialRepository;
use Sentinel\Event\Domain\Repository\EventSourceRepository;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRepository;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class EventSourceCredentialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_an_active_api_client_with_one_hashed_credential_and_audit_without_the_secret(): void
    {
        $fixture = $this->fixture();

        $result = $this->register($fixture);
        $credential = $result->issuedCredential()->credential();
        $bearerCredential = $result->issuedCredential()->bearerCredential();
        $secret = substr($bearerCredential, strrpos($bearerCredential, '.') + 1);

        $this->assertDatabaseHas('event_sources', [
            'id' => $result->eventSource()->id()->value(),
            'organization_id' => $fixture->organizationId,
            'project_id' => $fixture->projectId,
            'application_id' => $fixture->applicationId,
            'environment_id' => $fixture->environmentId,
            'source_type' => 'api_client',
            'status' => 'active',
            'version' => 1,
        ]);
        $this->assertDatabaseHas('event_source_credentials', [
            'id' => $credential->id()->value(),
            'organization_id' => $fixture->organizationId,
            'event_source_id' => $result->eventSource()->id()->value(),
            'status' => 'active',
            'revoked_at' => null,
            'expires_at' => null,
        ]);
        self::assertFalse(Schema::hasColumn('event_source_credentials', 'secret'));
        self::assertNotSame($secret, $credential->secretHash());
        self::assertTrue($credential->matchesSecret($secret));
        $this->assertDatabaseHas('audit_records', [
            'organization_id' => $fixture->organizationId,
            'action' => 'event_source.create',
            'resource_type' => 'event_source',
            'resource_id' => $result->eventSource()->id()->value(),
        ]);
        $this->assertDatabaseHas('audit_records', [
            'organization_id' => $fixture->organizationId,
            'action' => 'event_source.credential.issue',
            'resource_type' => 'event_source_credential',
            'resource_id' => $credential->id()->value(),
        ]);

        $auditJson = json_encode(
            DB::table('audit_records')->where('organization_id', $fixture->organizationId)->get(),
            JSON_THROW_ON_ERROR,
        );
        self::assertStringNotContainsString($bearerCredential, $auditJson);
        self::assertStringNotContainsString($secret, $auditJson);
        $this->assertDatabaseCount('outbox_messages', 0);
    }

    #[Test]
    public function an_active_credential_authenticates_only_its_event_source_scope_and_the_middleware_attaches_that_identity(): void
    {
        $fixture = $this->fixture();
        $result = $this->register($fixture);
        $bearerCredential = $result->issuedCredential()->bearerCredential();

        $identity = $this->authenticator()->authenticate(
            'Bearer '.$bearerCredential,
            OrganizationId::fromString($fixture->organizationId),
        );

        self::assertSame($result->eventSource()->id()->value(), $identity->eventSourceId()->value());
        self::assertSame($fixture->projectId, $identity->projectId()->value());
        self::assertSame($fixture->applicationId, $identity->applicationId()->value());
        self::assertSame($fixture->environmentId, $identity->environmentId()->value());

        $request = Request::create('/api/v1/organizations/'.$fixture->organizationId.'/events', 'POST');
        $request->headers->set('Authorization', 'Bearer '.$bearerCredential);
        $route = new Route(['POST'], '/api/v1/organizations/{organization}/events', static fn (): Response => new Response);
        $route->bind($request);
        $request->setRouteResolver(static fn (): Route => $route);

        $response = (new AuthenticateEventSourceBearer($this->authenticator()))->handle(
            $request,
            static fn (Request $authenticatedRequest): Response => new Response(
                (string) $authenticatedRequest->attributes->get(EventSourceIdentity::REQUEST_ATTRIBUTE)?->eventSourceId()->value(),
                204,
            ),
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertSame($result->eventSource()->id()->value(), $response->getContent());
    }

    #[Test]
    public function malformed_unknown_revoked_inactive_and_cross_organization_credentials_fail_authentication(): void
    {
        $fixture = $this->fixture();
        $result = $this->register($fixture);
        $bearerCredential = $result->issuedCredential()->bearerCredential();
        $authenticator = $this->authenticator();
        $organizationId = OrganizationId::fromString($fixture->organizationId);

        $this->assertAuthenticationFails(static fn (): EventSourceIdentity => $authenticator->authenticate('Bearer invalid', $organizationId));
        $this->assertAuthenticationFails(fn (): EventSourceIdentity => $authenticator->authenticate('Bearer esc_'.$this->uuid().'.'.str_repeat('a', 64), $organizationId));

        $otherOrganizationId = $this->organization($this->uuid());
        $this->assertAuthenticationFails(fn (): EventSourceIdentity => $authenticator->authenticate(
            'Bearer '.$bearerCredential,
            OrganizationId::fromString($otherOrganizationId),
        ));

        EventSourceCredentialRecord::query()
            ->where('id', $result->issuedCredential()->credential()->id()->value())
            ->update(['status' => 'revoked', 'revoked_at' => now()]);
        $this->assertAuthenticationFails(fn (): EventSourceIdentity => $authenticator->authenticate('Bearer '.$bearerCredential, $organizationId));

        EventSourceCredentialRecord::query()
            ->where('id', $result->issuedCredential()->credential()->id()->value())
            ->update(['status' => 'active', 'revoked_at' => null]);
        EventSourceRecord::query()
            ->where('id', $result->eventSource()->id()->value())
            ->update(['status' => 'inactive']);
        $this->assertAuthenticationFails(fn (): EventSourceIdentity => $authenticator->authenticate('Bearer '.$bearerCredential, $organizationId));
    }

    private function register(EventSourceCredentialFixture $fixture): RegisterEventSourceResult
    {
        return (new RegisterEventSourceHandler(
            eventSources: $this->app->make(EventSourceRepository::class),
            credentials: $this->app->make(EventSourceCredentialRepository::class),
            projects: $this->app->make(ProjectLookupQuery::class),
            applications: $this->app->make(ApplicationRepository::class),
            environments: $this->app->make(EnvironmentRepository::class),
            registrationPolicy: new AllowEventSourceRegistration,
            tenantContext: $fixture->tenantContext,
            idempotentOperation: new IdempotentOperation(
                $this->app->make(TransactionManager::class),
                $this->app->make(IdempotencyRepository::class),
                $this->app->make(UuidV7Generator::class),
                $this->app->make(Clock::class),
            ),
            uuidGenerator: $this->app->make(UuidV7Generator::class),
            credentialIssuer: new EventSourceCredentialIssuer,
            audit: $this->app->make(AuditRecorder::class),
        ))->handle(RegisterEventSourceCommand::fromValues(
            projectId: $fixture->projectId,
            applicationId: $fixture->applicationId,
            environmentId: $fixture->environmentId,
            name: 'Checkout API Client',
            idempotencyKey: 'event-source-credential-authentication',
        ));
    }

    private function authenticator(): EventSourceBearerAuthenticator
    {
        return $this->app->make(EventSourceBearerAuthenticator::class);
    }

    private function fixture(): EventSourceCredentialFixture
    {
        $organizationId = $this->organization($this->uuid());
        $identityId = $this->uuid();
        $projectId = $this->uuid();
        $applicationId = $this->uuid();
        $environmentId = $this->uuid();
        $now = now();

        ProjectRecord::query()->create(['id' => $projectId, 'organization_id' => $organizationId, 'name' => 'Event Project', 'slug' => 'event-project-'.$projectId, 'description' => null, 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        ApplicationRecord::query()->create(['id' => $applicationId, 'organization_id' => $organizationId, 'project_id' => $projectId, 'name' => 'Event Application', 'slug' => 'event-application-'.$applicationId, 'description' => null, 'criticality' => 'high', 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        EnvironmentRecord::query()->create(['id' => $environmentId, 'organization_id' => $organizationId, 'application_id' => $applicationId, 'name' => 'Production', 'slug' => 'production', 'environment_type' => 'production', 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);

        return new EventSourceCredentialFixture(
            organizationId: $organizationId,
            projectId: $projectId,
            applicationId: $applicationId,
            environmentId: $environmentId,
            tenantContext: TenantContext::fromValues($organizationId, $identityId, 'user', $this->uuid()),
        );
    }

    private function organization(string $identityId): string
    {
        $organizationId = $this->uuid();
        OrganizationRecord::query()->create([
            'id' => $organizationId,
            'name' => 'Event Organization',
            'slug' => 'event-organization-'.$organizationId,
            'timezone' => 'UTC',
            'created_by_identity_id' => $identityId,
            'status' => 'active',
            'version' => 1,
        ]);

        return $organizationId;
    }

    private function assertAuthenticationFails(\Closure $authenticate): void
    {
        try {
            $authenticate();
            self::fail('Expected Event Source authentication to fail.');
        } catch (EventSourceAuthenticationFailed) {
            self::assertTrue(true);
        }
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}

final readonly class EventSourceCredentialFixture
{
    public function __construct(
        public string $organizationId,
        public string $projectId,
        public string $applicationId,
        public string $environmentId,
        public TenantContext $tenantContext,
    ) {}
}

final class AllowEventSourceRegistration implements EventSourceRegistrationPolicy
{
    public function allows(TenantContext $tenantContext): bool
    {
        return true;
    }
}
