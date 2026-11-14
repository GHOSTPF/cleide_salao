<?php

namespace App\Http\Controllers\Painel\Profissional;

use App\Enums\StatusAgendamento;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Painel\Concerns\AcoesAgendamento;
use App\Models\Agendamento;
use App\Services\ReagendamentoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    use AcoesAgendamento;

    protected function prefixo(): string
    {
        return 'profissional.';
    }

    public function index(Request $request): View
    {
        $prof = $request->user()->profissional;
        $aba = in_array($request->input('aba'), ['hoje', 'proximos', 'historico'], true) ? $request->input('aba') : 'hoje';

        $query = Agendamento::with(['cliente', 'servico'])->where('profissional_id', $prof->id);

        $agendamentos = match ($aba) {
            'hoje' => $query->doDia(today())->whereNotIn('status', [StatusAgendamento::Reagendado->value])->orderBy('inicio')->get(),
            'proximos' => $query->where('inicio', '>=', today()->addDay())->whereIn('status', [StatusAgendamento::Pendente->value, StatusAgendamento::Confirmado->value])->orderBy('inicio')->limit(100)->get(),
            'historico' => $query->where('inicio', '<', today())->orderByDesc('inicio')->paginate(30)->withQueryString(),
        };

        return view('painel.profissional.agenda', compact('agendamentos', 'aba', 'prof'));
    }

    public function show(Agendamento $agendamento, ReagendamentoService $reag): View
    {
        $this->authorize('view', $agendamento);
        $agendamento->load(['cliente', 'profissional', 'servico', 'historico.user', 'pagamentos', 'comissao', 'anotacoes.profissional']);

        return view('painel.profissional.agendamento', [
            'ag' => $agendamento,
            'cadeia' => $reag->cadeia($agendamento),
            'prefixo' => 'profissional.',
        ]);
    }
}
