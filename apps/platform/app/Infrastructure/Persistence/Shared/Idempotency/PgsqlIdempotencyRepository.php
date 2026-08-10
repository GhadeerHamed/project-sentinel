<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Shared\Idempotency;

use App\Infrastructure\Persistence\Shared\Records\IdempotencyRecordModel;
use DateTimeImmutable;
use LogicException;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRecord;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRecordId;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRepository;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRequest;
use Sentinel\Shared\Domain\Idempotency\IdempotencyReservation;
use Sentinel\Shared\Domain\Idempotency\IdempotencyResponse;

final class PgsqlIdempotencyRepository implements IdempotencyRepository
{
    public function reserve(IdempotencyRequest $request, IdempotencyRecordId $recordId, DateTimeImmutable $at): IdempotencyReservation
    {
        $inserted = IdempotencyRecordModel::query()->insertOrIgnore([
            'id' => $recordId->value(),
            'organization_id' => $request->organizationId()->value(),
            'identity_id' => $request->identityId()->value(),
            'operation' => $request->operation(),
            'idempotency_key' => $request->key(),
            'request_hash' => $request->requestHash(),
            'status' => 'processing',
            'created_at' => $at,
        ]);
        $record = IdempotencyRecordModel::query()
            ->where('organization_id', $request->organizationId()->value())
            ->where('identity_id', $request->identityId()->value())
            ->where('operation', $request->operation())
            ->where('idempotency_key', $request->key())
            ->lockForUpdate()
            ->first();

        if ($record === null) {
            throw new LogicException('The idempotency record could not be reserved.');
        }

        return new IdempotencyReservation($this->toDomain($record), $inserted === 1);
    }

    public function complete(IdempotencyRecordId $recordId, IdempotencyResponse $response, DateTimeImmutable $completedAt): void
    {
        $completed = IdempotencyRecordModel::query()
            ->whereKey($recordId->value())
            ->where('status', 'processing')
            ->update([
                'status' => 'completed',
                'response_status' => $response->status(),
                'response_reference' => $response->resourceReference()->value(),
                'completed_at' => $completedAt,
            ]);

        if ($completed !== 1) {
            throw new LogicException('The idempotency record could not be completed.');
        }
    }

    private function toDomain(IdempotencyRecordModel $record): IdempotencyRecord
    {
        return IdempotencyRecord::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            identityId: $record->identity_id,
            operation: $record->operation,
            key: $record->idempotency_key,
            requestHash: $record->request_hash,
            status: $record->status,
            responseStatus: $record->response_status,
            responseReference: $record->response_reference,
            createdAt: $record->created_at,
            completedAt: $record->completed_at,
        );
    }
}
