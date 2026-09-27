<?php

use Acme\Withdrawals\Actions\RequestWithdrawalAction;
use Acme\Withdrawals\DTOs\RequestWithdrawalDTO;
use Acme\Withdrawals\Enums\WithdrawalStatus;
use Acme\Withdrawals\Exceptions\InvalidWithdrawalAmountException;

it('creates a Withdrawal with the flat fee already discounted', function () {
    $withdrawal = (new RequestWithdrawalAction)->execute(new RequestWithdrawalDTO(
        recipientType: 'community',
        recipientId: 1,
        amount: 100.00,
        requestedBy: 42,
    ));

    expect($withdrawal->fee)->toEqual('2.49')
        ->and($withdrawal->net_amount)->toEqual('97.51')
        ->and($withdrawal->status)->toBe(WithdrawalStatus::Requested);
});

it('rejects a withdrawal that would not cover the flat fee', function () {
    (new RequestWithdrawalAction)->execute(new RequestWithdrawalDTO(
        recipientType: 'community',
        recipientId: 1,
        amount: 2.00,
        requestedBy: 42,
    ));
})->throws(InvalidWithdrawalAmountException::class);
