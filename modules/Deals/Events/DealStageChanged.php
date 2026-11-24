<?php

declare(strict_types=1);

namespace Modules\Deals\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Deals\Models\Deal;

class DealStageChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Deal $deal,
        public readonly DealStatus $fromStatus,
        public readonly DealStatus $toStatus,
        public readonly ?LossReason $lossReason = null,
        public readonly ?int $actorId = null,
        public readonly ?string $ipAddress = null,
    ) {}
}
