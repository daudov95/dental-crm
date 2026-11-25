<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use Modules\Catalog\DTO\ServiceDTO;
use Modules\Catalog\Models\Service;

class UpdateServiceAction
{
    public function execute(Service $service, ServiceDTO $dto): Service
    {
        $service->update([
            'name' => $dto->name,
            'category' => $dto->category,
            'price' => $dto->price,
            'duration_minutes' => $dto->durationMinutes,
            'is_active' => $dto->isActive,
        ]);

        return $service->refresh();
    }
}
