<?php

declare(strict_types=1);

namespace Modules\Patients\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Patients\Enums\PatientSource;
use Modules\Patients\Models\Patient;

class PatientsSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            [
                'full_name' => 'Смирнов Алексей Сергеевич',
                'phone' => '+7 (777) 777-11-22',
                'birth_date' => '1997-04-15',
                'source' => PatientSource::Instagram,
                'notes' => 'Интересуется установкой металлической брекет-системы в рассрочку.',
                'consent_given' => true,
            ],
            [
                'full_name' => 'Кузнецова Елена Андреевна',
                'phone' => '+7 (777) 777-33-44',
                'birth_date' => '1993-09-21',
                'source' => PatientSource::WhatsApp,
                'notes' => 'Записалась на бесплатную консультацию по имплантации.',
                'consent_given' => true,
            ],
            [
                'full_name' => 'Попов Дмитрий Викторович',
                'phone' => '+7 (777) 777-55-66',
                'birth_date' => '1988-12-03',
                'source' => PatientSource::TelegramBot,
                'notes' => 'Заявка из Telegram-бота на лечение кариеса и профгигиену.',
                'consent_given' => true,
            ],
            [
                'full_name' => 'Соколова Анна Михайловна',
                'phone' => '+7 (777) 777-77-88',
                'birth_date' => '2001-06-11',
                'source' => PatientSource::TwoGis,
                'notes' => 'Нашла клинику в 2ГИС, уточняла цены на керамические брекеты.',
                'consent_given' => false,
            ],
            [
                'full_name' => 'Лебедев Максим Олегович',
                'phone' => '+7 (777) 777-99-00',
                'birth_date' => '1984-02-28',
                'source' => PatientSource::Referral,
                'notes' => 'Пришел по рекомендации родственников, требуется протезирование.',
                'consent_given' => true,
            ],
            [
                'full_name' => 'Морозова Ольга Владимировна',
                'phone' => '+7 (777) 777-77-71',
                'birth_date' => '1995-11-19',
                'source' => PatientSource::Phone,
                'notes' => 'Острая боль, первичный звонок на ресепшен.',
                'consent_given' => true,
            ],
            [
                'full_name' => 'Волков Артём Игоревич',
                'phone' => '+7 (777) 777-88-91',
                'birth_date' => '1990-07-08',
                'source' => PatientSource::Instagram,
                'notes' => 'Запрашивал расчет на циркониевые коронки в Direct.',
                'consent_given' => true,
            ],
            [
                'full_name' => 'Новикова Татьяна Николаевна',
                'phone' => '+7 (777) 777-77-12',
                'birth_date' => '1999-03-14',
                'source' => PatientSource::WhatsApp,
                'notes' => 'Планирует профессиональную гигиену перед установкой брекетов.',
                'consent_given' => false,
            ],
        ];

        foreach ($patients as $item) {
            Patient::updateOrCreate(
                ['phone' => $item['phone']],
                $item
            );
        }
    }
}
