<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ListaEspera;
use App\Services\ClienteService;
use App\Services\ListaEsperaService;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RelacionamentoController extends Controller
{
    public function retornos(ClienteService $svc): View
    {
        return view('painel.admin.relacionamento.retornos', ['retornos' => $svc->retornos(7)]);
    }

    public function aniversariantes(Request $request): View
    {
        $periodo = in_array($request->input('periodo'), ['dia', 'semana', 'mes'], true) ? $request->input('periodo') : 'semana';

        $query = Cliente::whereNotNull('data_nascimento');
        if ($periodo === 'dia') {
            $query->whereMonth('data_nascimento', now()->month)->whereDay('data_nascimento', now()->day);
        } elseif ($periodo === 'mes') {
            $query->whereMonth('data_nascimento', now()->month);
        } else {
            // Próximos 7 dias, inclusive na virada do ano: compara "MMDD"
            $dias = collect(range(0, 6))->map(fn ($i) => today()->addDays($i)->format('md'))->all();
            $query->whereIn(DB::raw("to_char(data_nascimento, 'MMDD')"), $dias);
        }

        $clientes = $query->get()->sortBy(fn ($c) => $c->data_nascimento->format('md'))->values();

        return view('painel.admin.relacionamento.aniversariantes', compact('clientes', 'periodo'));
    }

    public function inativas(Request $request): View
    {
        $dias = $request->integer('dias') ?: Setting::int('dias_cliente_inativa');

        $clientes = Cliente::where('ultima_visita_em', '<', now()->subDays($dias))
            ->whereDoesntHave('agendamentos', fn ($q) => $q->ativos()->where('inicio', '>', now()))
            ->orderBy('ultima_visita_em')
            ->paginate(40)->withQueryString();

        return view('painel.admin.relacionamento.inativas', compact('clientes', 'dias'));
    }

    public function listaEspera(Request $request): View
    {
        $status = $request->input('status', 'aguardando');

        return view('painel.admin.relacionamento.lista-espera', [
            'status' => $status,
            'entradas' => ListaEspera::with(['cliente', 'servico', 'profissional', 'agendamento'])
                ->when($status !== 'todas', fn ($q) => $q->where('status', $status))
                ->orderByRaw('vaga_liberada_em IS NULL')
                ->orderBy('data_desejada')
                ->paginate(40)->withQueryString(),
        ]);
    }

    public function statusListaEspera(Request $request, ListaEspera $entrada, ListaEsperaService $svc): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['contatada', 'expirada', 'aguardando'])]])['status'];

        if ($status === 'contatada') {
            $svc->marcarContatada($entrada);
        } else {
            $entrada->update(['status' => $status]);
        }

        return back()->with('sucesso', 'Lista de espera atualizada.');
    }
}
