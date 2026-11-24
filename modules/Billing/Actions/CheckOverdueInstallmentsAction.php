<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Carbon\CarbonImmutable;
use Modules\Billing\Models\Payment;
use Modules\Billing\Models\SystemAlert;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Deal;

final readonly class CheckOverdueInstallmentsAction
{
    public function execute(): int
    {
        $today = CarbonImmutable::today();
        $closedStatuses = array_map(fn (DealStatus $s): string => $s->value, DealStatus::closedCases());

        $deals = Deal::query()
            ->with('patient')
            ->where('is_installment', true)
            ->whereNotNull('next_payment_date')
            ->whereDate('next_payment_date', '<', $today->toDateString())
            ->whereNotIn('status', $closedStatuses)
            ->get();

        $alertsCreated = 0;

        foreach ($deals as $deal) {
            $paid = (float) Payment::where('deal_id', $deal->id)->sum('amount');
            $remaining = round((float) $deal->total_amount - $paid, 2);

            if ($remaining <= 0) {
                if ($deal->is_overdue) {
                    $deal->update(['is_overdue' => false]);
                }

                continue;
            }

            if (! $deal->is_overdue) {
                $deal->update(['is_overdue' => true]);
            }

            $alreadyExists = SystemAlert::query()
                ->where('type', 'overdue_installment')
                ->where('entity_type', Deal::class)
                ->where('entity_id', $deal->id)
                ->where('is_read', false)
                ->exists();

            if (! $alreadyExists) {
                $patientName = $deal->patient?->full_name ?? 'Пациент';
                $formattedDate = $deal->next_payment_date->format('d.m.Y');
                $formattedRemaining = number_format($remaining, 0, ',', ' ');

                SystemAlert::create([
                    'type' => 'overdue_installment',
                    'title' => "Просрочка рассрочки: {$patientName}",
                    'message' => "Сделка «{$deal->title}». Плановая дата платежа: {$formattedDate}. Остаток долга: {$formattedRemaining} ₽.",
                    'entity_type' => Deal::class,
                    'entity_id' => $deal->id,
                    'is_read' => false,
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }
}
