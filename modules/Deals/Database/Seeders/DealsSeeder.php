<?php

declare(strict_types=1);

namespace Modules\Deals\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\Service;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Enums\LossReason;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;

class DealsSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::orderBy('id')->get();
        $doctors = User::where('role', UserRole::Doctor)->orderBy('id')->get();
        $services = Service::all()->keyBy('name');

        if ($patients->count() < 6 || $doctors->isEmpty() || $services->isEmpty()) {
            return;
        }

        $firstDoctor = $doctors->first();
        $secondDoctor = $doctors->get(1) ?? $firstDoctor;

        $consultation = $services->get('Первичная консультация и осмотр');
        $ctScan = $services->get('Компьютерная томография (КТ) челюсти');
        $caries = $services->get('Лечение кариеса (световая пломба)');
        $hygiene = $services->get('Профессиональная гигиена полости рта');
        $metalBraces = $services->get('Установка металлической брекет-системы (2 челюсти)');
        $ceramicBraces = $services->get('Установка керамической брекет-системы (2 челюсти)');
        $implant = $services->get('Установка импланта (хирургический этап)');
        $crown = $services->get('Коронка из диоксида циркония');

        $dealsData = [
            [
                'title' => 'Заявка на профгигиену перед брекетами',
                'patient' => $patients->get(7) ?? $patients->first(),
                'doctor' => null,
                'status' => DealStatus::New,
                'loss_reason' => null,
                'is_installment' => false,
                'next_payment_date' => null,
                'items' => array_filter([$hygiene ? ['service' => $hygiene, 'qty' => 1] : null]),
            ],
            [
                'title' => 'Бесплатная консультация по имплантации',
                'patient' => $patients->get(1),
                'doctor' => $secondDoctor,
                'status' => DealStatus::Consultation,
                'loss_reason' => null,
                'is_installment' => false,
                'next_payment_date' => null,
                'items' => array_filter([
                    $consultation ? ['service' => $consultation, 'qty' => 1] : null,
                    $ctScan ? ['service' => $ctScan, 'qty' => 1] : null,
                ]),
            ],
            [
                'title' => 'План протезирования (циркониевые коронки × 2)',
                'patient' => $patients->get(4),
                'doctor' => $secondDoctor,
                'status' => DealStatus::TreatmentPlan,
                'loss_reason' => null,
                'is_installment' => true,
                'next_payment_date' => now()->addDays(10)->toDateString(),
                'items' => array_filter([$crown ? ['service' => $crown, 'qty' => 2] : null]),
            ],
            [
                'title' => 'Ортодонтическое лечение (металлические брекеты)',
                'patient' => $patients->get(0),
                'doctor' => $firstDoctor,
                'status' => DealStatus::InProgress,
                'loss_reason' => null,
                'is_installment' => true,
                'next_payment_date' => now()->addDays(5)->toDateString(),
                'items' => array_filter([
                    $metalBraces ? ['service' => $metalBraces, 'qty' => 1] : null,
                    $ctScan ? ['service' => $ctScan, 'qty' => 1] : null,
                ]),
            ],
            [
                'title' => 'Лечение кариеса и профгигиена',
                'patient' => $patients->get(2),
                'doctor' => $secondDoctor,
                'status' => DealStatus::Completed,
                'loss_reason' => null,
                'is_installment' => false,
                'next_payment_date' => null,
                'items' => array_filter([
                    $caries ? ['service' => $caries, 'qty' => 2] : null,
                    $hygiene ? ['service' => $hygiene, 'qty' => 1] : null,
                ]),
            ],
            [
                'title' => 'Керамические брекеты (запрос из 2ГИС)',
                'patient' => $patients->get(3),
                'doctor' => $firstDoctor,
                'status' => DealStatus::Lost,
                'loss_reason' => LossReason::HighPrice,
                'is_installment' => false,
                'next_payment_date' => null,
                'items' => array_filter([$ceramicBraces ? ['service' => $ceramicBraces, 'qty' => 1] : null]),
            ],
        ];

        foreach ($dealsData as $data) {
            $total = 0.0;
            foreach ($data['items'] as $item) {
                $total += (float) $item['service']->price * $item['qty'];
            }

            $deal = Deal::updateOrCreate(
                ['title' => $data['title']],
                [
                    'patient_id' => $data['patient']->id,
                    'doctor_id' => $data['doctor']?->id,
                    'status' => $data['status'],
                    'loss_reason' => $data['loss_reason'],
                    'total_amount' => round($total, 2),
                    'is_installment' => $data['is_installment'],
                    'next_payment_date' => $data['next_payment_date'],
                    'is_overdue' => false,
                ]
            );

            $deal->items()->delete();
            foreach ($data['items'] as $item) {
                $deal->items()->create([
                    'service_id' => $item['service']->id,
                    'quantity' => $item['qty'],
                    'unit_price' => (float) $item['service']->price,
                ]);
            }
        }
    }
}
