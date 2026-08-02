<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Audit;

interface AuditRecorder
{
    public function record(AuditRecord $auditRecord): void;
}
