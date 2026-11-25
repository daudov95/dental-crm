<?php

declare(strict_types=1);

namespace Modules\Deals\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Events\DealStageChanged;
use Modules\Deals\Models\Deal;

class ReorderDealsAction
{
    /**
     * @param list<int> $orderedIds
     */
    public function execute(
        DealStatus $targetStatus,
        array $orderedIds,
        ?int $actorId = null,
        ?string $ipAddress = null,
    ): void {
        if ($orderedIds === []) {
            return;
        }

        $stageChanges = [];

        DB::transaction(function () use ($targetStatus, $orderedIds, &$stageChanges): void {
            $deals = Deal::whereIn('id', $orderedIds)->get()->keyBy('id');

            foreach (array_values($orderedIds) as $index => $dealId) {
                $deal = $deals->get($dealId);
                if (! $deal) {
                    continue;
                }

                $previousStatus = $deal->status;
                $statusChanged = $previousStatus !== $targetStatus;

                if ($statusChanged && $targetStatus === DealStatus::Lost) {
                    throw new InvalidArgumentException('Для статуса «Отказ» необходимо указать причину.');
                }

                $updateData = ['sort_order' => $index + 1];

                if ($statusChanged) {
                    $updateData['status'] = $targetStatus;
                    $updateData['loss_reason'] = null;
                    $stageChanges[] = [
                        'deal' => $deal,
                        'from' => $previousStatus,
                        'to' => $targetStatus,
                    ];
                }

                $deal->update($updateData);
            }
        });

        foreach ($stageChanges as $change) {
            DealStageChanged::dispatch(
                $change['deal']->refresh(),
                $change['from'],
                $change['to'],
                null,
                $actorId,
                $ipAddress,
            );
        }
    }
}
