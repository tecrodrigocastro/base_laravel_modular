<?php

namespace Acme\Withdrawals\Actions;

use Acme\Withdrawals\Contracts\WithdrawalGatewayContract;
use Acme\Withdrawals\Enums\WithdrawalStatus;
use Acme\Withdrawals\Events\WithdrawalApprovedEvent;
use Acme\Withdrawals\Models\Withdrawal;

final class ApproveWithdrawalAction
{
    public function __construct(
        private readonly WithdrawalGatewayContract $gateway,
    ) {}

    public function execute(Withdrawal $withdrawal): Withdrawal
    {
        $this->gateway->transfer($withdrawal);

        $withdrawal->update(['status' => WithdrawalStatus::Paid]);

        event(new WithdrawalApprovedEvent($withdrawal));

        return $withdrawal;
    }
}
