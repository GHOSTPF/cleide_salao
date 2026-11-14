<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nenhuma extensão é obrigatória: hospedagens compartilhadas (cPanel) costumam não ter o
        // pacote postgresql-contrib. A exclusion constraint de agendamentos usa apenas ranges
        // nativos (sem btree_gist) e a busca de duplicadas tem fallback em PHP sem pg_trgm.

        // pg_trgm (opcional): acelera a sugestão de clientes duplicadas.
        $this->tentarCriarExtensao('pg_trgm');
    }

    public function down(): void
    {
        // Extensões não são removidas: podem ser usadas por outros objetos do banco.
    }

    private function tentarCriarExtensao(string $nome): void
    {
        $disponivel = DB::selectOne('SELECT 1 FROM pg_available_extensions WHERE name = ?', [$nome]);
        if (! $disponivel) {
            return;
        }

        try {
            // Savepoint: se faltar permissão, não aborta a transação da migration.
            DB::transaction(fn () => DB::statement("CREATE EXTENSION IF NOT EXISTS {$nome}"));
        } catch (\Throwable) {
            // Sem permissão para criar a extensão: o sistema segue sem ela.
        }
    }
};
