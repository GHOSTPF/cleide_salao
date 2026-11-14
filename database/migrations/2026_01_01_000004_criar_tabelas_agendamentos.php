<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('token_publico', 40)->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('profissional_id')->constrained('profissionais')->restrictOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->restrictOnDelete();
            $table->string('nome_informado')->nullable();
            $table->boolean('nome_divergente')->default(false);
            $table->timestampTz('inicio');
            $table->timestampTz('fim');
            $table->timestampTz('fim_bloqueio');
            $table->enum('status', ['pendente', 'confirmado', 'concluido', 'cancelado', 'nao_compareceu', 'reagendado'])->default('pendente');
            $table->enum('origem', ['site', 'painel', 'whatsapp'])->default('site');
            $table->foreignId('reagendado_de_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('reagendado_para_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->decimal('preco_previsto', 10, 2)->default(0);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->decimal('valor_cobrado', 10, 2)->nullable();
            $table->text('observacoes_cliente')->nullable();
            $table->text('observacoes_internas')->nullable();
            $table->timestampTz('confirmado_em')->nullable();
            $table->timestampTz('concluido_em')->nullable();
            $table->timestampTz('cancelado_em')->nullable();
            $table->enum('cancelado_por', ['cliente', 'salao', 'sistema'])->nullable();
            $table->string('motivo_cancelamento')->nullable();
            $table->string('reagendado_por')->nullable(); // cliente, salao, profissional
            $table->string('motivo_reagendamento')->nullable();
            $table->timestampTz('whatsapp_enviado_em')->nullable();
            $table->foreignId('criado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['profissional_id', 'inicio']);
            $table->index(['status', 'inicio']);
        });

        DB::statement('CREATE INDEX agendamentos_cliente_inicio_index ON agendamentos (cliente_id, inicio DESC)');

        // Período efetivamente ocupado na agenda (inclui o intervalo de limpeza/preparo)
        DB::statement("ALTER TABLE agendamentos ADD COLUMN periodo tstzrange GENERATED ALWAYS AS (tstzrange(inicio, fim_bloqueio, '[)')) STORED");

        // O coração do sistema: impossível dois agendamentos ativos sobrepostos para a mesma profissional,
        // mesmo com requisições simultâneas. reagendado/cancelado/nao_compareceu ficam fora e liberam o horário.
        DB::statement("ALTER TABLE agendamentos ADD CONSTRAINT agendamentos_sem_conflito
            EXCLUDE USING gist (profissional_id WITH =, periodo WITH &&)
            WHERE (status IN ('pendente', 'confirmado', 'concluido'))");

        DB::statement('ALTER TABLE agendamentos ADD CONSTRAINT agendamentos_fim_apos_inicio CHECK (fim > inicio AND fim_bloqueio >= fim)');

        Schema::create('agendamento_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_anterior')->nullable();
            $table->string('status_novo')->nullable();
            $table->text('observacao')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index('agendamento_id');
        });

        Schema::create('atendimento_anotacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->text('anotacao');
            $table->timestampTz('created_at')->useCurrent();
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimento_anotacoes');
        Schema::dropIfExists('agendamento_historico');
        Schema::dropIfExists('agendamentos');
    }
};
