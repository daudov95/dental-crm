<?php

declare(strict_types=1);

namespace Modules\Audit\Actions;

use Modules\Audit\DTO\AuditLogDTO;
use Modules\Audit\Models\AuditLog;

final readonly class RecordAuditLogAction
{
    public function execute(AuditLogDTO $dto): AuditLog
    {
        return AuditLog::create([
            'user_id' => $dto->userId,
            'action' => $dto->action,
            'auditable_type' => $dto->auditableType,
            'auditable_id' => $dto->auditableId,
            'ip_address' => $dto->ipAddress,
            'metadata' => $dto->metadata !== [] ? $dto->metadata : null,
        ]);
    }
}
