<?php

declare(strict_types=1);

namespace Modules\Billing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Billing\Actions\CheckOverdueInstallmentsAction;
use Modules\Billing\Actions\GenerateTomorrowRemindersAction;
use Modules\Billing\Enums\PaymentMethod;
use Modules\Billing\Models\Payment;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;

final class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', UserRole::Admin)->first()
            ?? User::where('role', UserRole::Director)->first();

        if (! $admin) {
            return;
        }

        $completedDeal = Deal::where('title', 'Лечение кариеса и профгигиена')->first();
        $orthoDeal = Deal::where('title', 'Ортодонтическое лечение (металлические брекеты)')->first();
        $prostheticsDeal = Deal::where('title', 'План протезирования (циркониевые коронки × 2)')->first();

        if ($completedDeal && ! Payment::where('deal_id', $completedDeal->id)->exists()) {
            Payment::create([
                'deal_id' => $completedDeal->id,
                'received_by_user_id' => $admin->id,
                'amount' => $completedDeal->total_amount,
                'method' => PaymentMethod::Card,
                'paid_at' => now()->subDays(3),
                'comment' => 'Полная оплата после завершения лечения.',
            ]);
        }

        if ($orthoDeal && ! Payment::where('deal_id', $orthoDeal->id)->exists()) {
            Payment::create([
                'deal_id' => $orthoDeal->id,
                'received_by_user_id' => $admin->id,
                'amount' => 35000,
                'method' => PaymentMethod::Transfer,
                'paid_at' => now()->subDays(14),
                'comment' => 'Первоначальный взнос по рассрочке (брекеты).',
            ]);

            Payment::create([
                'deal_id' => $orthoDeal->id,
                'received_by_user_id' => $admin->id,
                'amount' => 15000,
                'method' => PaymentMethod::Qr,
                'paid_at' => now()->subDays(2),
                'comment' => 'Второй платеж по графику рассрочки.',
            ]);
        }

        if ($prostheticsDeal) {
            if (! Payment::where('deal_id', $prostheticsDeal->id)->exists()) {
                Payment::create([
                    'deal_id' => $prostheticsDeal->id,
                    'received_by_user_id' => $admin->id,
                    'amount' => 10000,
                    'method' => PaymentMethod::Cash,
                    'paid_at' => now()->subDays(10),
                    'comment' => 'Аванс за изготовление циркониевых коронок.',
                ]);
            }

            // Делаем просроченную дату платежа для демонстрации алерта
            $prostheticsDeal->update([
                'is_installment' => true,
                'next_payment_date' => now()->subDays(2)->toDateString(),
            ]);
        }

        app(CheckOverdueInstallmentsAction::class)->execute();
        app(GenerateTomorrowRemindersAction::class)->execute();
    }
}
