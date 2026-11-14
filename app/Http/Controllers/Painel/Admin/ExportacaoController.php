<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Exports\Relatorio;
use App\Exports\VariosRelatorios;
use App\Http\Controllers\Controller;
use App\Models\Despesa;
use App\Models\Pagamento;
use App\Services\FinanceiroService;
use App\Support\Atividade;
use App\Support\Dinheiro;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ExportacaoController extends Controller
{
    public function clientes(Request $request): Response
    {
        $clientes = ClienteController::consulta($request->only(['q', 'filtro', 'ordem']))->get();

        $relatorio = new Relatorio('Clientes', ['Nome', 'WhatsApp', 'E-mail', 'Nascimento', 'Origem', 'Primeira visita', 'Última visita', 'Visitas', 'Total gasto', 'Faltas', 'Cancelamentos', 'Bloqueada online'],
            $clientes->map(fn ($c) => [
                $c->nome, $c->telefone_formatado, $c->email, $c->data_nascimento?->format('d/m/Y'), $c->origem->label(),
                $c->primeira_visita_em?->format('d/m/Y'), $c->ultima_visita_em?->format('d/m/Y'), $c->total_visitas,
                (float) $c->total_gasto, $c->total_faltas, $c->total_cancelamentos, $c->bloqueada_online ? 'Sim' : 'Não',
            ])->all());

        return $this->baixar($request, $relatorio, 'clientes');
    }

    public function agendamentos(Request $request): Response
    {
        $ags = AgendamentoController::consulta($request->only(['de', 'ate', 'status', 'profissional_id', 'servico_id', 'origem', 'q']))
            ->with(['cliente', 'profissional', 'servico'])->orderBy('inicio')->get();

        $relatorio = new Relatorio('Agendamentos', ['Código', 'Data', 'Início', 'Fim', 'Cliente', 'WhatsApp', 'Serviço', 'Profissional', 'Origem', 'Status', 'Previsto', 'Cobrado'],
            $ags->map(fn ($a) => [
                $a->codigo, $a->inicio->format('d/m/Y'), $a->inicio->format('H:i'), $a->fim->format('H:i'), $a->cliente->nome,
                $a->cliente->telefone_formatado, $a->servico->nome, $a->profissional->nome_exibicao, $a->origem->label(),
                $a->status->label(), (float) $a->preco_previsto, $a->valor_cobrado !== null ? (float) $a->valor_cobrado : null,
            ])->all());

        return $this->baixar($request, $relatorio, 'agendamentos');
    }

    public function financeiro(Request $request, FinanceiroService $svc): Response
    {
        [$de, $ate] = FinanceiroController::periodo($request);
        $resumo = $svc->resumo($de, $ate);

        $entradas = Pagamento::with(['agendamento.cliente', 'agendamento.servico', 'agendamento.profissional'])
            ->whereBetween('pago_em', [$de, $ate])->orderBy('pago_em')->get();
        $despesas = Despesa::whereBetween('data', [$de->toDateString(), $ate->toDateString()])->orderBy('data')->get();
        $comissoes = $svc->comissoesPorProfissional($de, $ate);

        $relatorios = [
            new Relatorio('Resumo', ['Indicador', 'Valor'], array_merge([
                ['Período', $de->format('d/m/Y') . ' a ' . $ate->format('d/m/Y')],
                ['Entradas', $resumo['entradas']],
                ['Saídas', $resumo['saidas']],
                ['Saldo', $resumo['saldo']],
                ['Comissões geradas', $resumo['comissoes']],
                ['Atendimentos', $resumo['atendimentos']],
                ['Ticket médio', $resumo['ticket_medio']],
            ], $resumo['por_forma']->map(fn ($f) => ['Recebido em ' . $f['label'], $f['total']])->values()->all())),
            new Relatorio('Entradas', ['Data', 'Cliente', 'Serviço', 'Profissional', 'Forma', 'Valor'],
                $entradas->map(fn ($p) => [$p->pago_em->format('d/m/Y H:i'), $p->agendamento->cliente->nome, $p->agendamento->servico->nome, $p->agendamento->profissional->nome_exibicao, $p->forma->label(), (float) $p->valor])->all()),
            new Relatorio('Despesas', ['Data', 'Descrição', 'Categoria', 'Pago', 'Valor'],
                $despesas->map(fn ($d) => [$d->data->format('d/m/Y'), $d->descricao, Despesa::CATEGORIAS[$d->categoria], $d->pago ? 'Sim' : 'Não', (float) $d->valor])->all()),
            new Relatorio('Comissões', ['Profissional', 'Atendimentos', 'Base', 'Comissão', 'Pendente', 'Paga'],
                $comissoes->map(fn ($c) => [$c->nome, (int) $c->atendimentos, (float) $c->base, (float) $c->total, (float) ($c->pendente ?? 0), (float) ($c->paga ?? 0)])->all()),
        ];

        $nome = 'financeiro-' . $de->format('Ymd') . '-' . $ate->format('Ymd');
        Atividade::registrar('exportou', null, null, ['relatorio' => 'financeiro', 'formato' => $request->input('formato')], 'Financeiro');

        if ($request->input('formato') === 'pdf') {
            return Pdf::loadView('painel.admin.exportacao.pdf', ['titulo' => 'Financeiro — ' . $de->format('d/m/Y') . ' a ' . $ate->format('d/m/Y'), 'relatorios' => $relatorios])
                ->setPaper('a4', 'landscape')->download($nome . '.pdf');
        }

        return Excel::download(new VariosRelatorios($relatorios), $nome . '.xlsx');
    }

    private function baixar(Request $request, Relatorio $relatorio, string $nome): Response
    {
        $nome .= '-' . now()->format('Ymd-His');
        Atividade::registrar('exportou', null, null, ['relatorio' => $relatorio->titulo, 'formato' => $request->input('formato'), 'linhas' => count($relatorio->linhas)], $relatorio->titulo);

        if ($request->input('formato') === 'pdf') {
            return Pdf::loadView('painel.admin.exportacao.pdf', ['titulo' => $relatorio->titulo, 'relatorios' => [$relatorio]])
                ->setPaper('a4', 'landscape')->download($nome . '.pdf');
        }

        return Excel::download($relatorio, $nome . '.xlsx');
    }
}
