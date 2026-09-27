<?php

namespace Acme\Withdrawals\Providers;

use Acme\Withdrawals\Adapters\LocalWithdrawalGatewayAdapter;
use Acme\Withdrawals\Contracts\WithdrawalGatewayContract;
use Illuminate\Support\ServiceProvider;

class WithdrawalsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WithdrawalGatewayContract::class, LocalWithdrawalGatewayAdapter::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
