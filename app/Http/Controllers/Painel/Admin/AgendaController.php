<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Enums\StatusAgendamento;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Painel\Concerns\AcoesAgendamento;
use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use App\Models\Profissional;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AgendaController extends Controller
{
    use AcoesAgendamento;

    protected function prefixo(): string
    {
        return 'admin.';
    }

    public function index(Request $request): View
    {
        $data = $request->filled('data') ? Carbon::parse($request->input('data'))->startOfDay() : today();
        $visao = $request->input('visao') === 'semana' ? 'semana' : 'dia';
        $profissionalId = $request->integer('profissional_id') ?: null;

        $profissionais = Profissional::ativas()->with('horarios')->get();
        $colunas = $profissionalId ? $profissionais->where('id', $profissionalId)->values() : $profissionais;

        [$de, $ate] = $visao === 'semana'
            ? [$data->copy()->startOfWeek(), $data->copy()->endOfWeek()]
            : [$data->copy(), $data->copy()->endOfDay()];

        $agendamentos = Agendamento::with(['cliente', 'servico', 'profissional'])
            ->whereBetween('inicio', [$de, $ate])
            ->whereNotIn('status', [StatusAgendamento::Reagendado->value, StatusAgendamento::Cancelado->value])
            ->when($profissionalId, fn ($q) => $q->where('profissional_id', $profissionalId))
            ->orderBy('inicio')
            ->get();

        $bloqueios = BloqueioAgenda::with('profissional')
            ->where('inicio', '<', $ate)->where('fim', '>', $de)
            ->when($profissionalId, fn ($q) => $q->where(fn ($w) => $w->whereNull('profissional_id')->orWhere('profissional_id', $profissionalId)))
            ->get();

        // Janela de horas exibida na visão dia (a partir dos expedientes)
        $horarios = $colunas->flatMap->horarios->where('dia_semana', $data->dayOfWeek);
        $horaIni = (int) min(8, (int) substr($horarios->min('hora_inicio') ?? '08', 0, 2));
        $horaFim = (int) max(19, (int) substr($horarios->max('hora_fim') ?? '19', 0, 2) + 1);
        foreach ($agendamentos as $a) {
            $horaIni = min($horaIni, $a->inicio->hour);
            $horaFim = max($horaFim, $a->fim->hour + 1);
        }

        return view('painel.admin.agenda', compact('data', 'visao', 'profissionalId', 'profissionais', 'colunas', 'agendamentos', 'bloqueios', 'horaIni', 'horaFim', 'de', 'ate'));
    }
}
