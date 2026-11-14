<?php

namespace Database\Seeders;

use App\Models\Depoimento;
use App\Models\GaleriaFoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        // Estrutura para as fotos reais enviadas pelo painel (Site → Galeria)
        Storage::disk('public')->makeDirectory('galeria');

        if (GaleriaFoto::count() === 0) {
            $fotos = [
                ['loiros', 'Loiro iluminado', true], ['loiros', 'Mechas personalizadas', true],
                ['cortes', 'Corte com movimento', true], ['unhas', 'Nails Spa', false],
                ['sobrancelhas', 'Design de sobrancelhas', false], ['maquiagem', 'Make para evento', true],
                ['ambiente', 'Nosso espaço', false], ['loiros', 'Platinado', false],
            ];
            foreach ($fotos as $i => [$cat, $titulo, $destaque]) {
                GaleriaFoto::create([
                    'titulo' => $titulo, 'categoria' => $cat, 'destaque' => $destaque, 'ordem' => $i + 1,
                    'caminho' => '/images/placeholders/' . $cat . '.svg',
                ]);
            }
        }

        if (Depoimento::count() === 0) {
            $depoimentos = [
                ['Mariana C.', 'Saí apaixonada pelo meu loiro! Atendimento cuidadoso do começo ao fim.', 5],
                ['Juliana R.', 'A Cleide entende exatamente o que a gente quer. Meu cabelo nunca esteve tão saudável.', 5],
                ['Fernanda A.', 'Ambiente lindo, aconchegante e um atendimento impecável. Super recomendo!', 5],
            ];
            foreach ($depoimentos as $i => [$nome, $texto, $nota]) {
                Depoimento::create(['nome_cliente' => $nome, 'texto' => $texto, 'nota' => $nota, 'ativo' => true, 'ordem' => $i + 1]);
            }
        }
    }
}
