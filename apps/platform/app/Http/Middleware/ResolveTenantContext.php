<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\Authentication\AuthenticatedIdentityResolver;
use App\Infrastructure\Tenant\TenantContextResolutionFailed;
use App\Infrastructure\Tenant\TenantContextResolver;
use App\Infrastructure\Tenant\TenantContextStore;
use Closure;
use Illuminate\Http\Request;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\Organization\Domain\Exception\OrganizationNotFound;
use Sentinel\Shared\Domain\Identifier\InvalidResourceId;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenantContext
{
    public function __construct(
        private readonly AuthenticatedIdentityResolver $identityResolver,
        private readonly TenantContextResolver $tenantContextResolver,
        private readonly TenantContextStore $tenantContextStore,
        private readonly UuidV7Generator $uuidV7Generator,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $identity = $this->identityResolver->currentIdentity();

        if ($identity === null) {
            abort(401);
        }

        $organizationId = $request->route('organization');

        if (! is_string($organizationId)) {
            abort(404);
        }

        try {
            $tenantContext = $this->tenantContextResolver->resolve(
                identity: $identity,
                requestedOrganizationId: $organizationId,
                correlationId: $this->uuidV7Generator->generate(),
            );
        } catch (OrganizationNotFound|MembershipNotFound|TenantContextResolutionFailed|InvalidResourceId) {
            abort(404);
        }

        $this->tenantContextStore->set($tenantContext);
        $request->attributes->set('tenant_context', $tenantContext);

        return $next($request);
    }
}
