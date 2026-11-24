<?php

declare(strict_types=1);

namespace Modules\Deals\DTO;

readonly class DealItemDTO
{
    public function __construct(
        public int $serviceId,
        public int $quantity,
        public float $unitPrice,
    ) {}
}
