<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Sentinel\Project\Domain\Exception\ProjectNotFound;
use Sentinel\Project\Domain\Exception\ProjectSlugConflict;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Error\TypedError;
use Sentinel\Shared\Domain\Idempotency\IdempotencyKeyConflict;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ApiExceptionRenderer
{
    public function register(Exceptions $exceptions): void
    {
        $exceptions->render($this->renderValidationException(...));
        $exceptions->render($this->renderAuthenticationException(...));
        $exceptions->render($this->renderAuthorizationException(...));
        $exceptions->render($this->renderAccessDeniedHttpException(...));
        $exceptions->render($this->renderThrottleRequestsException(...));
        $exceptions->render($this->renderOptimisticConcurrencyConflict(...));
        $exceptions->render($this->renderProjectNotFound(...));
        $exceptions->render($this->renderProjectSlugConflict(...));
        $exceptions->render($this->renderIdempotencyKeyConflict(...));
        $exceptions->render($this->renderTypedError(...));
    }

    private function renderValidationException(ValidationException $exception, Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return $this->errorResponse(
            code: 'request.validation_failed',
            message: 'The request contains invalid fields.',
            status: 422,
            details: ['fields' => $exception->errors()],
        );
    }

    private function renderAuthenticationException(AuthenticationException $exception, Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return $this->errorResponse('authentication.required', 'Authentication is required.', 401);
    }

    private function renderAuthorizationException(AuthorizationException $exception, Request $request): ?JsonResponse
    {
        return $this->renderAuthorizationError($request);
    }

    private function renderAccessDeniedHttpException(AccessDeniedHttpException $exception, Request $request): ?JsonResponse
    {
        return $this->renderAuthorizationError($request);
    }

    private function renderAuthorizationError(Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return $this->errorResponse('authorization.denied', 'You are not authorized to perform this operation.', 403);
    }

    private function renderThrottleRequestsException(ThrottleRequestsException $exception, Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return $this->errorResponse('rate_limit.exceeded', 'Too many requests.', 429);
    }

    private function renderOptimisticConcurrencyConflict(OptimisticConcurrencyConflict $exception, Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return $this->errorResponse(
            code: 'concurrency.version_conflict',
            message: 'The resource was modified by another operation.',
            status: 412,
            details: [
                'expected_version' => $exception->expectedVersion()->value()?->value(),
                'current_version' => $exception->currentVersion()->value()?->value(),
            ],
        );
    }

    private function renderProjectSlugConflict(ProjectSlugConflict $exception, Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return $this->errorResponse('project.slug_conflict', 'The project slug is already in use by this organization.', 409, $exception->context());
    }

    private function renderProjectNotFound(ProjectNotFound $exception, Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return $this->errorResponse('project.not_found', 'The project is not available.', 404);
    }

    private function renderIdempotencyKeyConflict(IdempotencyKeyConflict $exception, Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return $this->errorResponse('idempotency.key_conflict', 'The idempotency key was already used with a different request.', 409, $exception->context());
    }

    private function renderTypedError(TypedError $exception, Request $request): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        [$status, $code, $message] = match ($exception->category()) {
            ErrorCategory::VALIDATION_ERROR => [422, 'request.validation_failed', 'The request contains invalid fields.'],
            ErrorCategory::CONFLICT => [409, 'request.conflict', 'The request conflicts with existing state.'],
            ErrorCategory::NOT_FOUND => [404, 'resource.not_found', 'The requested resource is not available.'],
            ErrorCategory::FORBIDDEN => [403, 'authorization.denied', 'You are not authorized to perform this operation.'],
            ErrorCategory::CONCURRENCY_CONFLICT => [412, 'concurrency.version_conflict', 'The resource was modified by another operation.'],
            ErrorCategory::INVALID_STATE => [422, 'request.invalid_state', 'The request conflicts with the current resource state.'],
            ErrorCategory::DEPENDENCY_UNAVAILABLE => [503, 'dependency.unavailable', 'A required dependency is unavailable.'],
        };

        return $this->errorResponse($code, $message, $status, $exception->context());
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*');
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    private function errorResponse(string $code, string $message, int $status, ?array $details = null): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null && $details !== []) {
            $error['details'] = $details;
        }

        return response()->json(['error' => $error], $status);
    }
}
