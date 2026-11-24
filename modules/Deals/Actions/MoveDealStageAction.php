<?php

declare(strict_types=1);

namespace Modules\Deals\Actions;

use InvalidArgumentException;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Deals\Events\DealStageChanged;
use Modules\Deals\Models\Deal;

class MoveDealStageAction
{
    public function execute(
        Deal $deal,
        DealStatus $newStatus,
        ?LossReason $lossReason = null,
        ?int $actorId = null,
        ?string $ipAddress = null,
    ): Deal {
        if ($newStatus === DealStatus::Lost && $lossReason === null) {
            throw new InvalidArgumentException('Для статуса «Отказ» необходимо указать причину.');
        }

        $previousStatus = $deal->status;

        if ($previousStatus === $newStatus && $deal->loss_reason === $lossReason) {
            return $deal;
        }

        $deal->update([
            'status' => $newStatus,
            'loss_reason' => $newStatus === DealStatus::Lost ? $lossReason : null,
        ]);

        DealStageChanged::dispatch($deal, $previousStatus, $newStatus, $deal->loss_reason, $actorId, $ipAddress);

        return $deal->refresh();
    }
}
