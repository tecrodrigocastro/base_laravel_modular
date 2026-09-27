<?php

namespace Acme\Withdrawals\DTOs;

final class RequestWithdrawalDTO
{
    public function __construct(
        public readonly string $recipientType,
        public readonly int $recipientId,
        public readonly float $amount,
        public readonly int $requestedBy,
    ) {}
}
