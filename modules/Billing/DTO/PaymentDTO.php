<?php

declare(strict_types=1);

namespace Modules\Billing\DTO;

use Carbon\CarbonImmutable;
use Modules\Billing\Enums\PaymentMethod;

final readonly class PaymentDTO
{
    public function __construct(
        public int $dealId,
        public int $receivedByUserId,
        public float $amount,
        public PaymentMethod $method,
        public CarbonImmutable $paidAt,
        public ?string $comment = null,
        public ?string $nextPaymentDate = null,
    ) {}
}
