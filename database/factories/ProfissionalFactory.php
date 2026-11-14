<?php

namespace Database\Factories;

use App\Models\Profissional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profissional>
 */
class ProfissionalFactory extends Factory
{
    protected $model = Profissional::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->firstNameFemale() . ' ' . fake()->lastName(),
            'cor_agenda' => fake()->hexColor(),
            'percentual_comissao' => 40,
            'ativo' => true,
        ];
    }
}
