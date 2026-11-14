<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('painel.perfil', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        $request->user()->update($dados);

        return redirect()->route('perfil.edit')->with('sucesso', 'Perfil atualizado.');
    }
}
