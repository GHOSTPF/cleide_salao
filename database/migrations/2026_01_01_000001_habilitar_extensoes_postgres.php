<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // btree_gist: permite "profissional_id WITH =" dentro da exclusion constraint (GiST)
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        // pg_trgm: similaridade de nomes para sugerir clientes duplicadas
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    }

    public function down(): void
    {
        // Extensões não são removidas: podem ser usadas por outros objetos do banco.
    }
};
