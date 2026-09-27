<?php

namespace Acme\Withdrawals\Events;

use Acme\Withdrawals\Models\Withdrawal;

final class WithdrawalApprovedEvent
{
    public function __construct(
        public readonly Withdrawal $withdrawal,
    ) {}
}
