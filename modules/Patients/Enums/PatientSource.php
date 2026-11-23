<?php

declare(strict_types=1);

namespace Modules\Patients\Enums;

enum PatientSource: string
{
    case Instagram = 'instagram';
    case WhatsApp = 'whatsapp';
    case TelegramBot = 'telegram_bot';
    case TwoGis = '2gis';
    case Referral = 'referral';
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::WhatsApp => 'WhatsApp',
            self::TelegramBot => 'Telegram-бот',
            self::TwoGis => '2ГИС',
            self::Referral => 'Рекомендация',
            self::Phone => 'Телефон',
        };
    }
}
