<?php

declare(strict_types=1);

namespace Modules\Billing\Policies;

use Modules\Billing\Models\Payment;
use Modules\Identity\Models\User;

final readonly class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaffManager();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isStaffManager();
    }

    public function create(User $user): bool
    {
        return $user->isStaffManager();
    }

    public function manageAlerts(User $user): bool
    {
        return $user->isStaffManager();
    }
}
