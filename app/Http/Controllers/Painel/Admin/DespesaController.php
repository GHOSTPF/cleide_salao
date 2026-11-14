<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\Despesa;
use App\Support\Dinheiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DespesaController extends Controller
{
    public function create(): View
    {
        return view('painel.admin.financeiro.despesa-form', ['despesa' => new Despesa(['data' => today(), 'pago' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Despesa::create($this->dados($request));

        return redirect()->route('admin.financeiro.index', ['aba' => 'despesas'])->with('sucesso', 'Despesa lançada.');
    }

    public function edit(Despesa $despesa): View
    {
        return view('painel.admin.financeiro.despesa-form', ['despesa' => $despesa]);
    }

    public function update(Request $request, Despesa $despesa): RedirectResponse
    {
        $despesa->update($this->dados($request));

        return redirect()->route('admin.financeiro.index', ['aba' => 'despesas'])->with('sucesso', 'Despesa atualizada.');
    }

    public function destroy(Despesa $despesa): RedirectResponse
    {
        $despesa->delete();

        return back()->with('sucesso', 'Despesa excluída.');
    }

    private function dados(Request $request): array
    {
        $dados = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'categoria' => ['required', Rule::in(array_keys(Despesa::CATEGORIAS))],
            'valor' => ['required', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
            'data' => ['required', 'date'],
            'observacao' => ['nullable', 'string', 'max:1000'],
        ]);
        $dados['valor'] = Dinheiro::paraDecimal($dados['valor']);
        $dados['pago'] = $request->boolean('pago');

        return $dados;
    }
}
