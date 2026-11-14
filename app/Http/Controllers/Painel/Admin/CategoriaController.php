<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaServico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('painel.admin.servicos.categorias', ['categorias' => CategoriaServico::withCount('servicos')->orderBy('ordem')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        CategoriaServico::create($request->validate(['nome' => ['required', 'string', 'max:60'], 'ordem' => ['nullable', 'integer', 'min:0']]));

        return back()->with('sucesso', 'Categoria criada.');
    }

    public function update(Request $request, CategoriaServico $categoria): RedirectResponse
    {
        $categoria->update($request->validate(['nome' => ['required', 'string', 'max:60'], 'ordem' => ['nullable', 'integer', 'min:0']]));

        return back()->with('sucesso', 'Categoria atualizada.');
    }

    public function destroy(CategoriaServico $categoria): RedirectResponse
    {
        if ($categoria->servicos()->exists()) {
            return back()->with('erro', 'Esta categoria tem serviços. Mova-os antes de excluir.');
        }
        $categoria->delete();

        return back()->with('sucesso', 'Categoria excluída.');
    }
}
