<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAtividade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AtividadeController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['user_id', 'acao', 'entidade_tipo', 'entidade_id']);

        return view('painel.admin.atividades', [
            'filtros' => $filtros,
            'atividades' => LogAtividade::with('user')
                ->when($filtros['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
                ->when($filtros['acao'] ?? null, fn ($q, $v) => $q->where('acao', $v))
                ->when($filtros['entidade_tipo'] ?? null, fn ($q, $v) => $q->where('entidade_tipo', $v))
                ->when($filtros['entidade_id'] ?? null, fn ($q, $v) => $q->where('entidade_id', $v))
                ->latest('id')
                ->paginate(50)->withQueryString(),
            'usuarios' => User::orderBy('nome')->pluck('nome', 'id'),
            'acoes' => LogAtividade::distinct()->orderBy('acao')->pluck('acao', 'acao'),
            'tipos' => LogAtividade::distinct()->orderBy('entidade_tipo')->pluck('entidade_tipo', 'entidade_tipo'),
        ]);
    }
}
