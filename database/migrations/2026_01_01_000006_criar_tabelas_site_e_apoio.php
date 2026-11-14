<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lista_espera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $table->foreignId('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->date('data_desejada');
            $table->enum('periodo_preferido', ['manha', 'tarde', 'qualquer'])->default('qualquer');
            $table->enum('status', ['aguardando', 'contatada', 'convertida', 'expirada'])->default('aguardando');
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->timestampTz('vaga_liberada_em')->nullable(); // destaque nos alertas quando um horário do dia é liberado
            $table->timestampsTz();
            $table->index(['status', 'data_desejada']);
        });

        Schema::create('log_atividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 50);
            $table->string('entidade_tipo', 100);
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->jsonb('dados_antes')->nullable();
            $table->jsonb('dados_depois')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['entidade_tipo', 'entidade_id']);
            $table->index('created_at');
        });

        Schema::create('galeria_fotos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->nullable();
            $table->enum('categoria', ['loiros', 'cortes', 'unhas', 'sobrancelhas', 'maquiagem', 'ambiente']);
            $table->string('caminho');
            $table->boolean('destaque')->default(false);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestampsTz();
        });

        Schema::create('depoimentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome_cliente');
            $table->text('texto');
            $table->unsignedTinyInteger('nota')->default(5);
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestampsTz();
        });

        Schema::create('configuracoes', function (Blueprint $table) {
            $table->string('chave')->primary();
            $table->text('valor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
        Schema::dropIfExists('depoimentos');
        Schema::dropIfExists('galeria_fotos');
        Schema::dropIfExists('log_atividades');
        Schema::dropIfExists('lista_espera');
    }
};
