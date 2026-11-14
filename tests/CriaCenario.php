<?php

namespace Tests;

use App\Enums\PapelUsuario;
use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\CategoriaServico;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use App\Services\AgendamentoService;
use App\Support\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Cenário padrão: "agora" = segunda 16/11/2026 08:00; profissionais seg–sáb 09–18 com almoço 12–13.
 */
trait CriaCenario
{
    protected function congelarAgora(string $quando = '2026-11-16 08:00:00'): Carbon
    {
        $agora = Carbon::parse($quando, config('app.timezone'));
        Carbon::setTestNow($agora);

        return $agora;
    }

    protected function configurar(array $valores): void
    {
        Setting::setMuitas($valores);
    }

    protected function criarServico(array $attrs = []): Servico
    {
        $categoria = CategoriaServico::firstOrCreate(['nome' => 'Cabelo'], ['ordem' => 1]);
        $nome = $attrs['nome'] ?? 'Serviço ' . Str::random(5);

        return Servico::create($attrs + [
            'categoria_servico_id' => $categoria->id,
            'nome' => $nome,
            'slug' => Str::slug($nome) . '-' . Str::random(4),
            'duracao_minutos' => 60,
            'intervalo_apos_minutos' => 0,
            'preco' => 100,
            'ativo' => true,
            'exibir_no_site' => true,
        ]);
    }

    protected function criarProfissional(array $servicos = [], array $attrs = [], bool $comUsuario = false): Profissional
    {
        $userId = null;
        if ($comUsuario) {
            $userId = User::factory()->create(['papel' => PapelUsuario::Profissional])->id;
        }

        $prof = Profissional::create($attrs + [
            'user_id' => $userId,
            'nome' => 'Profissional ' . Str::random(4),
            'cor_agenda' => '#B08D57',
            'percentual_comissao' => 40,
            'ativo' => true,
        ]);

        foreach (range(1, 6) as $dia) {
            $prof->horarios()->create([
                'dia_semana' => $dia, 'hora_inicio' => '09:00', 'hora_fim' => '18:00',
                'intervalo_inicio' => '12:00', 'intervalo_fim' => '13:00',
            ]);
        }

        foreach ($servicos as $s) {
            $prof->servicos()->attach($s->id);
        }

        return $prof;
    }

    protected function criarAdmin(): User
    {
        return User::factory()->create(['papel' => PapelUsuario::Admin]);
    }

    protected function criarCliente(array $attrs = []): Cliente
    {
        return Cliente::factory()->create($attrs);
    }

    /** Insere um agendamento direto (sem regras de expediente), útil para montar cenários. */
    protected function criarAgendamento(Cliente $cliente, Profissional $prof, Servico $servico, string $inicio, StatusAgendamento $status = StatusAgendamento::Confirmado): Agendamento
    {
        $svc = app(AgendamentoService::class);
        $inicioC = Carbon::parse($inicio, config('app.timezone'));

        return $svc->inserir([
            'cliente_id' => $cliente->id,
            'profissional_id' => $prof->id,
            'servico_id' => $servico->id,
            'nome_informado' => $cliente->nome,
            'status' => $status,
            'origem' => 'painel',
            'confirmado_em' => $status === StatusAgendamento::Confirmado ? now() : null,
        ], $servico, $prof, $inicioC, ['duracao' => $servico->duracao_minutos, 'preco' => (float) $servico->preco]);
    }
}
