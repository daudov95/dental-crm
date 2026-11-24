<?php

declare(strict_types=1);

namespace Modules\Scheduling\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Deals\Models\Deal;
use Modules\Identity\Enums\UserRole;
use Modules\Identity\Models\User;
use Modules\Patients\Models\Patient;
use Modules\Scheduling\Enums\AppointmentStatus;
use Modules\Scheduling\Models\Appointment;

final class SchedulingSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::orderBy('id')->get();
        $doctors = User::where('role', UserRole::Doctor)->orderBy('id')->get();
        $deals = Deal::all()->keyBy('title');

        if ($patients->count() < 5 || $doctors->isEmpty()) {
            return;
        }

        $firstDoctor = $doctors->first();
        $secondDoctor = $doctors->get(1) ?? $firstDoctor;

        $orthoDeal = $deals->get('Ортодонтическое лечение (металлические брекеты)');
        $consultDeal = $deals->get('Бесплатная консультация по имплантации');
        $prostheticsDeal = $deals->get('План протезирования (циркониевые коронки × 2)');

        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $appointments = [
            [
                'patient_id' => $patients->get(0)->id,
                'doctor_id' => $firstDoctor->id,
                'deal_id' => $orthoDeal?->id,
                'starts_at' => $yesterday->copy()->setTime(10, 0),
                'ends_at' => $yesterday->copy()->setTime(11, 30),
                'status' => AppointmentStatus::Completed,
                'notes' => 'Фиксация брекет-системы на верхнюю челюсть, даны рекомендации по уходу.',
            ],
            [
                'patient_id' => $patients->get(1)->id,
                'doctor_id' => $secondDoctor->id,
                'deal_id' => $consultDeal?->id,
                'starts_at' => $today->copy()->setTime(11, 0),
                'ends_at' => $today->copy()->setTime(11, 30),
                'status' => AppointmentStatus::Scheduled,
                'notes' => 'Первичная бесплатная консультация, направление на КТ.',
            ],
            [
                'patient_id' => $patients->get(4)->id,
                'doctor_id' => $secondDoctor->id,
                'deal_id' => $prostheticsDeal?->id,
                'starts_at' => $today->copy()->setTime(14, 0),
                'ends_at' => $today->copy()->setTime(15, 0),
                'status' => AppointmentStatus::Scheduled,
                'notes' => 'Снятие слепков под циркониевые коронки.',
            ],
            [
                'patient_id' => $patients->get(0)->id,
                'doctor_id' => $firstDoctor->id,
                'deal_id' => $orthoDeal?->id,
                'starts_at' => $tomorrow->copy()->setTime(10, 0),
                'ends_at' => $tomorrow->copy()->setTime(10, 30),
                'status' => AppointmentStatus::Scheduled,
                'notes' => 'Плановая коррекция и замена дуги (этап 2).',
            ],
            [
                'patient_id' => $patients->get(2)->id,
                'doctor_id' => $secondDoctor->id,
                'deal_id' => null,
                'starts_at' => $tomorrow->copy()->setTime(12, 0),
                'ends_at' => $tomorrow->copy()->setTime(13, 0),
                'status' => AppointmentStatus::Scheduled,
                'notes' => 'Контрольный осмотр после лечения кариеса.',
            ],
        ];

        foreach ($appointments as $item) {
            Appointment::updateOrCreate(
                [
                    'doctor_id' => $item['doctor_id'],
                    'starts_at' => $item['starts_at'],
                ],
                $item
            );
        }
    }
}
