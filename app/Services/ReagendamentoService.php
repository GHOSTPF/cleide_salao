<?php

namespace App\Services;

use App\Enums\StatusAgendamento;
use App\Exceptions\AgendamentoException;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Models\User;
use App\Support\Atividade;
use App\Support\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reagendar = o agendamento antigo NÃO é alterado em seus dados; vira "reagendado" (sai da constraint
 * e libera o horário) e um novo agendamento é criado, ligado por reagendado_de_id / reagendado_para_id.
 */
class ReagendamentoService
{
    public const PEDIDO_POR = ['cliente' => 'Pedido da cliente', 'salao' => 'Salão', 'profissional' => 'Profissional'];

    public function __construct(
        private readonly AgendamentoService $agendamentos,
        private readonly DisponibilidadeService $disponibilidade,
        private readonly ListaEsperaService $listaEspera,
    ) {}

    /** Retorna null se pode reagendar ou a mensagem do impedimento. */
    public function impedimento(Agendamento $ag, User $user): ?string
    {
        if (! in_array($ag->status, [StatusAgendamento::Pendente, StatusAgendamento::Confirmado], true)) {
            return 'Só é possível reagendar agendamentos pendentes ou confirmados.';
        }
        if ($user->isAdmin()) {
            return null; // admin ignora prazo e limite
        }

        if (! Setting::bool('profissional_pode_reagendar')) {
            return 'Reagendamento por profissionais está desativado.';
        }
        $horas = Setting::int('antecedencia_cancelamento_horas');
        if (now()->addHours($horas)->gt($ag->inicio)) {
            return 'Reagendamentos precisam ser feitos com pelo menos ' . $horas . 'h de antecedência. Fale com a administração.';
        }
        $limite = Setting::int('limite_reagendamentos_por_agendamento');
        if ($this->quantidadeNaCadeia($ag) >= $limite) {
            return 'Limite de ' . $limite . ' reagendamento(s) atingido para este agendamento.';
        }

        return null;
    }

    public function reagendar(
        Agendamento $antigo,
        Carbon $novoInicio,
        ?Profissional $novaProfissional,
        User $user,
        string $pedidoPor,
        ?string $motivo = null,
        bool $encaixe = false,
    ): Agendamento {
        $encaixe = $encaixe && $user->isAdmin();

        $novo = DB::transaction(function () use ($antigo, $novoInicio, $novaProfissional, $user, $pedidoPor, $motivo, $encaixe) {
            $antigo = Agendamento::with(['servico', 'profissional', 'cliente'])->lockForUpdate()->findOrFail($antigo->id);

            if ($erro = $this->impedimento($antigo, $user)) {
                throw new AgendamentoException($erro);
            }

            $profissional = $novaProfissional ?? $antigo->profissional;
            if ($user->isProfissional() && $profissional->id !== $antigo->profissional_id) {
                throw new AgendamentoException('Você só pode reagendar para a sua própria agenda.');
            }

            $erro = $this->disponibilidade->verificarHorario($antigo->servico, $profissional, $novoInicio, DisponibilidadeService::MODO_PAINEL, $antigo->id, $encaixe);
            if ($erro) {
                throw new AgendamentoException($erro);
            }

            $info = $this->disponibilidade->infoServico($antigo->servico, $profissional);
            $this->agendamentos->garantirClienteSemChoquePublico($antigo->cliente, $novoInicio, $novoInicio->copy()->addMinutes($info['duracao']), $antigo->id);

            $statusAnterior = $antigo->status;
            $rotuloPedido = self::PEDIDO_POR[$pedidoPor] ?? $pedidoPor;

            // 1) antigo sai da constraint (libera o horário)
            Atividade::acao('reagendou', fn () => $antigo->update([
                'status' => StatusAgendamento::Reagendado,
                'reagendado_por' => $pedidoPor,
                'motivo_reagendamento' => $motivo,
            ]));

            // 2) novo agendamento ligado ao antigo (a constraint garante o novo horário livre)
            $n = $this->quantidadeNaCadeia($antigo) + 1;
            $novo = Atividade::acao('reagendou', fn () => $this->agendamentos->inserir([
                'codigo' => $antigo->codigo_base . '-R' . $n,
                'cliente_id' => $antigo->cliente_id,
                'profissional_id' => $profissional->id,
                'servico_id' => $antigo->servico_id,
                'nome_informado' => $antigo->nome_informado,
                'nome_divergente' => false,
                'origem' => $antigo->origem,
                'status' => $statusAnterior === StatusAgendamento::Confirmado ? StatusAgendamento::Confirmado : StatusAgendamento::Pendente,
                'confirmado_em' => $statusAnterior === StatusAgendamento::Confirmado ? now() : null,
                'reagendado_de_id' => $antigo->id,
                'observacoes_cliente' => $antigo->observacoes_cliente,
                'observacoes_internas' => $antigo->observacoes_internas,
                'whatsapp_enviado_em' => $antigo->whatsapp_enviado_em,
                'criado_por_user_id' => $user->id,
            ], $antigo->servico, $profissional, $novoInicio, $info));

            $antigo->forceFill(['reagendado_para_id' => $novo->id])->saveQuietly();

            Cliente::whereKey($antigo->cliente_id)->increment('total_reagendamentos');

            $descricao = 'Reagendado de ' . $antigo->inicio->format('d/m H:i') . ' para ' . $novoInicio->format('d/m H:i')
                . ($profissional->id !== $antigo->profissional_id ? ' com ' . $profissional->nome_exibicao : '')
                . ' (' . mb_strtolower($rotuloPedido) . ($motivo ? ', motivo: ' . $motivo : '') . ')'
                . ($user->isProfissional() ? ' — feito pela profissional' : '');

            $this->agendamentos->historico($antigo, $statusAnterior, StatusAgendamento::Reagendado, $descricao, $user);
            $this->agendamentos->historico($novo, null, $novo->status, 'Criado por reagendamento de ' . $antigo->codigo, $user);

            $this->listaEspera->verificarVagaLiberada($antigo);

            return $novo;
        });

        return $novo->load(['cliente', 'servico', 'profissional']);
    }

    /** Quantos reagendamentos já existem na cadeia até este agendamento. */
    public function quantidadeNaCadeia(Agendamento $ag): int
    {
        $n = 0;
        $atual = $ag;
        while ($atual->reagendado_de_id && $n < 50) {
            $n++;
            $atual = Agendamento::find($atual->reagendado_de_id);
            if (! $atual) {
                break;
            }
        }

        return $n;
    }

    /** Cadeia completa (do primeiro ao último), em ordem. */
    public function cadeia(Agendamento $ag): Collection
    {
        $primeiro = $ag;
        $guard = 0;
        while ($primeiro->reagendado_de_id && $guard++ < 50) {
            $primeiro = Agendamento::find($primeiro->reagendado_de_id) ?? $primeiro;
        }

        $cadeia = collect([$primeiro]);
        $atual = $primeiro;
        $guard = 0;
        while ($atual->reagendado_para_id && $guard++ < 50) {
            $atual = Agendamento::find($atual->reagendado_para_id);
            if (! $atual) {
                break;
            }
            $cadeia->push($atual);
        }

        return $cadeia->count() > 1 ? Agendamento::with(['profissional', 'servico'])->whereIn('id', $cadeia->pluck('id'))->orderBy('id')->get() : collect();
    }
}
