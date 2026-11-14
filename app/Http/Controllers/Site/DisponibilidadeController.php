<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Servico;
use App\Services\DisponibilidadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisponibilidadeController extends Controller
{
    /** GET /horarios-disponiveis?servico_id=&profissional_id=&data= */
    public function index(Request $request, DisponibilidadeService $disponibilidade): JsonResponse
    {
        $dados = $request->validate([
            'servico_id' => ['required', 'integer'],
            'profissional_id' => ['nullable', 'integer'],
            'data' => ['required', 'date_format:Y-m-d'],
        ]);

        $servico = Servico::noSite()->find($dados['servico_id']);
        if (! $servico) {
            return response()->json([]);
        }

        return response()->json(
            $disponibilidade->slots($servico, $dados['profissional_id'] ?? null, $dados['data'], DisponibilidadeService::MODO_SITE)
        );
    }
}
