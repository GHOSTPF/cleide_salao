<?php

namespace Database\Seeders;

use App\Models\CategoriaServico;
use App\Models\Servico;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [];
        foreach (['Cabelo', 'Unhas', 'Sobrancelhas', 'Maquiagem'] as $i => $nome) {
            $categorias[$nome] = CategoriaServico::firstOrCreate(['nome' => $nome], ['ordem' => $i + 1]);
        }

        // Preços e durações são placeholders editáveis no painel
        $servicos = [
            ['Cabelo', 'Terapia dos Fios', 90, 15, 150, false, 30, false, 'Tratamento profundo de reconstrução e hidratação para devolver brilho, força e maciez aos fios.'],
            ['Cabelo', 'Corte e Escova', 60, 10, 90, false, 45, false, 'Corte feminino personalizado para o seu rosto e estilo, finalizado com escova modelada.'],
            ['Cabelo', 'Mechas Personalizadas', 240, 15, 350, true, 60, true, 'Nossa especialidade: loiros e descoloração com técnica, cuidado com a fibra e matização sob medida.'],
            ['Cabelo', 'Liso Premium', 180, 15, 250, true, 90, false, 'Alisamento com acabamento natural, movimento e redução de volume, sem agredir os fios.'],
            ['Unhas', 'Nails Spa', 60, 10, 70, false, 15, false, 'Cuidado completo de mãos e pés com esfoliação, hidratação e esmaltação impecável.'],
            ['Sobrancelhas', 'Design de Sobrancelhas e Epilação', 40, 5, 50, false, 20, false, 'Design que valoriza o seu olhar, respeitando o formato natural das sobrancelhas.'],
            ['Maquiagem', 'Maquiagem e Penteado', 90, 15, 220, true, null, false, 'Make e penteado para eventos, festas e ocasiões especiais, com acabamento duradouro.'],
        ];

        foreach ($servicos as $ordem => [$cat, $nome, $duracao, $intervalo, $preco, $aPartir, $retorno, $avaliacao, $descricao]) {
            Servico::updateOrCreate(['slug' => Str::slug($nome)], [
                'categoria_servico_id' => $categorias[$cat]->id,
                'nome' => $nome,
                'descricao' => $descricao,
                'duracao_minutos' => $duracao,
                'intervalo_apos_minutos' => $intervalo,
                'preco' => $preco,
                'preco_a_partir_de' => $aPartir,
                'retorno_sugerido_dias' => $retorno,
                'requer_avaliacao' => $avaliacao,
                'ativo' => true,
                'exibir_no_site' => true,
                'ordem' => $ordem + 1,
            ]);
        }
    }
}
