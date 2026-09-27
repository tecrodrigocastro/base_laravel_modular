<?php

use Acme\Withdrawals\Actions\ApproveWithdrawalAction;
use Acme\Withdrawals\Contracts\WithdrawalGatewayContract;
use Acme\Withdrawals\Enums\WithdrawalStatus;
use Acme\Withdrawals\Events\WithdrawalApprovedEvent;
use Acme\Withdrawals\Models\Withdrawal;

it('transfers through the bound gateway, marks the Withdrawal as paid and fires the event', function () {
    Event::fake();

    $gateway = Mockery::mock(WithdrawalGatewayContract::class);
    $gateway->shouldReceive('transfer')->once();
    app()->instance(WithdrawalGatewayContract::class, $gateway);

    $withdrawal = Withdrawal::create([
        'recipient_type' => 'community',
        'recipient_id' => 1,
        'amount' => 100.00,
        'fee' => 2.49,
        'net_amount' => 97.51,
        'status' => WithdrawalStatus::Requested,
        'requested_by' => 42,
    ]);

    app(ApproveWithdrawalAction::class)->execute($withdrawal);

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Paid);
    Event::assertDispatched(WithdrawalApprovedEvent::class, fn ($event) => $event->withdrawal->is($withdrawal));
});
