<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\Depoimento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepoimentoController extends Controller
{
    public function index(): View
    {
        return view('painel.admin.site.depoimentos', ['depoimentos' => Depoimento::orderBy('ordem')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Depoimento::create($this->dados($request));

        return back()->with('sucesso', 'Depoimento adicionado.');
    }

    public function update(Request $request, Depoimento $depoimento): RedirectResponse
    {
        $depoimento->update($this->dados($request));

        return back()->with('sucesso', 'Depoimento atualizado.');
    }

    public function destroy(Depoimento $depoimento): RedirectResponse
    {
        $depoimento->delete();

        return back()->with('sucesso', 'Depoimento removido.');
    }

    private function dados(Request $request): array
    {
        $dados = $request->validate([
            'nome_cliente' => ['required', 'string', 'max:80'],
            'texto' => ['required', 'string', 'max:600'],
            'nota' => ['required', 'integer', 'between:1,5'],
            'ordem' => ['nullable', 'integer', 'min:0'],
        ]);
        $dados['ativo'] = $request->boolean('ativo');
        $dados['ordem'] = (int) ($dados['ordem'] ?? 0);

        return $dados;
    }
}
