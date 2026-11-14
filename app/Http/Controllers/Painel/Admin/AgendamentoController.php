<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Enums\OrigemAgendamento;
use App\Enums\StatusAgendamento;
use App\Exceptions\AgendamentoException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Painel\Concerns\AcoesAgendamento;
use App\Http\Requests\Painel\AgendamentoPainelRequest;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ListaEspera;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\AgendamentoService;
use App\Services\ClienteService;
use App\Services\ReagendamentoService;
use App\Support\Atividade;
use App\Support\Dinheiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendamentoController extends Controller
{
    use AcoesAgendamento;

    protected function prefixo(): string
    {
        return 'admin.';
    }

    public function index(Request $request): View
    {
        $filtros = $request->only(['de', 'ate', 'status', 'profissional_id', 'servico_id', 'origem', 'q']);

        $agendamentos = $this->consulta($filtros)
            ->with(['cliente', 'profissional', 'servico'])
            ->orderByDesc('inicio')
            ->paginate(25)
            ->withQueryString();

        return view('painel.admin.agendamentos.index', [
            'agendamentos' => $agendamentos,
            'filtros' => $filtros,
            'profissionais' => Profissional::orderBy('nome')->pluck('nome', 'id'),
            'servicos' => Servico::orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    /** Consulta filtrada reaproveitada pela exportação. */
    public static function consulta(array $f)
    {
        return Agendamento::query()
            ->when($f['de'] ?? null, fn ($q, $v) => $q->where('inicio', '>=', \Illuminate\Support\Carbon::parse($v)->startOfDay()))
            ->when($f['ate'] ?? null, fn ($q, $v) => $q->where('inicio', '<=', \Illuminate\Support\Carbon::parse($v)->endOfDay()))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['profissional_id'] ?? null, fn ($q, $v) => $q->where('profissional_id', $v))
            ->when($f['servico_id'] ?? null, fn ($q, $v) => $q->where('servico_id', $v))
            ->when($f['origem'] ?? null, fn ($q, $v) => $q->where('origem', $v))
            ->when($f['q'] ?? null, function ($q, $v) {
                $digitos = preg_replace('/\D/', '', $v);
                $q->where(fn ($w) => $w->where('codigo', 'ilike', '%' . $v . '%')
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'ilike', '%' . $v . '%')
                        ->when(strlen($digitos) >= 4, fn ($c2) => $c2->orWhere('telefone', 'like', '%' . $digitos . '%'))));
            });
    }

    public function pendentes(): View
    {
        return view('painel.admin.agendamentos.pendentes', [
            'pendentes' => Agendamento::with(['cliente', 'profissional', 'servico'])
                ->where('status', StatusAgendamento::Pendente)
                ->orderBy('inicio')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $entrada = $request->filled('lista_espera_id') ? ListaEspera::with('cliente')->find($request->integer('lista_espera_id')) : null;
        $cliente = $request->filled('cliente_id') ? Cliente::find($request->integer('cliente_id')) : $entrada?->cliente;

        return view('painel.admin.agendamentos.create', [
            'servicos' => Servico::ativos()->with(['categoria', 'profissionais' => fn ($q) => $q->where('profissionais.ativo', true)])->orderBy('ordem')->get(),
            'cliente' => $cliente,
            'entrada' => $entrada,
            'prefill' => [
                'servico_id' => old('servico_id', $request->input('servico_id', $entrada?->servico_id)),
                'profissional_id' => old('profissional_id', $request->input('profissional_id', $entrada?->profissional_id)),
                'data' => old('data', $request->input('data', $entrada?->data_desejada?->toDateString() ?? today()->toDateString())),
                'hora' => old('hora', $request->input('hora')),
            ],
        ]);
    }

    public function store(AgendamentoPainelRequest $request, AgendamentoService $svc): RedirectResponse
    {
        try {
            $ag = $svc->criarPeloPainel($request->validated(), $request->user());
        } catch (AgendamentoException $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        return redirect()->route('admin.agendamentos.show', $ag)
            ->with('sucesso', 'Agendamento ' . $ag->codigo . ' criado para ' . $ag->inicio->format('d/m/Y \à\s H:i') . '.');
    }

    public function show(Agendamento $agendamento, ReagendamentoService $reag): View
    {
        $agendamento->load(['cliente', 'profissional', 'servico', 'historico.user', 'pagamentos', 'comissao', 'anotacoes.profissional', 'criadoPor']);

        return view('painel.admin.agendamentos.show', [
            'ag' => $agendamento,
            'cadeia' => $reag->cadeia($agendamento),
            'prefixo' => 'admin.',
        ]);
    }

    public function update(Request $request, Agendamento $agendamento): RedirectResponse
    {
        $dados = $request->validate([
            'observacoes_internas' => ['nullable', 'string', 'max:2000'],
            'preco_previsto' => ['nullable', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
        ]);

        $alteracoes = ['observacoes_internas' => $dados['observacoes_internas'] ?? null];
        if (isset($dados['preco_previsto']) && $agendamento->status !== StatusAgendamento::Concluido) {
            $alteracoes['preco_previsto'] = Dinheiro::paraDecimal($dados['preco_previsto']);
        }

        Atividade::acao(isset($alteracoes['preco_previsto']) && (float) $alteracoes['preco_previsto'] !== (float) $agendamento->preco_previsto ? 'alterou_valor' : 'alterou',
            fn () => $agendamento->update($alteracoes));

        return redirect()->route('admin.agendamentos.show', $agendamento)->with('sucesso', 'Agendamento atualizado.');
    }

    public function estornar(Request $request, Agendamento $agendamento, AgendamentoService $svc): RedirectResponse
    {
        $this->authorize('estornar', $agendamento);
        $motivo = $request->validate(['motivo' => ['nullable', 'string', 'max:255']])['motivo'] ?? null;

        return $this->executar(fn () => $svc->estornar($agendamento, $request->user(), $motivo), 'Estorno realizado. Pagamento e comissão revertidos.', $agendamento);
    }

    public function desfazerFalta(Agendamento $agendamento, AgendamentoService $svc): RedirectResponse
    {
        $this->authorize('desfazerFalta', $agendamento);

        return $this->executar(fn () => $svc->desfazerFalta($agendamento, auth()->user()), 'Falta desfeita.', $agendamento);
    }

    public function nomeDivergente(Request $request, Agendamento $agendamento, ClienteService $clientes): RedirectResponse
    {
        $atualizar = $request->input('acao') === 'atualizar';
        $clientes->resolverNomeDivergente($agendamento->load('cliente'), $atualizar);

        return back()->with('sucesso', $atualizar ? 'Cadastro atualizado com o novo nome.' : 'Nome do cadastro mantido.');
    }
}
