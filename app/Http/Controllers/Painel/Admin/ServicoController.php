<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaServico;
use App\Models\Servico;
use App\Support\Dinheiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServicoController extends Controller
{
    public function index(): View
    {
        return view('painel.admin.servicos.index', [
            'categorias' => CategoriaServico::with(['servicos' => fn ($q) => $q->withCount('profissionais')])->orderBy('ordem')->get(),
        ]);
    }

    public function create(): View
    {
        return view('painel.admin.servicos.form', ['servico' => new Servico(['ativo' => true, 'exibir_no_site' => true, 'duracao_minutos' => 60]), 'categorias' => CategoriaServico::orderBy('ordem')->pluck('nome', 'id')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $servico = Servico::create($this->dados($request));

        return redirect()->route('admin.servicos.index')->with('sucesso', 'Serviço "' . $servico->nome . '" criado. Vincule as profissionais que o realizam.');
    }

    public function edit(Servico $servico): View
    {
        return view('painel.admin.servicos.form', ['servico' => $servico, 'categorias' => CategoriaServico::orderBy('ordem')->pluck('nome', 'id')]);
    }

    public function update(Request $request, Servico $servico): RedirectResponse
    {
        $servico->update($this->dados($request, $servico));

        return redirect()->route('admin.servicos.index')->with('sucesso', 'Serviço atualizado.');
    }

    public function destroy(Servico $servico): RedirectResponse
    {
        if (\App\Models\Agendamento::where('servico_id', $servico->id)->exists()) {
            $servico->update(['ativo' => false]);

            return back()->with('sucesso', 'O serviço tem histórico de agendamentos e foi desativado (não excluído).');
        }
        if ($servico->foto) {
            Storage::disk('public')->delete($servico->foto);
        }
        $servico->delete();

        return back()->with('sucesso', 'Serviço excluído.');
    }

    private function dados(Request $request, ?Servico $servico = null): array
    {
        $dados = $request->validate([
            'categoria_servico_id' => ['required', 'exists:categorias_servico,id'],
            'nome' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'duracao_minutos' => ['required', 'integer', 'min:5', 'max:720'],
            'intervalo_apos_minutos' => ['nullable', 'integer', 'min:0', 'max:240'],
            'preco' => ['required', 'regex:/^\d{1,7}([.,]\d{1,2})?$/'],
            'retorno_sugerido_dias' => ['nullable', 'integer', 'min:1', 'max:730'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'remover_foto' => ['nullable', 'boolean'],
        ]);

        $dados['preco'] = Dinheiro::paraDecimal($dados['preco']);
        $dados['intervalo_apos_minutos'] = (int) ($dados['intervalo_apos_minutos'] ?? 0);
        $dados['ordem'] = (int) ($dados['ordem'] ?? 0);
        foreach (['preco_a_partir_de', 'requer_avaliacao', 'ativo', 'exibir_no_site'] as $flag) {
            $dados[$flag] = $request->boolean($flag);
        }

        $base = Str::slug($dados['nome']);
        $slug = $base;
        $i = 2;
        while (Servico::where('slug', $slug)->when($servico, fn ($q) => $q->where('id', '!=', $servico->id))->exists()) {
            $slug = $base . '-' . $i++;
        }
        $dados['slug'] = $slug;

        if ($request->hasFile('foto')) {
            if ($servico?->foto) {
                Storage::disk('public')->delete($servico->foto);
            }
            $dados['foto'] = $request->file('foto')->store('servicos', 'public');
        } elseif ($request->boolean('remover_foto') && $servico?->foto) {
            Storage::disk('public')->delete($servico->foto);
            $dados['foto'] = null;
        } else {
            unset($dados['foto']);
        }
        unset($dados['remover_foto']);

        return $dados;
    }
}
