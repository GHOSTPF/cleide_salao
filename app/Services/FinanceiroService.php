<?php

namespace App\Services;

use App\Enums\FormaPagamento;
use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\Comissao;
use App\Models\Despesa;
use App\Models\Pagamento;
use App\Support\Atividade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceiroService
{
    /** Resumo do período [inicio, fim] (datas inclusivas). */
    public function resumo(Carbon $inicio, Carbon $fim): array
    {
        [$de, $ate] = $this->limites($inicio, $fim);

        $entradas = (float) Pagamento::whereBetween('pago_em', [$de, $ate])->sum('valor');
        $saidas = (float) Despesa::whereBetween('data', [$de->toDateString(), $ate->toDateString()])->sum('valor');
        $atendimentos = Agendamento::where('status', StatusAgendamento::Concluido)->whereBetween('concluido_em', [$de, $ate])->count();

        $porForma = Pagamento::whereBetween('pago_em', [$de, $ate])
            ->selectRaw('forma, SUM(valor) AS total, COUNT(*) AS qtd')->groupBy('forma')->get()
            ->mapWithKeys(fn ($l) => [$l->forma->value => ['label' => $l->forma->label(), 'total' => (float) $l->total, 'qtd' => (int) $l->qtd]]);

        foreach (FormaPagamento::cases() as $f) {
            if (! $porForma->has($f->value)) {
                $porForma[$f->value] = ['label' => $f->label(), 'total' => 0.0, 'qtd' => 0];
            }
        }

        $comissoes = (float) Comissao::whereHas('agendamento', fn ($q) => $q->whereBetween('concluido_em', [$de, $ate]))->sum('valor');

        return [
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $entradas - $saidas,
            'comissoes' => $comissoes,
            'atendimentos' => $atendimentos,
            'ticket_medio' => $atendimentos ? round($entradas / $atendimentos, 2) : 0,
            'por_forma' => $porForma->sortByDesc('total'),
        ];
    }

    /** Comissões por profissional no período (pela data de conclusão do atendimento). */
    public function comissoesPorProfissional(Carbon $inicio, Carbon $fim): Collection
    {
        [$de, $ate] = $this->limites($inicio, $fim);

        return DB::table('comissoes')
            ->join('agendamentos', 'agendamentos.id', '=', 'comissoes.agendamento_id')
            ->join('profissionais', 'profissionais.id', '=', 'comissoes.profissional_id')
            ->whereBetween('agendamentos.concluido_em', [$de, $ate])
            ->groupBy('profissionais.id', 'profissionais.nome', 'profissionais.apelido')
            ->selectRaw("profissionais.id, COALESCE(profissionais.apelido, profissionais.nome) AS nome,
                COUNT(*) AS atendimentos, SUM(comissoes.base_calculo) AS base,
                SUM(comissoes.valor) AS total,
                SUM(comissoes.valor) FILTER (WHERE comissoes.status = 'pendente') AS pendente,
                SUM(comissoes.valor) FILTER (WHERE comissoes.status = 'paga') AS paga")
            ->orderBy('nome')
            ->get();
    }

    /** Faturamento (pagamentos) dos últimos N meses, incluindo o atual. */
    public function faturamentoMensal(int $meses = 6): Collection
    {
        $inicio = now()->startOfMonth()->subMonths($meses - 1);
        $totais = Pagamento::where('pago_em', '>=', $inicio)
            ->selectRaw("to_char(pago_em AT TIME ZONE ?, 'YYYY-MM') AS mes, SUM(valor) AS total", [config('app.timezone')])
            ->groupBy('mes')->pluck('total', 'mes');

        $nomes = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

        return collect(range(0, $meses - 1))->map(function ($i) use ($inicio, $totais, $nomes) {
            $m = $inicio->copy()->addMonths($i);

            return ['mes' => $nomes[$m->month - 1] . '/' . $m->format('y'), 'total' => (float) ($totais[$m->format('Y-m')] ?? 0)];
        });
    }

    public function marcarComissoesPagas(array $ids): int
    {
        return Atividade::acao('pagou_comissao', function () use ($ids) {
            $n = 0;
            Comissao::whereIn('id', $ids)->where('status', 'pendente')->get()->each(function (Comissao $c) use (&$n) {
                $c->update(['status' => 'paga', 'pago_em' => now()]);
                $n++;
            });

            return $n;
        });
    }

    public function limites(Carbon $inicio, Carbon $fim): array
    {
        return [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()];
    }
}
