<?php

declare(strict_types=1);

namespace Modules\Patients\Support;

final readonly class PhoneFormatter
{
    public const string PATTERN = '/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/';

    public static function normalize(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7' . substr($digits, 1);
        }

        if (strlen($digits) !== 11 || ! str_starts_with($digits, '7')) {
            return null;
        }

        return sprintf(
            '+7 (%s) %s-%s-%s',
            substr($digits, 1, 3),
            substr($digits, 4, 3),
            substr($digits, 7, 2),
            substr($digits, 9, 2),
        );
    }
}
