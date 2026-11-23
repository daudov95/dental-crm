<?php

declare(strict_types=1);

namespace Modules\Catalog\DTO;

use Modules\Catalog\Enums\ServiceCategory;

readonly class ServiceDTO
{
    public function __construct(
        public string $name,
        public ServiceCategory $category,
        public float $price,
        public int $durationMinutes,
        public bool $isActive = true,
    ) {}
}
