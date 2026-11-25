<?php

declare(strict_types=1);

namespace Modules\Deals\DTO;

use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;

readonly class DealDTO
{
    /**
     * @param list<DealItemDTO> $items
     */
    public function __construct(
        public int $patientId,
        public ?int $doctorId,
        public string $title,
        public DealStatus $status,
        public ?LossReason $lossReason,
        public bool $isInstallment,
        public ?string $nextPaymentDate,
        public array $items = [],
    ) {}
}
