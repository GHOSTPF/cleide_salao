<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Feriados e fechamento do salão = bloqueios com profissional_id NULL. */
class FeriadoController extends Controller
{
    public function index(): View
    {
        return view('painel.admin.feriados', [
            'bloqueios' => BloqueioAgenda::whereNull('profissional_id')->where('fim', '>=', now()->subDays(30))->orderBy('inicio')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = ProfissionalController::validarBloqueio($request);
        BloqueioAgenda::create(['profissional_id' => null] + $dados);

        $afetados = Agendamento::ativos()->where('inicio', '<', $dados['fim'])->where('fim', '>', $dados['inicio'])->count();

        return back()->with('sucesso', 'Fechamento cadastrado.' . ($afetados ? ' Atenção: há ' . $afetados . ' agendamento(s) nesse período para reagendar.' : ''));
    }

    public function destroy(BloqueioAgenda $bloqueio): RedirectResponse
    {
        abort_unless($bloqueio->profissional_id === null, 404);
        $bloqueio->delete();

        return back()->with('sucesso', 'Fechamento removido.');
    }
}
