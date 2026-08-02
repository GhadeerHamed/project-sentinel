<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Shared\Audit;

use App\Infrastructure\Persistence\Shared\Records\AuditRecordModel;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;

final class PgsqlAuditRecorder implements AuditRecorder
{
    public function record(AuditRecord $auditRecord): void
    {
        AuditRecordModel::query()->create($auditRecord->jsonSerialize());
    }
}
