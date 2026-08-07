<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Concurrency\CurrentVersion;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Tests\TestCase;

final class ApiExceptionRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/_testing/errors/validation', function (): never {
            throw ValidationException::withMessages(['name' => ['The name field is required.']]);
        });
        Route::get('/api/_testing/errors/authentication', function (): never {
            throw new AuthenticationException;
        });
        Route::get('/api/_testing/errors/authorization', function (): never {
            throw new AuthorizationException;
        });
        Route::get('/api/_testing/errors/throttled', function (): never {
            throw new ThrottleRequestsException;
        });
        Route::get('/api/_testing/errors/concurrency', function (): never {
            throw OptimisticConcurrencyConflict::forVersions(
                ExpectedVersion::fromInt(3),
                CurrentVersion::fromInt(4),
            );
        });
        Route::get('/api/_testing/errors/invalid-state', function (): never {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'An organization-specific message must not be rendered.',
                ['state' => 'suspended'],
            );
        });
    }

    #[Test]
    public function it_renders_validation_errors_in_the_api_envelope(): void
    {
        $this->getJson('/api/_testing/errors/validation')
            ->assertUnprocessable()
            ->assertExactJson([
                'error' => [
                    'code' => 'request.validation_failed',
                    'message' => 'The request contains invalid fields.',
                    'details' => ['fields' => ['name' => ['The name field is required.']]],
                ],
            ]);
    }

    #[Test]
    public function it_renders_authentication_and_authorization_errors_in_the_api_envelope(): void
    {
        $this->getJson('/api/_testing/errors/authentication')
            ->assertUnauthorized()
            ->assertExactJson([
                'error' => [
                    'code' => 'authentication.required',
                    'message' => 'Authentication is required.',
                ],
            ]);

        $this->getJson('/api/_testing/errors/authorization')
            ->assertForbidden()
            ->assertExactJson([
                'error' => [
                    'code' => 'authorization.denied',
                    'message' => 'You are not authorized to perform this operation.',
                ],
            ]);
    }

    #[Test]
    public function it_renders_rate_limit_errors_in_the_api_envelope(): void
    {
        $this->getJson('/api/_testing/errors/throttled')
            ->assertTooManyRequests()
            ->assertExactJson([
                'error' => [
                    'code' => 'rate_limit.exceeded',
                    'message' => 'Too many requests.',
                ],
            ]);
    }

    #[Test]
    public function it_uses_precondition_failed_for_optimistic_concurrency_conflicts(): void
    {
        $this->getJson('/api/_testing/errors/concurrency')
            ->assertStatus(412)
            ->assertExactJson([
                'error' => [
                    'code' => 'concurrency.version_conflict',
                    'message' => 'The resource was modified by another operation.',
                    'details' => [
                        'expected_version' => 3,
                        'current_version' => 4,
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_keeps_typed_invalid_state_errors_generic(): void
    {
        $this->getJson('/api/_testing/errors/invalid-state')
            ->assertUnprocessable()
            ->assertExactJson([
                'error' => [
                    'code' => 'request.invalid_state',
                    'message' => 'The request conflicts with the current resource state.',
                    'details' => ['state' => 'suspended'],
                ],
            ]);
    }
}
