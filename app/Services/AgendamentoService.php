<?php

namespace App\Services;

use App\Enums\CanceladoPor;
use App\Enums\FormaPagamento;
use App\Enums\OrigemAgendamento;
use App\Enums\OrigemCliente;
use App\Enums\StatusAgendamento;
use App\Exceptions\AgendamentoException;
use App\Models\Agendamento;
use App\Models\AgendamentoHistorico;
use App\Models\AtendimentoAnotacao;
use App\Models\Cliente;
use App\Models\Comissao;
use App\Models\ListaEspera;
use App\Models\Pagamento;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use App\Support\Atividade;
use App\Support\Dinheiro;
use App\Support\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgendamentoService
{
    private const ALFABETO_CODIGO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct(
        private readonly ClienteService $clientes,
        private readonly DisponibilidadeService $disponibilidade,
        private readonly ListaEsperaService $listaEspera,
    ) {}

    /**
     * Fluxo do formulário público. Dados já validados pelo Form Request:
     * nome, telefone, servico_id, profissional_id (null = sem preferência), data (Y-m-d), hora (H:i), observacoes.
     *
     * @return array{agendamento: Agendamento, cliente_recorrente: bool}
     */
    public function criarPeloSite(array $dados): array
    {
        $servico = Servico::noSite()->findOrFail($dados['servico_id']);
        $inicio = $this->montarInicio($dados['data'], $dados['hora']);

        return DB::transaction(function () use ($dados, $servico, $inicio) {
            $r = $this->clientes->upsertPorTelefone($dados, OrigemCliente::Site, true);
            /** @var Cliente $cliente */
            $cliente = $r['cliente'];

            if ($cliente->bloqueada_online) {
                throw new AgendamentoException('Para agendar, fale com a gente pelo WhatsApp.', true);
            }

            $pendentes = Agendamento::where('cliente_id', $cliente->id)
                ->where('status', StatusAgendamento::Pendente)
                ->where('inicio', '>', now())
                ->count();
            if ($pendentes >= Setting::int('limite_agendamentos_pendentes_por_cliente')) {
                throw new AgendamentoException('Você já tem agendamentos aguardando confirmação. Fale com a gente pelo WhatsApp para agendar mais.', true);
            }

            $profissional = $this->resolverProfissional($servico, $dados['profissional_id'] ?? null, $inicio, DisponibilidadeService::MODO_SITE);

            if ($erro = $this->disponibilidade->verificarHorario($servico, $profissional, $inicio, DisponibilidadeService::MODO_SITE)) {
                throw new AgendamentoException($erro === 'Esse horário já está ocupado.' ? AgendamentoException::CONFLITO : $erro);
            }

            $info = $this->disponibilidade->infoServico($servico, $profissional);
            $this->garantirClienteSemChoque($cliente, $inicio, $inicio->copy()->addMinutes($info['duracao']));

            $ag = $this->inserir([
                'cliente_id' => $cliente->id,
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'nome_informado' => Str::squish(strip_tags($dados['nome'])),
                'nome_divergente' => $r['nome_divergente'],
                'origem' => OrigemAgendamento::Site,
                'status' => StatusAgendamento::Pendente,
                'observacoes_cliente' => filled($dados['observacoes'] ?? null) ? Str::limit(strip_tags($dados['observacoes']), 500, '') : null,
            ], $servico, $profissional, $inicio, $info);

            $this->historico($ag, null, StatusAgendamento::Pendente, 'Agendamento criado pelo site', null);

            return ['agendamento' => $ag, 'cliente_recorrente' => ! $r['nova']];
        });
    }

    /**
     * Novo agendamento pelo painel (balcão ou combinado por WhatsApp).
     * Dados: cliente_id | (nome, telefone), servico_id, profissional_id, data, hora, origem, encaixe, observacoes_internas, lista_espera_id
     */
    public function criarPeloPainel(array $dados, User $user): Agendamento
    {
        $servico = Servico::ativos()->findOrFail($dados['servico_id']);
        $profissional = Profissional::findOrFail($dados['profissional_id']);
        $inicio = $this->montarInicio($dados['data'], $dados['hora']);
        $origem = OrigemAgendamento::from($dados['origem'] ?? 'painel');
        $encaixe = ($dados['encaixe'] ?? false) && $user->isAdmin();

        return DB::transaction(function () use ($dados, $servico, $profissional, $inicio, $origem, $encaixe, $user) {
            if (! empty($dados['cliente_id'])) {
                $cliente = Cliente::findOrFail($dados['cliente_id']);
                $divergente = false;
            } else {
                $r = $this->clientes->upsertPorTelefone(
                    $dados,
                    $origem === OrigemAgendamento::Whatsapp ? OrigemCliente::Whatsapp : OrigemCliente::Balcao,
                );
                $cliente = $r['cliente'];
                $divergente = $r['nome_divergente'];
            }

            if ($erro = $this->disponibilidade->verificarHorario($servico, $profissional, $inicio, DisponibilidadeService::MODO_PAINEL, null, $encaixe)) {
                throw new AgendamentoException($erro);
            }

            $info = $this->disponibilidade->infoServico($servico, $profissional);
            $this->garantirClienteSemChoque($cliente, $inicio, $inicio->copy()->addMinutes($info['duracao']));

            $ag = $this->inserir([
                'cliente_id' => $cliente->id,
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'nome_informado' => $dados['nome'] ?? $cliente->nome,
                'nome_divergente' => $divergente,
                'origem' => $origem,
                'status' => StatusAgendamento::Confirmado,
                'confirmado_em' => now(),
                'observacoes_cliente' => $dados['observacoes_cliente'] ?? null,
                'observacoes_internas' => $dados['observacoes_internas'] ?? null,
                'criado_por_user_id' => $user->id,
            ], $servico, $profissional, $inicio, $info);

            $obs = 'Agendamento criado pelo painel (' . $origem->label() . ')' . ($encaixe ? ' — encaixe fora do expediente' : '');
            $this->historico($ag, null, StatusAgendamento::Confirmado, $obs, $user);

            if (! empty($dados['lista_espera_id']) && $entrada = ListaEspera::find($dados['lista_espera_id'])) {
                $this->listaEspera->converter($entrada, $ag);
            }

            return $ag;
        });
    }

    public function confirmar(Agendamento $ag, ?User $user): Agendamento
    {
        return DB::transaction(function () use ($ag, $user) {
            $ag = $this->travar($ag);
            $this->transicionar($ag, StatusAgendamento::Confirmado, ['confirmado_em' => now()], 'confirmou', 'Agendamento confirmado', $user);

            return $ag;
        });
    }

    public function cancelar(Agendamento $ag, ?User $user, CanceladoPor $por, ?string $motivo = null): Agendamento
    {
        return DB::transaction(function () use ($ag, $user, $por, $motivo) {
            $ag = $this->travar($ag);
            if (! in_array($ag->status, [StatusAgendamento::Pendente, StatusAgendamento::Confirmado], true)) {
                throw new AgendamentoException('Só é possível cancelar agendamentos pendentes ou confirmados.');
            }
            $this->transicionar($ag, StatusAgendamento::Cancelado, [
                'cancelado_em' => now(),
                'cancelado_por' => $por,
                'motivo_cancelamento' => $motivo,
            ], 'cancelou', trim($por->label() . ($motivo ? ': ' . $motivo : '')), $user);

            Cliente::whereKey($ag->cliente_id)->increment('total_cancelamentos');
            $this->listaEspera->verificarVagaLiberada($ag);

            return $ag;
        });
    }

    public function marcarFalta(Agendamento $ag, ?User $user): Agendamento
    {
        return DB::transaction(function () use ($ag, $user) {
            $ag = $this->travar($ag);
            if (! $ag->ja_comecou) {
                throw new AgendamentoException('A falta só pode ser registrada após o horário de início.');
            }
            $this->transicionar($ag, StatusAgendamento::NaoCompareceu, [], 'marcou_falta', 'Cliente não compareceu', $user);

            $cliente = Cliente::lockForUpdate()->find($ag->cliente_id);
            $cliente->increment('total_faltas');
            $limite = Setting::int('faltas_para_bloqueio');
            if ($limite > 0 && $cliente->total_faltas >= $limite && ! $cliente->bloqueada_online) {
                $this->clientes->bloquear($cliente, 'Bloqueio automático: ' . $cliente->total_faltas . ' faltas');
            }

            return $ag;
        });
    }

    /** Só admin. Volta para confirmado. */
    public function desfazerFalta(Agendamento $ag, User $user): Agendamento
    {
        return DB::transaction(function () use ($ag, $user) {
            $ag = $this->travar($ag);
            if ($ag->status !== StatusAgendamento::NaoCompareceu) {
                throw new AgendamentoException('Este agendamento não está marcado como falta.');
            }
            $this->comTratamentoDeConflito(fn () => $this->transicionar($ag, StatusAgendamento::Confirmado, [], 'desfez_falta', 'Falta desfeita', $user));
            DB::table('clientes')->where('id', $ag->cliente_id)->update(['total_faltas' => DB::raw('GREATEST(total_faltas - 1, 0)')]);

            return $ag;
        });
    }

    /**
     * Conclui o atendimento: exige valor cobrado + 1 ou 2 formas de pagamento cuja soma bate com o valor.
     *
     * @param  array<int, array{forma: string, valor: mixed}>  $pagamentos
     */
    public function concluir(Agendamento $ag, User $user, mixed $valorCobrado, array $pagamentos, ?string $anotacao = null): Agendamento
    {
        $valor = Dinheiro::paraDecimal($valorCobrado);
        if ($valor === null || $valor < 0) {
            throw new AgendamentoException('Informe o valor cobrado.');
        }

        $pagamentos = collect($pagamentos)
            ->map(fn ($p) => ['forma' => FormaPagamento::from($p['forma']), 'valor' => Dinheiro::paraDecimal($p['valor'] ?? null) ?? 0])
            ->filter(fn ($p) => $p['valor'] > 0)
            ->values();

        if ($pagamentos->count() > 2) {
            throw new AgendamentoException('Use no máximo duas formas de pagamento.');
        }
        if ($valor > 0 && $pagamentos->isEmpty()) {
            throw new AgendamentoException('Informe a forma de pagamento.');
        }
        if (abs($pagamentos->sum('valor') - $valor) > 0.009) {
            throw new AgendamentoException('A soma dos pagamentos (' . Dinheiro::formatar($pagamentos->sum('valor')) . ') não bate com o valor cobrado (' . Dinheiro::formatar($valor) . ').');
        }

        return DB::transaction(function () use ($ag, $user, $valor, $pagamentos, $anotacao) {
            $ag = $this->travar($ag);
            if (! $ag->ja_comecou) {
                throw new AgendamentoException('O atendimento só pode ser concluído após o horário de início.');
            }
            if (! in_array($ag->status, [StatusAgendamento::Pendente, StatusAgendamento::Confirmado], true)) {
                throw new AgendamentoException('Só é possível concluir agendamentos pendentes ou confirmados.');
            }

            Atividade::acao('concluiu', function () use ($ag, $user, $valor, $pagamentos) {
                foreach ($pagamentos as $p) {
                    Pagamento::create([
                        'agendamento_id' => $ag->id, 'valor' => $p['valor'], 'forma' => $p['forma'],
                        'pago_em' => now(), 'registrado_por_user_id' => $user->id,
                    ]);
                }

                // Comissão como snapshot do percentual vigente
                $percentual = (float) $ag->profissional->percentual_comissao;
                Comissao::create([
                    'agendamento_id' => $ag->id,
                    'profissional_id' => $ag->profissional_id,
                    'base_calculo' => $valor,
                    'percentual' => $percentual,
                    'valor' => round($valor * $percentual / 100, 2),
                    'status' => 'pendente',
                ]);
            });

            $this->transicionar($ag, StatusAgendamento::Concluido, [
                'valor_cobrado' => $valor,
                'desconto' => max(0, round((float) $ag->preco_previsto - $valor, 2)),
                'concluido_em' => now(),
            ], 'concluiu', 'Atendimento concluído — ' . Dinheiro::formatar($valor) . ' (' . $pagamentos->map(fn ($p) => $p['forma']->label())->join(' + ') . ')', $user);

            DB::table('clientes')->where('id', $ag->cliente_id)->update([
                'total_visitas' => DB::raw('total_visitas + 1'),
                'total_gasto' => DB::raw('total_gasto + ' . number_format($valor, 2, '.', '')),
                'primeira_visita_em' => DB::raw("LEAST(COALESCE(primeira_visita_em, 'infinity'::timestamptz), '" . $ag->inicio->format('Y-m-d H:i:sP') . "'::timestamptz)"),
                'ultima_visita_em' => DB::raw("GREATEST(COALESCE(ultima_visita_em, '-infinity'::timestamptz), '" . $ag->inicio->format('Y-m-d H:i:sP') . "'::timestamptz)"),
                'updated_at' => now(),
            ]);

            if (filled($anotacao)) {
                AtendimentoAnotacao::create([
                    'cliente_id' => $ag->cliente_id, 'agendamento_id' => $ag->id,
                    'profissional_id' => $ag->profissional_id, 'anotacao' => $anotacao,
                ]);
            }

            return $ag->load(['pagamentos', 'comissao']);
        });
    }

    /** Só admin: reverte pagamentos e comissão, volta para confirmado e recalcula a cliente. */
    public function estornar(Agendamento $ag, User $user, ?string $motivo = null): Agendamento
    {
        return DB::transaction(function () use ($ag, $user, $motivo) {
            $ag = $this->travar($ag);
            if ($ag->status !== StatusAgendamento::Concluido) {
                throw new AgendamentoException('Só atendimentos concluídos podem ser estornados.');
            }

            Atividade::acao('estornou', function () use ($ag) {
                $ag->pagamentos()->get()->each->delete();
                $ag->comissao()->get()->each->delete();
            });

            $this->transicionar($ag, StatusAgendamento::Confirmado, [
                'valor_cobrado' => null, 'desconto' => 0, 'concluido_em' => null,
            ], 'estornou', 'Estorno' . ($motivo ? ': ' . $motivo : ''), $user);

            $this->clientes->recalcular($ag->cliente()->first());

            return $ag;
        });
    }

    /** Cancela pendentes não confirmados dentro de `expirar_pendente_horas` (0 = nunca). */
    public function expirarPendentes(): int
    {
        $horas = Setting::int('expirar_pendente_horas');
        if ($horas <= 0) {
            return 0;
        }

        $total = 0;
        Agendamento::where('status', StatusAgendamento::Pendente)
            ->where('created_at', '<=', now()->subHours($horas))
            ->orderBy('id')
            ->each(function (Agendamento $ag) use (&$total) {
                $this->cancelar($ag, null, CanceladoPor::Sistema, 'Não confirmado em até ' . Setting::int('expirar_pendente_horas') . 'h');
                $total++;
            });

        return $total;
    }

    public function registrarWhatsappEnviado(Agendamento $ag): void
    {
        if (! $ag->whatsapp_enviado_em) {
            $ag->forceFill(['whatsapp_enviado_em' => now()])->saveQuietly();
            $this->historico($ag, $ag->status, $ag->status, 'Cliente abriu o WhatsApp para enviar o pedido ao salão', null);
        }
    }

    public function historico(Agendamento $ag, ?StatusAgendamento $anterior, ?StatusAgendamento $novo, ?string $obs, ?User $user): void
    {
        AgendamentoHistorico::create([
            'agendamento_id' => $ag->id,
            'user_id' => $user?->id ?? Auth::id(),
            'status_anterior' => $anterior?->value,
            'status_novo' => $novo?->value,
            'observacao' => $obs,
        ]);
    }

    // ------------------------------------------------------------------ internos

    /**
     * Insere o agendamento dentro de um SAVEPOINT; a exclusion constraint é a garantia final.
     * SQLSTATE 23P01 (exclusion_violation) vira mensagem amigável.
     */
    public function inserir(array $attrs, Servico $servico, Profissional $profissional, Carbon $inicio, array $info): Agendamento
    {
        $fim = $inicio->copy()->addMinutes($info['duracao']);
        $attrs += [
            'codigo' => $attrs['codigo'] ?? $this->gerarCodigo(),
            'token_publico' => Str::random(40),
            'inicio' => $inicio,
            'fim' => $fim,
            'fim_bloqueio' => $fim->copy()->addMinutes($servico->intervalo_apos_minutos),
            'preco_previsto' => $info['preco'],
        ];

        return $this->comTratamentoDeConflito(fn () => DB::transaction(fn () => Agendamento::create($attrs)));
    }

    public function comTratamentoDeConflito(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (QueryException $e) {
            if (self::ehConflito($e)) {
                throw AgendamentoException::conflito();
            }
            throw $e;
        }
    }

    public static function ehConflito(QueryException $e): bool
    {
        return ($e->errorInfo[0] ?? $e->getCode()) === '23P01';
    }

    public function gerarCodigo(): string
    {
        do {
            $codigo = 'CL-';
            for ($i = 0; $i < 5; $i++) {
                $codigo .= self::ALFABETO_CODIGO[random_int(0, strlen(self::ALFABETO_CODIGO) - 1)];
            }
        } while (Agendamento::where('codigo', $codigo)->exists());

        return $codigo;
    }

    public function montarInicio(string $data, string $hora): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $data . ' ' . substr($hora, 0, 5), config('app.timezone'))->startOfMinute();
    }

    private function resolverProfissional(Servico $servico, mixed $profissionalId, Carbon $inicio, string $modo): Profissional
    {
        if ($profissionalId) {
            $prof = Profissional::where('ativo', true)->find($profissionalId);
            if (! $prof || ! $this->disponibilidade->profissionalRealizaServico($prof, $servico)) {
                throw new AgendamentoException('Profissional indisponível para este serviço.');
            }

            return $prof;
        }

        return $this->disponibilidade->escolherProfissional($servico, $inicio, $modo)
            ?? throw AgendamentoException::conflito();
    }

    private function garantirClienteSemChoque(Cliente $cliente, Carbon $inicio, Carbon $fim, ?int $ignorarId = null): void
    {
        $choque = Agendamento::ativos()
            ->where('cliente_id', $cliente->id)
            ->where('inicio', '<', $fim)->where('fim', '>', $inicio)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->exists();

        if ($choque) {
            throw new AgendamentoException('Você já tem um agendamento nesse horário.');
        }
    }

    public function garantirClienteSemChoquePublico(Cliente $cliente, Carbon $inicio, Carbon $fim, ?int $ignorarId = null): void
    {
        $this->garantirClienteSemChoque($cliente, $inicio, $fim, $ignorarId);
    }

    private function travar(Agendamento $ag): Agendamento
    {
        return Agendamento::with(['profissional', 'cliente', 'servico'])->lockForUpdate()->findOrFail($ag->id);
    }

    private function transicionar(Agendamento $ag, StatusAgendamento $novo, array $extra, string $acao, ?string $obs, ?User $user): void
    {
        $anterior = $ag->status;
        if (! $anterior->podeIrPara($novo)) {
            throw new AgendamentoException('Não é possível mudar de "' . $anterior->label() . '" para "' . $novo->label() . '".');
        }

        Atividade::acao($acao, fn () => $ag->update(['status' => $novo] + $extra));
        $this->historico($ag, $anterior, $novo, $obs, $user);
    }
}
