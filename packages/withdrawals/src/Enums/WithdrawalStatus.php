<?php

namespace Acme\Withdrawals\Enums;

enum WithdrawalStatus: string
{
    case Requested = 'requested';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
}
