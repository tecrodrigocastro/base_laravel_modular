<?php

namespace Acme\Withdrawals\Contracts;

use Acme\Withdrawals\Models\Withdrawal;

/**
 * The seam between ApproveWithdrawalAction and whatever actually moves the
 * money. Today, LocalWithdrawalGatewayAdapter implements this against the
 * local database only. If withdrawals are ever extracted into their own
 * service, a RemoteWithdrawalGatewayAdapter implementing this same contract
 * is the only thing that changes — ApproveWithdrawalAction never does.
 */
interface WithdrawalGatewayContract
{
    public function transfer(Withdrawal $withdrawal): void;
}
