<?php

namespace Acme\Withdrawals\Models;

use Acme\Withdrawals\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $table = 'withdrawals';

    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'amount',
        'fee',
        'net_amount',
        'status',
        'requested_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'status' => WithdrawalStatus::class,
    ];
}
