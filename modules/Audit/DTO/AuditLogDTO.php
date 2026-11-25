<?php

declare(strict_types=1);

namespace Modules\Audit\DTO;

use Modules\Audit\Enums\AuditAction;

final readonly class AuditLogDTO
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public AuditAction $action,
        public string $auditableType,
        public int $auditableId,
        public ?int $userId = null,
        public ?string $ipAddress = null,
        public array $metadata = [],
    ) {}
}
