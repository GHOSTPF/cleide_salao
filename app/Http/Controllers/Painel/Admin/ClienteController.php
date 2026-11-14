<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\AtendimentoAnotacao;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Rules\TelefoneValido;
use App\Services\ClienteService;
use App\Services\ReagendamentoService;
use App\Support\Telefone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['q', 'filtro', 'ordem']);

        $clientes = self::consulta($filtros)->paginate(30)->withQueryString();

        return view('painel.admin.clientes.index', compact('clientes', 'filtros'));
    }

    public static function consulta(array $f)
    {
        return Cliente::query()
            ->when($f['q'] ?? null, function ($q, $v) {
                $digitos = preg_replace('/\D/', '', $v);
                $q->where(fn ($w) => $w->where('nome', 'ilike', '%' . $v . '%')
                    ->when(strlen($digitos) >= 4, fn ($w2) => $w2->orWhere('telefone', 'like', '%' . $digitos . '%')));
            })
            ->when(($f['filtro'] ?? null) === 'sumidas', fn ($q) => $q->where('ultima_visita_em', '<', now()->subDays(60)))
            ->when(($f['filtro'] ?? null) === 'aniversariantes', fn ($q) => $q->whereMonth('data_nascimento', now()->month))
            ->when(($f['filtro'] ?? null) === 'bloqueadas', fn ($q) => $q->where('bloqueada_online', true))
            ->when(($f['ordem'] ?? null) === 'gasto', fn ($q) => $q->orderByDesc('total_gasto'))
            ->when(($f['ordem'] ?? null) === 'visita', fn ($q) => $q->orderByRaw('ultima_visita_em DESC NULLS LAST'))
            ->orderBy('nome');
    }

    /** Autocomplete do "Novo agendamento" (somente painel; o site nunca busca por telefone). */
    public function buscar(Request $request): JsonResponse
    {
        $termo = trim((string) $request->input('q'));
        if (mb_strlen($termo) < 2) {
            return response()->json([]);
        }

        return response()->json(
            self::consulta(['q' => $termo])->limit(10)->get()
                ->map(fn ($c) => ['id' => $c->id, 'nome' => $c->nome, 'telefone' => $c->telefone_formatado])
        );
    }

    public function show(Cliente $cliente, ClienteService $svc, ReagendamentoService $reag): View
    {
        $cliente->load(['anotacoes.profissional', 'profissionalPreferida']);

        $agendamentos = Agendamento::with(['servico', 'profissional', 'pagamentos', 'reagendadoPara'])
            ->where('cliente_id', $cliente->id)
            ->orderByDesc('inicio')
            ->get();

        return view('painel.admin.clientes.show', [
            'cliente' => $cliente,
            'agendamentos' => $agendamentos,
            'proximo' => $agendamentos->filter(fn ($a) => $a->inicio->isFuture() && in_array($a->status->value, ['pendente', 'confirmado'], true))->sortBy('inicio')->first(),
            'indicadores' => $svc->indicadores($cliente),
            'outrasClientes' => Cliente::where('id', '!=', $cliente->id)->orderBy('nome')->get(['id', 'nome', 'telefone']),
        ]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('painel.admin.clientes.edit', [
            'cliente' => $cliente,
            'profissionais' => Profissional::ativas()->pluck('nome', 'id'),
        ]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'telefone' => ['required', 'string', new TelefoneValido],
            'email' => ['nullable', 'email', 'max:255'],
            'data_nascimento' => ['nullable', 'date', 'before:today'],
            'instagram' => ['nullable', 'string', 'max:60'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'origem' => ['required', Rule::in(['site', 'balcao', 'whatsapp', 'indicacao'])],
            'profissional_preferida_id' => ['nullable', 'exists:profissionais,id'],
        ]);
        $dados['telefone'] = Telefone::normalizar($dados['telefone']);
        $dados['instagram'] = isset($dados['instagram']) ? ltrim($dados['instagram'], '@') : null;

        $existe = Cliente::withTrashed()->where('telefone', $dados['telefone'])->where('id', '!=', $cliente->id)->first();
        if ($existe) {
            return back()->withInput()->with('erro', 'Este WhatsApp já pertence a ' . $existe->nome . '. Use "Mesclar" na ficha para unificar as clientes.');
        }

        $cliente->update($dados);

        return redirect()->route('admin.clientes.show', $cliente)->with('sucesso', 'Cadastro atualizado.');
    }

    public function bloquear(Request $request, Cliente $cliente, ClienteService $svc): RedirectResponse
    {
        $motivo = $request->validate(['motivo' => ['required', 'string', 'max:255']])['motivo'];
        $svc->bloquear($cliente, $motivo);

        return back()->with('sucesso', 'Cliente bloqueada para agendamento online. Ela ainda pode ser agendada pelo painel.');
    }

    public function desbloquear(Cliente $cliente, ClienteService $svc): RedirectResponse
    {
        $svc->desbloquear($cliente);

        return back()->with('sucesso', 'Cliente desbloqueada para agendamento online.');
    }

    public function anotacao(Request $request, Cliente $cliente): RedirectResponse
    {
        $dados = $request->validate(['anotacao' => ['required', 'string', 'max:2000']]);
        AtendimentoAnotacao::create(['cliente_id' => $cliente->id, 'anotacao' => $dados['anotacao']]);

        return back()->with('sucesso', 'Anotação salva.');
    }

    public function duplicadas(ClienteService $svc): View
    {
        return view('painel.admin.clientes.duplicadas', ['pares' => $svc->possiveisDuplicadas(100)]);
    }

    public function mesclar(Request $request, ClienteService $svc): RedirectResponse
    {
        $dados = $request->validate([
            'principal_id' => ['required', 'integer', 'exists:clientes,id'],
            'secundaria_id' => ['required', 'integer', 'exists:clientes,id', 'different:principal_id'],
        ]);

        $principal = $svc->mesclar(Cliente::findOrFail($dados['principal_id']), Cliente::findOrFail($dados['secundaria_id']));

        return redirect()->route('admin.clientes.show', $principal)->with('sucesso', 'Clientes mescladas. Histórico unificado e totais recalculados.');
    }

    public function ignorarDuplicidade(Request $request, ClienteService $svc): RedirectResponse
    {
        $dados = $request->validate([
            'a_id' => ['required', 'integer', 'exists:clientes,id'],
            'b_id' => ['required', 'integer', 'exists:clientes,id'],
        ]);
        $svc->ignorarDuplicidade(Cliente::findOrFail($dados['a_id']), Cliente::findOrFail($dados['b_id']));

        return back()->with('sucesso', 'Par ignorado. Não será sugerido novamente.');
    }
}
