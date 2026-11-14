<?php

namespace Database\Factories;

use App\Enums\PapelUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'papel' => PapelUsuario::Admin,
            'ativo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['papel' => PapelUsuario::Admin]);
    }

    public function profissional(): static
    {
        return $this->state(fn () => ['papel' => PapelUsuario::Profissional]);
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['ativo' => false]);
    }
}
