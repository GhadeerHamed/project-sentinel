<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Error;

enum ErrorCategory: string
{
    case VALIDATION_ERROR = 'validation_error';
    case CONFLICT = 'conflict';
    case NOT_FOUND = 'not_found';
    case FORBIDDEN = 'forbidden';
    case CONCURRENCY_CONFLICT = 'concurrency_conflict';
    case INVALID_STATE = 'invalid_state';
    case DEPENDENCY_UNAVAILABLE = 'dependency_unavailable';
}
