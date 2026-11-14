<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('telefone', 20)->unique(); // só dígitos, com DDI 55
            $table->string('email')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('instagram')->nullable();
            $table->text('observacoes')->nullable();
            $table->enum('origem', ['site', 'balcao', 'whatsapp', 'indicacao'])->default('site');
            $table->timestampTz('consentimento_lgpd_em')->nullable();
            // Colunas desnormalizadas (fonte da verdade: agendamentos + pagamentos)
            $table->timestampTz('primeira_visita_em')->nullable();
            $table->timestampTz('ultima_visita_em')->nullable();
            $table->unsignedInteger('total_visitas')->default(0);
            $table->decimal('total_gasto', 10, 2)->default(0);
            $table->unsignedInteger('total_faltas')->default(0);
            $table->unsignedInteger('total_cancelamentos')->default(0);
            $table->unsignedInteger('total_reagendamentos')->default(0);
            $table->boolean('bloqueada_online')->default(false);
            $table->string('motivo_bloqueio')->nullable();
            $table->foreignId('profissional_preferida_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->foreignId('mesclada_em_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('ultima_visita_em');
        });

        DB::statement('CREATE INDEX clientes_nome_trgm ON clientes USING gin (nome gin_trgm_ops)');

        Schema::create('clientes_duplicidade_ignorada', function (Blueprint $table) {
            $table->foreignId('cliente_a_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('cliente_b_id')->constrained('clientes')->cascadeOnDelete();
            $table->timestampTz('created_at')->nullable();
            $table->primary(['cliente_a_id', 'cliente_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_duplicidade_ignorada');
        Schema::dropIfExists('clientes');
    }
};
