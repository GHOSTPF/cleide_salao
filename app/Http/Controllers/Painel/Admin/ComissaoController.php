<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comissao;
use App\Models\Profissional;
use App\Services\FinanceiroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComissaoController extends Controller
{
    public function index(Request $request): View
    {
        [$de, $ate] = FinanceiroController::periodo($request);
        $status = $request->input('status', 'pendente');
        $profissionalId = $request->integer('profissional_id') ?: null;

        $comissoes = Comissao::with(['agendamento.cliente', 'agendamento.servico', 'profissional'])
            ->join('agendamentos', 'agendamentos.id', '=', 'comissoes.agendamento_id')
            ->whereBetween('agendamentos.concluido_em', [$de, $ate])
            ->when($status !== 'todas', fn ($q) => $q->where('comissoes.status', $status))
            ->when($profissionalId, fn ($q) => $q->where('comissoes.profissional_id', $profissionalId))
            ->orderBy('agendamentos.concluido_em', 'desc')
            ->select('comissoes.*')
            ->get();

        return view('painel.admin.financeiro.comissoes', [
            'de' => $de, 'ate' => $ate, 'status' => $status, 'profissionalId' => $profissionalId,
            'comissoes' => $comissoes,
            'profissionais' => Profissional::orderBy('nome')->pluck('nome', 'id'),
        ]);
    }

    public function pagar(Request $request, FinanceiroService $svc): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']])['ids'];
        $n = $svc->marcarComissoesPagas($ids);

        return back()->with('sucesso', $n . ' comissão(ões) marcada(s) como paga(s).');
    }
}
