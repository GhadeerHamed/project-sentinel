<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentinel\Event\Application\Command\RevokeEventSourceCredentialCommand;
use Sentinel\Event\Application\Handler\RevokeEventSourceCredentialHandler;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class RevokeEventSourceCredentialController extends Controller
{
    public function __invoke(Request $request, RevokeEventSourceCredentialHandler $handler, TenantContext $tenantContext): JsonResponse
    {
        $routeValues = ['project', 'application', 'environment', 'eventSource', 'credential'];
        $values = array_map(fn (string $key): mixed => $request->route($key), $routeValues);

        if (array_any($values, static fn (mixed $value): bool => ! is_string($value))) {
            abort(404);
        }

        $handler->handle(new RevokeEventSourceCredentialCommand(...$values));

        return response()->json(['data' => ['id' => $values[4], 'type' => 'event_source_credential', 'attributes' => ['status' => 'revoked']]], 200)
            ->header('X-Correlation-ID', $tenantContext->correlationId()->value());
    }
}
