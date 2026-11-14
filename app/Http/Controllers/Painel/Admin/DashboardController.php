<?php

namespace App\Http\Controllers\Painel\Admin;

use App\Enums\OrigemAgendamento;
use App\Enums\StatusAgendamento;
use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ListaEspera;
use App\Models\Pagamento;
use App\Services\ClienteService;
use App\Services\FinanceiroService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(FinanceiroService $financeiro, ClienteService $clientes): View
    {
        $hoje = today();
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        $doDia = Agendamento::doDia($hoje)->whereIn('status', StatusAgendamento::ativos())
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $faturamentoMes = (float) Pagamento::whereBetween('pago_em', [$inicioMes, $fimMes])->sum('valor');
        $concluidosMes = Agendamento::where('status', StatusAgendamento::Concluido)->whereBetween('concluido_em', [$inicioMes, $fimMes])->count();
        $faltasMes = Agendamento::where('status', StatusAgendamento::NaoCompareceu)->whereBetween('inicio', [$inicioMes, now()])->count();

        $cards = [
            'hoje_total' => (int) $doDia->sum(),
            'hoje_confirmados' => (int) ($doDia['confirmado'] ?? 0),
            'hoje_pendentes' => (int) ($doDia['pendente'] ?? 0),
            'faturamento_hoje' => (float) Pagamento::whereBetween('pago_em', [$hoje, $hoje->copy()->endOfDay()])->sum('valor'),
            'faturamento_mes' => $faturamentoMes,
            'ticket_medio' => $concluidosMes ? $faturamentoMes / $concluidosMes : 0,
            'clientes_novas' => Cliente::whereBetween('created_at', [$inicioMes, $fimMes])->count(),
            'taxa_faltas' => ($concluidosMes + $faltasMes) ? round($faltasMes * 100 / ($concluidosMes + $faltasMes), 1) : 0,
        ];

        $proximos = Agendamento::with(['cliente', 'profissional', 'servico'])
            ->whereIn('status', [StatusAgendamento::Pendente, StatusAgendamento::Confirmado])
            ->where('inicio', '>=', now()->subMinutes(30))
            ->orderBy('inicio')->limit(8)->get();

        $rankingServicos = DB::table('agendamentos')
            ->join('servicos', 'servicos.id', '=', 'agendamentos.servico_id')
            ->where('agendamentos.status', 'concluido')
            ->whereBetween('agendamentos.concluido_em', [$inicioMes, $fimMes])
            ->groupBy('servicos.id', 'servicos.nome')
            ->selectRaw('servicos.nome, count(*) as total, sum(agendamentos.valor_cobrado) as valor')
            ->orderByDesc('total')->limit(5)->get();

        return view('painel.admin.dashboard', [
            'cards' => $cards,
            'proximos' => $proximos,
            'rankingServicos' => $rankingServicos,
            'faturamento6' => $financeiro->faturamentoMensal(6),
            'alertas' => $this->alertas($clientes),
        ]);
    }

    private function alertas(ClienteService $clientes): array
    {
        $semDesfecho = Agendamento::with(['cliente', 'profissional', 'servico'])
            ->whereIn('status', [StatusAgendamento::Pendente, StatusAgendamento::Confirmado])
            ->where('fim', '<', now())
            ->where('inicio', '>=', today()->subDays(7))
            ->orderBy('inicio')->get();

        return [
            'pendentes' => Agendamento::where('status', StatusAgendamento::Pendente)->count(),
            'sem_desfecho' => $semDesfecho,
            'lista_espera_vaga' => ListaEspera::with(['cliente', 'servico'])->where('status', 'aguardando')->whereNotNull('vaga_liberada_em')
                ->whereDate('data_desejada', '>=', today())->orderBy('data_desejada')->get(),
            'nome_divergente' => Agendamento::with('cliente')->where('nome_divergente', true)
                ->whereNotIn('status', [StatusAgendamento::Cancelado->value, StatusAgendamento::Reagendado->value])->latest('id')->limit(10)->get(),
            'duplicadas' => $clientes->possiveisDuplicadas(20)->count(),
            'bloqueadas_faltas' => Cliente::where('bloqueada_online', true)->where('motivo_bloqueio', 'like', 'Bloqueio automático%')->orderByDesc('updated_at')->limit(10)->get(),
            'reagendados_profissional' => Agendamento::with(['cliente', 'profissional', 'reagendadoPara'])
                ->where('status', StatusAgendamento::Reagendado)->where('reagendado_por', 'profissional')
                ->where('updated_at', '>=', now()->subDays(7))->latest('updated_at')->limit(10)->get(),
            'sem_whatsapp' => Agendamento::with(['cliente', 'servico', 'profissional'])
                ->where('origem', OrigemAgendamento::Site)->where('status', StatusAgendamento::Pendente)
                ->whereNull('whatsapp_enviado_em')->where('inicio', '>=', now())->orderBy('inicio')->limit(10)->get(),
        ];
    }
}
