<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Authentication\ActiveIdentityUserProvider;
use App\Infrastructure\Identifier\LaravelUuidV7Generator;
use App\Infrastructure\IdentityAccess\Policy\ActiveMembershipWithPermissionMayAssignRolePolicy;
use App\Infrastructure\IdentityAccess\Policy\ActiveMembershipWithPermissionMayInviteMembershipPolicy;
use App\Infrastructure\IdentityAccess\Policy\ActiveMembershipWithPermissionMayRemoveMembershipPolicy;
use App\Infrastructure\IdentityAccess\Policy\ActiveMembershipWithPermissionMayRevokeRolePolicy;
use App\Infrastructure\IdentityAccess\Policy\ActiveMembershipWithPermissionMaySuspendMembershipPolicy;
use App\Infrastructure\IdentityAccess\Policy\ActiveMembershipWithPermissionMayUpdateRolePermissionsPolicy;
use App\Infrastructure\Organization\Policy\ActiveHumanUserMayCreateOrganizationPolicy;
use App\Infrastructure\Organization\Policy\ActiveMembershipWithPermissionMaySuspendOrganizationPolicy;
use App\Infrastructure\Organization\Policy\ActiveMembershipWithPermissionMayUpdateOrganizationPolicy;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlAdministrativeAccessRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlMembershipRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlOrganizationRoleRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlPermissionAssignmentRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlRoleAssignmentRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlRoleProvisioningRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlUserRepository;
use App\Infrastructure\Persistence\Organization\Repositories\PgsqlOrganizationRepository;
use App\Infrastructure\Persistence\Project\Queries\PgsqlProjectListQuery;
use App\Infrastructure\Persistence\Project\Queries\PgsqlProjectLookupQuery;
use App\Infrastructure\Persistence\Project\Repositories\PgsqlProjectRepository;
use App\Infrastructure\Persistence\Shared\Audit\PgsqlAuditRecorder;
use App\Infrastructure\Persistence\Shared\Idempotency\PgsqlIdempotencyRepository;
use App\Infrastructure\Persistence\Shared\Outbox\PgsqlOutboxMessageStore;
use App\Infrastructure\Project\Policy\ActiveMembershipWithPermissionMayArchiveProjectPolicy;
use App\Infrastructure\Project\Policy\ActiveMembershipWithPermissionMayCreateProjectPolicy;
use App\Infrastructure\Project\Policy\ActiveMembershipWithPermissionMayGetProjectPolicy;
use App\Infrastructure\Project\Policy\ActiveMembershipWithPermissionMayListProjectsPolicy;
use App\Infrastructure\Shared\Transaction\LaravelTransactionManager;
use App\Infrastructure\Tenant\TenantContextStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Sentinel\IdentityAccess\Application\Policy\MembershipInvitationPolicy;
use Sentinel\IdentityAccess\Application\Policy\MembershipRemovalPolicy;
use Sentinel\IdentityAccess\Application\Policy\MembershipSuspensionPolicy;
use Sentinel\IdentityAccess\Application\Policy\RoleAssignmentPolicy;
use Sentinel\IdentityAccess\Application\Policy\RolePermissionUpdatePolicy;
use Sentinel\IdentityAccess\Application\Policy\RoleRevocationPolicy;
use Sentinel\IdentityAccess\Domain\Repository\AdministrativeAccessRepository;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\OrganizationRoleRepository;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleAssignmentRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleProvisioningRepository;
use Sentinel\IdentityAccess\Domain\Repository\UserRepository;
use Sentinel\Organization\Application\Policy\OrganizationCreationPolicy;
use Sentinel\Organization\Application\Policy\OrganizationSuspensionPolicy;
use Sentinel\Organization\Application\Policy\OrganizationUpdatePolicy;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Project\Application\Policy\ProjectArchivalPolicy;
use Sentinel\Project\Application\Policy\ProjectCreationPolicy;
use Sentinel\Project\Application\Policy\ProjectListingPolicy;
use Sentinel\Project\Application\Policy\ProjectRetrievalPolicy;
use Sentinel\Project\Application\Query\ProjectListQuery;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\Repository\ProjectRepository;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRepository;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Time\SystemClock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            UuidV7Generator::class,
            LaravelUuidV7Generator::class,
        );

        $this->app->singleton(
            Clock::class,
            SystemClock::class,
        );

        $this->app->singleton(
            TransactionManager::class,
            LaravelTransactionManager::class,
        );

        $this->app->singleton(
            UserRepository::class,
            PgsqlUserRepository::class,
        );

        $this->app->singleton(
            OrganizationRepository::class,
            PgsqlOrganizationRepository::class,
        );

        $this->app->singleton(ProjectRepository::class, PgsqlProjectRepository::class);
        $this->app->singleton(ProjectListQuery::class, PgsqlProjectListQuery::class);
        $this->app->singleton(ProjectLookupQuery::class, PgsqlProjectLookupQuery::class);

        $this->app->singleton(IdempotencyRepository::class, PgsqlIdempotencyRepository::class);

        $this->app->singleton(
            MembershipRepository::class,
            PgsqlMembershipRepository::class,
        );

        $this->app->singleton(
            AdministrativeAccessRepository::class,
            PgsqlAdministrativeAccessRepository::class,
        );

        $this->app->singleton(
            RoleProvisioningRepository::class,
            PgsqlRoleProvisioningRepository::class,
        );

        $this->app->singleton(
            RoleAssignmentRepository::class,
            PgsqlRoleAssignmentRepository::class,
        );

        $this->app->singleton(
            OrganizationRoleRepository::class,
            PgsqlOrganizationRoleRepository::class,
        );

        $this->app->singleton(PermissionAssignmentRepository::class, PgsqlPermissionAssignmentRepository::class);

        $this->app->singleton(
            OutboxMessageStore::class,
            PgsqlOutboxMessageStore::class,
        );

        $this->app->singleton(
            AuditRecorder::class,
            PgsqlAuditRecorder::class,
        );

        $this->app->singleton(
            OrganizationCreationPolicy::class,
            ActiveHumanUserMayCreateOrganizationPolicy::class,
        );

        $this->app->singleton(OrganizationUpdatePolicy::class, ActiveMembershipWithPermissionMayUpdateOrganizationPolicy::class);
        $this->app->singleton(OrganizationSuspensionPolicy::class, ActiveMembershipWithPermissionMaySuspendOrganizationPolicy::class);
        $this->app->singleton(ProjectCreationPolicy::class, ActiveMembershipWithPermissionMayCreateProjectPolicy::class);
        $this->app->singleton(ProjectArchivalPolicy::class, ActiveMembershipWithPermissionMayArchiveProjectPolicy::class);
        $this->app->singleton(ProjectListingPolicy::class, ActiveMembershipWithPermissionMayListProjectsPolicy::class);
        $this->app->singleton(ProjectRetrievalPolicy::class, ActiveMembershipWithPermissionMayGetProjectPolicy::class);
        $this->app->singleton(MembershipInvitationPolicy::class, ActiveMembershipWithPermissionMayInviteMembershipPolicy::class);
        $this->app->singleton(MembershipRemovalPolicy::class, ActiveMembershipWithPermissionMayRemoveMembershipPolicy::class);
        $this->app->singleton(MembershipSuspensionPolicy::class, ActiveMembershipWithPermissionMaySuspendMembershipPolicy::class);
        $this->app->singleton(RoleAssignmentPolicy::class, ActiveMembershipWithPermissionMayAssignRolePolicy::class);
        $this->app->singleton(RoleRevocationPolicy::class, ActiveMembershipWithPermissionMayRevokeRolePolicy::class);
        $this->app->singleton(RolePermissionUpdatePolicy::class, ActiveMembershipWithPermissionMayUpdateRolePermissionsPolicy::class);

        $this->app->scoped(TenantContextStore::class);

        $this->app->scoped(TenantContext::class, function ($app): TenantContext {
            return $app->make(TenantContextStore::class)->tenantContext();
        });
    }

    public function boot(): void
    {
        Auth::provider('identity_users', function ($app, array $config): ActiveIdentityUserProvider {
            return new ActiveIdentityUserProvider(
                hasher: $app['hash'],
                model: $config['model'],
            );
        });
    }
}
