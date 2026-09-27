<?php

namespace Acme\Withdrawals\Adapters;

use Acme\Withdrawals\Contracts\WithdrawalGatewayContract;
use Acme\Withdrawals\Models\Withdrawal;

/**
 * Placeholder adapter: this template has no real payment gateway wired in.
 * A real project swaps the container binding in WithdrawalsServiceProvider
 * for an adapter that actually calls a gateway, without touching
 * ApproveWithdrawalAction or anything else that depends on
 * WithdrawalGatewayContract.
 */
final class LocalWithdrawalGatewayAdapter implements WithdrawalGatewayContract
{
    public function transfer(Withdrawal $withdrawal): void
    {
        // No-op on purpose — see the class docblock.
    }
}
