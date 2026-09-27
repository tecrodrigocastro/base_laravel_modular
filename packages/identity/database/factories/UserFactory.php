<?php

namespace Acme\Identity\Database\Factories;

use Acme\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Resolve to whichever concrete class an app configured for the 'users'
     * auth provider (e.g. App\Models\User extends Acme\Identity\Models\User),
     * so User::factory() in either app creates the app's own subclass.
     */
    public function modelName(): string
    {
        return config('auth.providers.users.model') ?: User::class;
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => true,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}
