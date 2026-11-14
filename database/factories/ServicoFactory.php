<?php

namespace Database\Factories;

use App\Models\CategoriaServico;
use App\Models\Servico;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Servico>
 */
class ServicoFactory extends Factory
{
    protected $model = Servico::class;

    public function definition(): array
    {
        $nome = fake()->unique()->words(2, true);

        return [
            'categoria_servico_id' => fn () => CategoriaServico::firstOrCreate(['nome' => 'Cabelo'], ['ordem' => 1])->id,
            'nome' => ucfirst($nome),
            'slug' => Str::slug($nome) . '-' . Str::random(4),
            'duracao_minutos' => 60,
            'intervalo_apos_minutos' => 0,
            'preco' => 100,
            'ativo' => true,
            'exibir_no_site' => true,
        ];
    }
}
