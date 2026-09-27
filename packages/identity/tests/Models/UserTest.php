<?php

use Acme\Identity\Models\User;

it('creates a user via the factory and hashes the password', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);

    expect($user->name)->toBe('Ada Lovelace')
        ->and($user->password)->not->toBe('password')
        ->and($user->status)->toBeTrue();
});
