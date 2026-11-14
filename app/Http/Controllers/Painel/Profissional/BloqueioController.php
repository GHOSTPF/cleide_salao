<?php

namespace App\Http\Controllers\Painel\Profissional;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Painel\Admin\ProfissionalController;
use App\Models\BloqueioAgenda;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Opcional: habilitado por `profissional_gerencia_bloqueios`. */
class BloqueioController extends Controller
{
    private function garantirHabilitado(): void
    {
        abort_unless(Setting::bool('profissional_gerencia_bloqueios'), 403, 'O gerenciamento de bloqueios pelas profissionais está desativado.');
    }

    public function index(Request $request): View
    {
        $this->garantirHabilitado();

        return view('painel.profissional.bloqueios', [
            'bloqueios' => BloqueioAgenda::where('profissional_id', $request->user()->profissional->id)
                ->where('fim', '>=', now())->orderBy('inicio')->get(),
            'fechamentos' => BloqueioAgenda::whereNull('profissional_id')->where('fim', '>=', now())->orderBy('inicio')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->garantirHabilitado();
        BloqueioAgenda::create(['profissional_id' => $request->user()->profissional->id] + ProfissionalController::validarBloqueio($request));

        return back()->with('sucesso', 'Bloqueio adicionado à sua agenda.');
    }

    public function destroy(Request $request, BloqueioAgenda $bloqueio): RedirectResponse
    {
        $this->garantirHabilitado();
        abort_unless($bloqueio->profissional_id === $request->user()->profissional->id, 403);
        $bloqueio->delete();

        return back()->with('sucesso', 'Bloqueio removido.');
    }
}
