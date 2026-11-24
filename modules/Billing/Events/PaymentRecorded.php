<?php

declare(strict_types=1);

namespace Modules\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\Payment;

final readonly class PaymentRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Payment $payment,
        public ?int $actorId = null,
        public ?string $ipAddress = null,
    ) {}
}
