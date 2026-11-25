<?php

declare(strict_types=1);

namespace Modules\Analytics\DTO;

use Carbon\CarbonImmutable;

final readonly class AnalyticsPeriodDTO
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {}

    public static function fromStrings(string $dateFrom, string $dateTo): self
    {
        $from = CarbonImmutable::parse($dateFrom)->startOfDay();
        $to = CarbonImmutable::parse($dateTo)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return new self($from, $to);
    }
}
