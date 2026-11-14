<?php

namespace App\Services;

use App\Enums\OrigemCliente;
use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\AtendimentoAnotacao;
use App\Models\Cliente;
use App\Models\ListaEspera;
use App\Support\Atividade;
use App\Support\Telefone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ClienteService
{
    /**
     * Encontra (ou cria) a cliente pelo telefone normalizado. A cliente nunca é duplicada:
     * - nunca sobrescreve o nome do cadastro;
     * - e-mail e data de nascimento só são preenchidos se estiverem vazios;
     * - se a cliente foi mesclada, segue para a cliente principal.
     *
     * @return array{cliente: Cliente, nova: bool, nome_divergente: bool}
     */
    public function upsertPorTelefone(array $dados, OrigemCliente $origem = OrigemCliente::Site, bool $consentimento = false): array
    {
        $telefone = Telefone::normalizar($dados['telefone'] ?? '');
        if (! Telefone::valido($telefone)) {
            throw new InvalidArgumentException('Telefone inválido.');
        }
        $nome = $this->limparNome($dados['nome'] ?? '');

        return DB::transaction(function () use ($telefone, $nome, $dados, $origem, $consentimento) {
            // createOrFirst usa SAVEPOINT + UNIQUE(telefone): seguro com requisições simultâneas
            $cliente = Cliente::withTrashed()->createOrFirst(['telefone' => $telefone], [
                'nome' => $nome,
                'email' => $dados['email'] ?? null,
                'data_nascimento' => $dados['data_nascimento'] ?? null,
                'origem' => $origem,
                'consentimento_lgpd_em' => $consentimento ? now() : null,
            ]);
            $nova = $cliente->wasRecentlyCreated;

            $cliente = $this->resolverPrincipal($cliente);

            if (! $nova) {
                $alteracoes = [];
                if (blank($cliente->email) && filled($dados['email'] ?? null)) {
                    $alteracoes['email'] = $dados['email'];
                }
                if (blank($cliente->data_nascimento) && filled($dados['data_nascimento'] ?? null)) {
                    $alteracoes['data_nascimento'] = $dados['data_nascimento'];
                }
                if ($consentimento && ! $cliente->consentimento_lgpd_em) {
                    $alteracoes['consentimento_lgpd_em'] = now();
                }
                if ($alteracoes) {
                    $cliente->update($alteracoes);
                }
            }

            return [
                'cliente' => $cliente,
                'nova' => $nova,
                'nome_divergente' => ! $nova && $nome !== '' && self::nomesDiferentes($nome, $cliente->nome),
            ];
        });
    }

    /** Se a cliente foi mesclada (soft delete + mesclada_em_id), devolve a principal. */
    public function resolverPrincipal(Cliente $cliente): Cliente
    {
        $visitados = [];
        while ($cliente->trashed() && $cliente->mesclada_em_id && ! in_array($cliente->id, $visitados, true)) {
            $visitados[] = $cliente->id;
            $cliente = Cliente::withTrashed()->findOrFail($cliente->mesclada_em_id);
        }
        if ($cliente->trashed()) {
            $cliente->restore();
        }

        return $cliente;
    }

    public static function normalizarNome(string $nome): string
    {
        return Str::of(Str::ascii($nome))->lower()->squish()->toString();
    }

    public static function nomesDiferentes(string $a, string $b): bool
    {
        return self::normalizarNome($a) !== self::normalizarNome($b);
    }

    private function limparNome(string $nome): string
    {
        return Str::of(strip_tags($nome))->squish()->limit(120, '')->toString();
    }

    /** Atualiza o cadastro com o nome informado no agendamento (botão "Atualizar cadastro"). */
    public function resolverNomeDivergente(Agendamento $ag, bool $atualizarCadastro): void
    {
        DB::transaction(function () use ($ag, $atualizarCadastro) {
            if ($atualizarCadastro && filled($ag->nome_informado)) {
                Atividade::acao('atualizou_nome', fn () => $ag->cliente->update(['nome' => $ag->nome_informado]));
            }
            $ag->update(['nome_divergente' => false]);
        });
    }

    /**
     * Recalcula as colunas desnormalizadas a partir das tabelas fonte (agendamentos + pagamentos).
     */
    public function recalcular(Cliente $cliente): Cliente
    {
        $this->recalcularQuery($cliente->id);

        return $cliente->refresh();
    }

    /** Recalcula todas as clientes com um único UPDATE. */
    public function recalcularTodas(): int
    {
        return $this->recalcularQuery(null);
    }

    private function recalcularQuery(?int $clienteId): int
    {
        $filtro = $clienteId ? 'WHERE c2.id = ?' : '';
        $bindings = $clienteId ? [$clienteId] : [];

        $concluido = StatusAgendamento::Concluido->value;
        $falta = StatusAgendamento::NaoCompareceu->value;
        $cancelado = StatusAgendamento::Cancelado->value;
        $reagendado = StatusAgendamento::Reagendado->value;

        return DB::update("
            UPDATE clientes c SET
                primeira_visita_em = s.primeira,
                ultima_visita_em = s.ultima,
                total_visitas = s.visitas,
                total_gasto = s.gasto,
                total_faltas = s.faltas,
                total_cancelamentos = s.cancelamentos,
                total_reagendamentos = s.reagendamentos
            FROM (
                SELECT c2.id,
                    MIN(a.inicio) FILTER (WHERE a.status = '{$concluido}') AS primeira,
                    MAX(a.inicio) FILTER (WHERE a.status = '{$concluido}') AS ultima,
                    COUNT(a.id) FILTER (WHERE a.status = '{$concluido}') AS visitas,
                    COALESCE((SELECT SUM(p.valor) FROM pagamentos p JOIN agendamentos a2 ON a2.id = p.agendamento_id
                              WHERE a2.cliente_id = c2.id AND a2.status = '{$concluido}'), 0) AS gasto,
                    COUNT(a.id) FILTER (WHERE a.status = '{$falta}') AS faltas,
                    COUNT(a.id) FILTER (WHERE a.status = '{$cancelado}') AS cancelamentos,
                    COUNT(a.id) FILTER (WHERE a.status = '{$reagendado}') AS reagendamentos
                FROM clientes c2
                LEFT JOIN agendamentos a ON a.cliente_id = c2.id
                {$filtro}
                GROUP BY c2.id
            ) s
            WHERE s.id = c.id", $bindings);
    }

    /**
     * Mescla a cliente secundária na principal: move agendamentos (e seus pagamentos),
     * anotações e lista de espera; marca a secundária com mesclada_em_id + soft delete e recalcula.
     */
    public function mesclar(Cliente $principal, Cliente $secundaria): Cliente
    {
        if ($principal->id === $secundaria->id) {
            throw new InvalidArgumentException('Selecione duas clientes diferentes.');
        }

        return Atividade::acao('mesclou', function () use ($principal, $secundaria) {
            return DB::transaction(function () use ($principal, $secundaria) {
                Agendamento::where('cliente_id', $secundaria->id)->update(['cliente_id' => $principal->id]);
                AtendimentoAnotacao::where('cliente_id', $secundaria->id)->update(['cliente_id' => $principal->id]);
                ListaEspera::where('cliente_id', $secundaria->id)->update(['cliente_id' => $principal->id]);

                $complemento = [];
                foreach (['email', 'data_nascimento', 'instagram', 'profissional_preferida_id'] as $campo) {
                    if (blank($principal->{$campo}) && filled($secundaria->{$campo})) {
                        $complemento[$campo] = $secundaria->{$campo};
                    }
                }
                if (filled($secundaria->observacoes)) {
                    $complemento['observacoes'] = trim(($principal->observacoes ? $principal->observacoes . "\n" : '')
                        . '[Mesclada de ' . $secundaria->nome . ' - ' . $secundaria->telefone_formatado . '] ' . $secundaria->observacoes);
                }
                if (! $principal->consentimento_lgpd_em && $secundaria->consentimento_lgpd_em) {
                    $complemento['consentimento_lgpd_em'] = $secundaria->consentimento_lgpd_em;
                }
                if ($complemento) {
                    $principal->update($complemento);
                }

                DB::table('clientes_duplicidade_ignorada')
                    ->where(fn ($q) => $q->whereIn('cliente_a_id', [$secundaria->id])->orWhereIn('cliente_b_id', [$secundaria->id]))
                    ->delete();

                $secundaria->update(['mesclada_em_id' => $principal->id]);
                $secundaria->delete();

                return $this->recalcular($principal);
            });
        });
    }

    /** Pares de possíveis duplicadas: nome com similaridade >= 0.6 e telefones diferentes. */
    public function possiveisDuplicadas(int $limite = 50): Collection
    {
        $linhas = DB::transaction(function () use ($limite) {
            DB::statement('SET LOCAL pg_trgm.similarity_threshold = 0.6');

            return DB::select('
                SELECT a.id AS a_id, b.id AS b_id, similarity(a.nome, b.nome) AS similaridade
                FROM clientes a
                JOIN clientes b ON b.nome % a.nome AND a.id < b.id
                WHERE a.deleted_at IS NULL AND b.deleted_at IS NULL
                  AND a.telefone <> b.telefone
                  AND NOT EXISTS (
                      SELECT 1 FROM clientes_duplicidade_ignorada i
                      WHERE (i.cliente_a_id = a.id AND i.cliente_b_id = b.id) OR (i.cliente_a_id = b.id AND i.cliente_b_id = a.id)
                  )
                ORDER BY similaridade DESC
                LIMIT ?', [$limite]);
        });

        $clientes = Cliente::whereIn('id', collect($linhas)->flatMap(fn ($l) => [$l->a_id, $l->b_id])->unique())->get()->keyBy('id');

        return collect($linhas)->map(fn ($l) => (object) [
            'a' => $clientes[$l->a_id],
            'b' => $clientes[$l->b_id],
            'similaridade' => round((float) $l->similaridade * 100),
        ]);
    }

    public function ignorarDuplicidade(Cliente $a, Cliente $b): void
    {
        [$menor, $maior] = $a->id < $b->id ? [$a->id, $b->id] : [$b->id, $a->id];
        DB::table('clientes_duplicidade_ignorada')->insertOrIgnore([
            'cliente_a_id' => $menor, 'cliente_b_id' => $maior, 'created_at' => now(),
        ]);
    }

    public function bloquear(Cliente $cliente, string $motivo): void
    {
        Atividade::acao('bloqueou', fn () => $cliente->update(['bloqueada_online' => true, 'motivo_bloqueio' => $motivo]));
    }

    public function desbloquear(Cliente $cliente): void
    {
        Atividade::acao('desbloqueou', fn () => $cliente->update(['bloqueada_online' => false, 'motivo_bloqueio' => null]));
    }

    /** Indicadores da ficha (profissional e serviço mais frequentes, próximo retorno sugerido). */
    public function indicadores(Cliente $cliente, ?int $somenteProfissionalId = null): array
    {
        $concluidos = Agendamento::with(['servico', 'profissional'])
            ->where('cliente_id', $cliente->id)
            ->where('status', StatusAgendamento::Concluido)
            ->when($somenteProfissionalId, fn ($q) => $q->where('profissional_id', $somenteProfissionalId))
            ->orderByDesc('inicio')
            ->get();

        $profMaisFrequente = $concluidos->groupBy('profissional_id')->sortByDesc->count()->first()?->first()?->profissional;
        $servicoMaisFeito = $concluidos->groupBy('servico_id')->sortByDesc->count()->first()?->first()?->servico;

        $proximoRetorno = $concluidos
            ->filter(fn ($a) => $a->servico->retorno_sugerido_dias)
            ->unique('servico_id')
            ->map(fn ($a) => ['servico' => $a->servico->nome, 'data' => $a->inicio->copy()->addDays($a->servico->retorno_sugerido_dias)->startOfDay()])
            ->sortBy('data')
            ->first();

        return [
            'profissional_mais_frequente' => $profMaisFrequente,
            'servico_mais_feito' => $servicoMaisFeito,
            'proximo_retorno' => $proximoRetorno,
        ];
    }

    /**
     * Clientes no prazo de retorno: último atendimento concluído de um serviço com retorno sugerido
     * já venceu (ou vence nos próximos $diasAntecedencia) e não há agendamento futuro ativo.
     */
    public function retornos(int $diasAntecedencia = 7): Collection
    {
        $linhas = DB::select("
            SELECT DISTINCT ON (a.cliente_id, a.servico_id)
                a.cliente_id, a.servico_id, a.inicio AS ultimo_atendimento,
                (a.inicio + (s.retorno_sugerido_dias || ' days')::interval) AS retorno_em
            FROM agendamentos a
            JOIN servicos s ON s.id = a.servico_id AND s.retorno_sugerido_dias IS NOT NULL
            JOIN clientes c ON c.id = a.cliente_id AND c.deleted_at IS NULL
            WHERE a.status = 'concluido'
            ORDER BY a.cliente_id, a.servico_id, a.inicio DESC
        ");

        $limite = now()->addDays($diasAntecedencia);
        $linhas = collect($linhas)->filter(fn ($l) => Carbon::parse($l->retorno_em)->lte($limite));

        $comFuturo = Agendamento::ativos()->where('inicio', '>', now())
            ->whereIn('cliente_id', $linhas->pluck('cliente_id')->unique())
            ->pluck('cliente_id')->unique()->flip();

        $linhas = $linhas->reject(fn ($l) => isset($comFuturo[$l->cliente_id]));
        $clientes = Cliente::whereIn('id', $linhas->pluck('cliente_id')->unique())->get()->keyBy('id');
        $servicos = \App\Models\Servico::whereIn('id', $linhas->pluck('servico_id')->unique())->get()->keyBy('id');

        return $linhas->map(fn ($l) => (object) [
            'cliente' => $clientes[$l->cliente_id],
            'servico' => $servicos[$l->servico_id],
            'ultimo_atendimento' => Carbon::parse($l->ultimo_atendimento),
            'retorno_em' => Carbon::parse($l->retorno_em),
        ])->sortBy('retorno_em')->values();
    }
}
