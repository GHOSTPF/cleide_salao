<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->cascadeOnDelete();
            $table->decimal('valor', 10, 2);
            $table->enum('forma', ['pix', 'dinheiro', 'debito', 'credito']);
            $table->timestampTz('pago_em');
            $table->foreignId('registrado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index('pago_em');
            $table->index('agendamento_id');
        });

        Schema::create('comissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->unique()->constrained('agendamentos')->cascadeOnDelete();
            $table->foreignId('profissional_id')->constrained('profissionais')->restrictOnDelete();
            $table->decimal('base_calculo', 10, 2);
            $table->decimal('percentual', 5, 2);
            $table->decimal('valor', 10, 2);
            $table->enum('status', ['pendente', 'paga'])->default('pendente');
            $table->timestampTz('pago_em')->nullable();
            $table->timestampsTz();
            $table->index(['profissional_id', 'status']);
        });

        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->enum('categoria', ['aluguel', 'produtos', 'energia', 'agua', 'internet', 'marketing', 'outros']);
            $table->decimal('valor', 10, 2);
            $table->date('data');
            $table->boolean('pago')->default(false);
            $table->text('observacao')->nullable();
            $table->timestampsTz();
            $table->index('data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesas');
        Schema::dropIfExists('comissoes');
        Schema::dropIfExists('pagamentos');
    }
};
