<?php

declare(strict_types=1);

namespace Modules\Deals\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Deals\DTO\DealDTO;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Events\DealCreated;
use Modules\Deals\Models\Deal;

class CreateDealAction
{
    public function execute(DealDTO $dto, ?int $actorId = null, ?string $ipAddress = null): Deal
    {
        if ($dto->status === DealStatus::Lost && $dto->lossReason === null) {
            throw new InvalidArgumentException('Для статуса «Отказ» необходимо указать причину.');
        }

        $totalAmount = $this->calculateTotal($dto);

        $deal = DB::transaction(function () use ($dto, $totalAmount): Deal {
            $deal = Deal::create([
                'patient_id' => $dto->patientId,
                'doctor_id' => $dto->doctorId,
                'title' => $dto->title,
                'status' => $dto->status,
                'loss_reason' => $dto->status === DealStatus::Lost ? $dto->lossReason : null,
                'total_amount' => $totalAmount,
                'is_installment' => $dto->isInstallment,
                'next_payment_date' => $dto->isInstallment ? $dto->nextPaymentDate : null,
                'is_overdue' => false,
            ]);

            foreach ($dto->items as $item) {
                $deal->items()->create([
                    'service_id' => $item->serviceId,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unitPrice,
                ]);
            }

            return $deal;
        });

        DealCreated::dispatch($deal, $actorId, $ipAddress);

        return $deal->load(['patient', 'doctor', 'items.service']);
    }

    private function calculateTotal(DealDTO $dto): float
    {
        $sum = 0.0;
        foreach ($dto->items as $item) {
            $sum += $item->unitPrice * $item->quantity;
        }

        return round($sum, 2);
    }
}
