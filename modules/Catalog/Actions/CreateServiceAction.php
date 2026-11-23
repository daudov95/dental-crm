<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use Modules\Catalog\DTO\ServiceDTO;
use Modules\Catalog\Models\Service;

class CreateServiceAction
{
    public function execute(ServiceDTO $dto): Service
    {
        return Service::create([
            'name' => $dto->name,
            'category' => $dto->category,
            'price' => $dto->price,
            'duration_minutes' => $dto->durationMinutes,
            'is_active' => $dto->isActive,
        ]);
    }
}
