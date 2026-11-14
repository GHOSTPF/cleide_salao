<?php

namespace App\Http\Controllers\Painel\Profissional;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\AtendimentoAnotacao;
use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ficha restrita: só clientes que a profissional atendeu ou tem agendadas,
 * só os atendimentos com ela e nenhum dado financeiro global.
 */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $profId = $request->user()->profissional->id;
        $q = trim((string) $request->input('q'));

        $clientes = Cliente::query()
            ->whereHas('agendamentos', fn ($a) => $a->where('profissional_id', $profId))
            ->when($q, fn ($w) => $w->where('nome', 'ilike', '%' . $q . '%'))
            ->withMax(['agendamentos as ultimo_atendimento' => fn ($a) => $a->where('profissional_id', $profId)->where('status', 'concluido')], 'inicio')
            ->withCount(['agendamentos as atendimentos_comigo' => fn ($a) => $a->where('profissional_id', $profId)->where('status', 'concluido')])
            ->orderBy('nome')
            ->paginate(30)->withQueryString();

        return view('painel.profissional.clientes.index', compact('clientes', 'q'));
    }

    public function show(Request $request, Cliente $cliente, ClienteService $svc): View
    {
        $this->authorize('view', $cliente);
        $profId = $request->user()->profissional->id;

        return view('painel.profissional.clientes.show', [
            'cliente' => $cliente,
            'agendamentos' => Agendamento::with('servico')->where('cliente_id', $cliente->id)->where('profissional_id', $profId)->orderByDesc('inicio')->get(),
            'anotacoes' => AtendimentoAnotacao::with('profissional')->where('cliente_id', $cliente->id)->latest('created_at')->get(),
            'indicadores' => $svc->indicadores($cliente, $profId),
        ]);
    }
}
