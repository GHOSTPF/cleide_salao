<?php

namespace Database\Seeders;

use App\Support\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::PADROES as $chave => $valor) {
            // Não sobrescreve o que já foi alterado no painel
            DB::table('configuracoes')->insertOrIgnore(['chave' => $chave, 'valor' => $valor]);
        }

        Setting::limparCache();
    }
}
