<?php

namespace Acme\Withdrawals\Exceptions;

use RuntimeException;

class InvalidWithdrawalAmountException extends RuntimeException
{
    public static function belowFee(float $amount, float $fee): self
    {
        return new self("Withdrawal amount ({$amount}) must be greater than the fee ({$fee}).");
    }
}
