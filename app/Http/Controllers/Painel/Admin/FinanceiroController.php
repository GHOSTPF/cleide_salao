<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\Despesa;
use App\Models\Pagamento;
use App\Services\FinanceiroService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinanceiroController extends Controller
{
    public static function periodo(Request $request): array
    {
        $de = $request->filled('de') ? Carbon::parse($request->input('de')) : now()->startOfMonth();
        $ate = $request->filled('ate') ? Carbon::parse($request->input('ate')) : now()->endOfMonth();

        return [$de->startOfDay(), $ate->endOfDay()];
    }

    public function index(Request $request, FinanceiroService $svc): View
    {
        [$de, $ate] = self::periodo($request);
        $aba = in_array($request->input('aba'), ['entradas', 'despesas'], true) ? $request->input('aba') : 'entradas';
        $forma = $request->input('forma');

        $entradas = Pagamento::with(['agendamento.cliente', 'agendamento.servico', 'agendamento.profissional'])
            ->whereBetween('pago_em', [$de, $ate])
            ->when($forma, fn ($q) => $q->where('forma', $forma))
            ->orderByDesc('pago_em')
            ->paginate(30, ['*'], 'pagina_entradas')->withQueryString();

        $despesas = Despesa::whereBetween('data', [$de->toDateString(), $ate->toDateString()])
            ->orderByDesc('data')->paginate(30, ['*'], 'pagina_despesas')->withQueryString();

        return view('painel.admin.financeiro.index', [
            'de' => $de, 'ate' => $ate, 'aba' => $aba, 'forma' => $forma,
            'resumo' => $svc->resumo($de, $ate),
            'comissoes' => $svc->comissoesPorProfissional($de, $ate),
            'entradas' => $entradas,
            'despesas' => $despesas,
        ]);
    }
}
