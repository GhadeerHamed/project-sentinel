<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

use Sentinel\Shared\Domain\Identifier\ResourceId;

final readonly class IdempotencyResourceReference extends ResourceId {}
