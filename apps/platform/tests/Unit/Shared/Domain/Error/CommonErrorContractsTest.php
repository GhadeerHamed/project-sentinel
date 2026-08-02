<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Error;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Error\ApplicationError;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\DomainError;
use Sentinel\Shared\Domain\Error\DomainRuleViolation;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Error\TypedError;

final class CommonErrorContractsTest extends TestCase
{
    #[Test]
    public function it_provides_a_typed_domain_error_with_immutable_context(): void
    {
        $error = DomainRuleViolation::forCategory(
            category: ErrorCategory::VALIDATION_ERROR,
            message: 'Validation failed.',
            context: ['field' => 'name', 'rule' => 'required'],
        );

        self::assertInstanceOf(DomainError::class, $error);
        self::assertInstanceOf(TypedError::class, $error);
        self::assertSame(ErrorCategory::VALIDATION_ERROR, $error->category());
        self::assertSame('validation_error', $error->category()->value);
        self::assertSame(['field' => 'name', 'rule' => 'required'], $error->context());

        $context = $error->context();
        $context['field'] = 'email';

        self::assertSame(['field' => 'name', 'rule' => 'required'], $error->context());
    }

    #[Test]
    public function it_provides_a_typed_application_error_with_mapping_ready_category(): void
    {
        $error = ApplicationOperationFailure::forCategory(
            category: ErrorCategory::DEPENDENCY_UNAVAILABLE,
            message: 'Secret store is unavailable.',
            context: ['dependency' => 'secret_store'],
        );

        self::assertInstanceOf(ApplicationError::class, $error);
        self::assertInstanceOf(TypedError::class, $error);
        self::assertSame(ErrorCategory::DEPENDENCY_UNAVAILABLE, $error->category());
        self::assertSame('dependency_unavailable', $error->category()->value);
        self::assertSame(['dependency' => 'secret_store'], $error->context());
    }

    #[Test]
    public function it_exposes_stable_shared_error_categories(): void
    {
        self::assertSame(
            [
                'validation_error',
                'conflict',
                'not_found',
                'forbidden',
                'concurrency_conflict',
                'invalid_state',
                'dependency_unavailable',
            ],
            array_map(
                static fn (ErrorCategory $category): string => $category->value,
                ErrorCategory::cases(),
            ),
        );
    }
}
