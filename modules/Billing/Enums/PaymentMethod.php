<?php

declare(strict_types=1);

namespace Modules\Billing\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Qr = 'qr';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Наличные',
            self::Card => 'Банковская карта',
            self::Qr => 'QR-код',
            self::Transfer => 'Перевод',
        };
    }
}
