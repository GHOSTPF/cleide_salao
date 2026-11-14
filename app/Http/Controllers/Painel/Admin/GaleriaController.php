<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\GaleriaFoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GaleriaController extends Controller
{
    public function index(): View
    {
        return view('painel.admin.site.galeria', ['fotos' => GaleriaFoto::orderBy('categoria')->orderBy('ordem')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'fotos' => ['required', 'array', 'min:1', 'max:20'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'categoria' => ['required', Rule::in(array_keys(GaleriaFoto::CATEGORIAS))],
            'titulo' => ['nullable', 'string', 'max:120'],
            'destaque' => ['nullable', 'boolean'],
        ]);

        $ordem = (int) GaleriaFoto::where('categoria', $dados['categoria'])->max('ordem');
        foreach ($request->file('fotos') as $arquivo) {
            GaleriaFoto::create([
                'titulo' => $dados['titulo'] ?? null,
                'categoria' => $dados['categoria'],
                'caminho' => $arquivo->store('galeria', 'public'),
                'destaque' => $request->boolean('destaque'),
                'ordem' => ++$ordem,
            ]);
        }

        return back()->with('sucesso', count($request->file('fotos')) . ' foto(s) enviada(s).');
    }

    public function update(Request $request, GaleriaFoto $foto): RedirectResponse
    {
        $dados = $request->validate([
            'titulo' => ['nullable', 'string', 'max:120'],
            'categoria' => ['required', Rule::in(array_keys(GaleriaFoto::CATEGORIAS))],
            'ordem' => ['nullable', 'integer', 'min:0'],
        ]);
        $dados['destaque'] = $request->boolean('destaque');
        $dados['ordem'] = (int) ($dados['ordem'] ?? 0);
        $foto->update($dados);

        return back()->with('sucesso', 'Foto atualizada.');
    }

    public function destroy(GaleriaFoto $foto): RedirectResponse
    {
        if (! str_starts_with($foto->caminho, '/') && ! str_starts_with($foto->caminho, 'http')) {
            Storage::disk('public')->delete($foto->caminho);
        }
        $foto->delete();

        return back()->with('sucesso', 'Foto removida.');
    }
}
