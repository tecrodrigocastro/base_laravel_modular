<?php

namespace Acme\Withdrawals\Actions;

use Acme\Withdrawals\DTOs\RequestWithdrawalDTO;
use Acme\Withdrawals\Enums\WithdrawalStatus;
use Acme\Withdrawals\Exceptions\InvalidWithdrawalAmountException;
use Acme\Withdrawals\Models\Withdrawal;

/**
 * Flat fee per withdrawal — a business rule meant to be tuned per project,
 * not the shape of the Action.
 */
final class RequestWithdrawalAction
{
    private const FEE = 2.49;

    public function execute(RequestWithdrawalDTO $dto): Withdrawal
    {
        if ($dto->amount <= self::FEE) {
            throw InvalidWithdrawalAmountException::belowFee($dto->amount, self::FEE);
        }

        return Withdrawal::create([
            'recipient_type' => $dto->recipientType,
            'recipient_id' => $dto->recipientId,
            'amount' => $dto->amount,
            'fee' => self::FEE,
            'net_amount' => round($dto->amount - self::FEE, 2),
            'status' => WithdrawalStatus::Requested,
            'requested_by' => $dto->requestedBy,
        ]);
    }
}
