<?php

namespace App\Filament\Admin\Resources\Withdrawals\Schemas;

use Acme\Withdrawals\Enums\WithdrawalStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WithdrawalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('recipient_type')
                    ->required(),
                TextInput::make('recipient_id')
                    ->required()
                    ->numeric(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('fee')
                    ->required()
                    ->numeric(),
                TextInput::make('net_amount')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(WithdrawalStatus::class)
                    ->required(),
                TextInput::make('requested_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
