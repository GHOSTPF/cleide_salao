<?php

namespace Database\Factories;

use App\Enums\OrigemCliente;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->firstNameFemale() . ' ' . fake()->lastName(),
            'telefone' => '5583' . '9' . fake()->unique()->numerify('########'),
            'email' => fake()->optional(0.4)->safeEmail(),
            'data_nascimento' => fake()->optional(0.7)->dateTimeBetween('-60 years', '-16 years'),
            'instagram' => fake()->optional(0.3)->userName(),
            'origem' => fake()->randomElement(OrigemCliente::cases()),
            'consentimento_lgpd_em' => now(),
        ];
    }
}
