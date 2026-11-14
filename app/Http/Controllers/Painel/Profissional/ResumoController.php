<?php

namespace App\Http\Controllers\Painel\Profissional;

use App\Enums\StatusAgendamento;
use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Comissao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ResumoController extends Controller
{
    public function index(Request $request): View
    {
        $prof = $request->user()->profissional;
        $mes = $request->filled('mes') ? Carbon::createFromFormat('Y-m', $request->input('mes'))->startOfMonth() : now()->startOfMonth();
        $fim = $mes->copy()->endOfMonth();

        $concluidos = Agendamento::with(['cliente', 'servico', 'comissao'])
            ->where('profissional_id', $prof->id)
            ->where('status', StatusAgendamento::Concluido)
            ->whereBetween('concluido_em', [$mes, $fim])
            ->orderByDesc('concluido_em')
            ->get();

        return view('painel.profissional.resumo', [
            'prof' => $prof,
            'mes' => $mes,
            'concluidos' => $concluidos,
            'faltas' => Agendamento::where('profissional_id', $prof->id)->where('status', StatusAgendamento::NaoCompareceu)->whereBetween('inicio', [$mes, $fim])->count(),
            'comissaoMes' => (float) $concluidos->sum(fn ($a) => (float) ($a->comissao?->valor ?? 0)),
            'pendenteTotal' => (float) Comissao::where('profissional_id', $prof->id)->where('status', 'pendente')->sum('valor'),
            'pagasMes' => (float) Comissao::where('profissional_id', $prof->id)->where('status', 'paga')->whereBetween('pago_em', [$mes, $fim])->sum('valor'),
        ]);
    }
}
