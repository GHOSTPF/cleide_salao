<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profissionais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('nome');
            $table->string('apelido')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('foto')->nullable();
            $table->text('bio')->nullable();
            $table->string('cor_agenda', 7)->default('#B08D57');
            $table->decimal('percentual_comissao', 5, 2)->default(0);
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestampsTz();
        });

        Schema::create('categorias_servico', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->unsignedInteger('ordem')->default(0);
        });

        Schema::create('servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_servico_id')->constrained('categorias_servico')->restrictOnDelete();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->text('descricao')->nullable();
            $table->unsignedInteger('duracao_minutos');
            $table->unsignedInteger('intervalo_apos_minutos')->default(0);
            $table->decimal('preco', 10, 2)->default(0);
            $table->boolean('preco_a_partir_de')->default(false);
            $table->unsignedInteger('retorno_sugerido_dias')->nullable();
            $table->boolean('requer_avaliacao')->default(false);
            $table->string('foto')->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('exibir_no_site')->default(true);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestampsTz();
        });

        Schema::create('profissional_servico', function (Blueprint $table) {
            $table->foreignId('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $table->unsignedInteger('duracao_minutos')->nullable();
            $table->decimal('preco', 10, 2)->nullable();
            $table->primary(['profissional_id', 'servico_id']);
        });

        Schema::create('horarios_trabalho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 0 = domingo ... 6 = sábado
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->time('intervalo_inicio')->nullable();
            $table->time('intervalo_fim')->nullable();
            $table->index(['profissional_id', 'dia_semana']);
        });

        Schema::create('bloqueios_agenda', function (Blueprint $table) {
            $table->id();
            // null = salão inteiro fechado (feriado/fechamento)
            $table->foreignId('profissional_id')->nullable()->constrained('profissionais')->cascadeOnDelete();
            $table->timestampTz('inicio');
            $table->timestampTz('fim');
            $table->string('motivo')->nullable();
            $table->timestampsTz();
            $table->index(['profissional_id', 'inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueios_agenda');
        Schema::dropIfExists('horarios_trabalho');
        Schema::dropIfExists('profissional_servico');
        Schema::dropIfExists('servicos');
        Schema::dropIfExists('categorias_servico');
        Schema::dropIfExists('profissionais');
    }
};
