<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use Modules\Catalog\Models\Service;

class ToggleServiceActiveAction
{
    public function execute(Service $service): Service
    {
        $service->update(['is_active' => !$service->is_active]);

        return $service->refresh();
    }
}
