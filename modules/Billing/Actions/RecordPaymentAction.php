<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Billing\DTO\PaymentDTO;
use Modules\Billing\Events\PaymentRecorded;
use Modules\Billing\Models\Payment;
use Modules\Billing\Models\SystemAlert;
use Modules\Deals\Models\Deal;

final readonly class RecordPaymentAction
{
    public function execute(PaymentDTO $dto, ?int $actorId = null, ?string $ipAddress = null): Payment
    {
        if ($dto->amount <= 0) {
            throw new InvalidArgumentException('Сумма платежа должна быть больше нуля.');
        }

        $payment = DB::transaction(function () use ($dto): Payment {
            $deal = Deal::lockForUpdate()->findOrFail($dto->dealId);

            $payment = Payment::create([
                'deal_id' => $deal->id,
                'received_by_user_id' => $dto->receivedByUserId,
                'amount' => round($dto->amount, 2),
                'method' => $dto->method,
                'paid_at' => $dto->paidAt,
                'comment' => $dto->comment,
            ]);

            $totalPaid = (float) Payment::where('deal_id', $deal->id)->sum('amount');
            $remaining = round((float) $deal->total_amount - $totalPaid, 2);

            if ($remaining <= 0) {
                $deal->update([
                    'is_overdue' => false,
                    'next_payment_date' => null,
                ]);

                SystemAlert::where('type', 'overdue_installment')
                    ->where('entity_type', Deal::class)
                    ->where('entity_id', $deal->id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            } elseif ($deal->is_installment && $dto->nextPaymentDate !== null && $dto->nextPaymentDate !== '') {
                $nextDate = CarbonImmutable::parse($dto->nextPaymentDate)->startOfDay();
                $deal->update([
                    'next_payment_date' => $nextDate->toDateString(),
                    'is_overdue' => $nextDate->lt(CarbonImmutable::today()),
                ]);
            }

            return $payment;
        });

        PaymentRecorded::dispatch($payment, $actorId, $ipAddress);

        return $payment->load(['deal.patient', 'receiver']);
    }
}
