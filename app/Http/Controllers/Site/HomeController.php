<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CategoriaServico;
use App\Models\Depoimento;
use App\Models\GaleriaFoto;
use App\Models\Profissional;
use App\Support\Setting;
use App\Support\WhatsAppLink;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $categorias = CategoriaServico::query()
            ->with(['servicos' => fn ($q) => $q->noSite()->with(['profissionais' => fn ($p) => $p->where('profissionais.ativo', true)->orderBy('ordem')])])
            ->orderBy('ordem')
            ->get()
            ->filter(fn ($c) => $c->servicos->isNotEmpty())
            ->values();

        // Dados enxutos para o formulário (Alpine): serviço -> profissionais aptas
        $servicosJs = $categorias->flatMap->servicos->mapWithKeys(fn ($s) => [$s->id => [
            'id' => $s->id,
            'nome' => $s->nome,
            'duracao' => $s->duracao_formatada,
            'preco' => $s->preco_formatado,
            'requer_avaliacao' => $s->requer_avaliacao,
            'profissionais' => $s->profissionais->map(fn ($p) => ['id' => $p->id, 'nome' => $p->nome_exibicao])->values(),
        ]]);

        return view('site.home', [
            'config' => Setting::todas(),
            'categorias' => $categorias,
            'servicosJs' => $servicosJs,
            'equipe' => Profissional::ativas()->get(),
            'galeria' => GaleriaFoto::orderByDesc('destaque')->orderBy('ordem')->limit(24)->get(),
            'depoimentos' => Depoimento::where('ativo', true)->orderBy('ordem')->get(),
            'linkWhatsapp' => WhatsAppLink::falarComSalao(),
            'dataMinima' => today()->toDateString(),
            'dataMaxima' => today()->addDays(Setting::int('dias_maximos_agendamento'))->toDateString(),
        ]);
    }
}
