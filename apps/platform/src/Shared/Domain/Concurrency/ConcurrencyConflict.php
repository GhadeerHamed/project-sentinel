<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Concurrency;

use Throwable;

interface ConcurrencyConflict extends Throwable {}
