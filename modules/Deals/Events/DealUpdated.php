<?php

declare(strict_types=1);

namespace Modules\Deals\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Deals\Models\Deal;

class DealUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $changes
     */
    public function __construct(
        public readonly Deal $deal,
        public readonly array $changes,
        public readonly ?int $actorId = null,
        public readonly ?string $ipAddress = null,
    ) {}
}
