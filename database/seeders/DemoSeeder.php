<?php

namespace Database\Seeders;

use App\Enums\CanceladoPor;
use App\Enums\OrigemAgendamento;
use App\Enums\PapelUsuario;
use App\Enums\StatusAgendamento;
use App\Exceptions\AgendamentoException;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Despesa;
use App\Models\ListaEspera;
use App\Models\Profissional;
use App\Models\User;
use App\Services\AgendamentoService;
use App\Services\ClienteService;
use App\Services\DisponibilidadeService;
use App\Services\ReagendamentoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Massa de dados de exemplo — somente APP_ENV=local.
 */
class DemoSeeder extends Seeder
{
    public function run(AgendamentoService $agSvc, DisponibilidadeService $disp, ReagendamentoService $reag, ClienteService $cliSvc): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::where('papel', PapelUsuario::Admin)->firstOrFail();
        auth()->setUser($admin);

        $clientes = Cliente::factory()->count(30)->create();
        $profissionais = Profissional::with('servicos')->where('ativo', true)->get();
        $formas = ['pix', 'pix', 'pix', 'dinheiro', 'debito', 'credito'];

        $criados = 0;
        $tentativas = 0;
        while ($criados < 120 && $tentativas < 1500) {
            $tentativas++;
            $prof = $profissionais->random();
            $servico = $prof->servicos->random();
            $dia = today()->addDays(random_int(-75, 20));
            if ($dia->isSunday()) {
                continue;
            }
            $inicio = $dia->copy()->setTime(random_int(9, 16), [0, 30][random_int(0, 1)]);
            $info = $disp->infoServico($servico, $prof);
            if (! $disp->dentroDoExpediente($prof->loadMissing('horarios'), $inicio, $inicio->copy()->addMinutes($info['duracao']))) {
                continue;
            }

            $cliente = $clientes->random();
            $passado = $inicio->lt(now());
            try {
                $ag = $agSvc->inserir([
                    'cliente_id' => $cliente->id,
                    'profissional_id' => $prof->id,
                    'servico_id' => $servico->id,
                    'nome_informado' => $cliente->nome,
                    'status' => $passado || random_int(0, 2) ? StatusAgendamento::Confirmado : StatusAgendamento::Pendente,
                    'confirmado_em' => now(),
                    'origem' => collect(OrigemAgendamento::cases())->random(),
                    'whatsapp_enviado_em' => random_int(0, 3) ? now() : null,
                    'observacoes_cliente' => random_int(0, 4) ? null : 'Quero algo natural',
                ], $servico, $prof, $inicio, $info);
            } catch (AgendamentoException) {
                continue; // horário já ocupado, tenta outro
            }
            $agSvc->historico($ag, null, $ag->status, 'Criado pelo seeder de demonstração', $admin);
            $criados++;

            if (! $passado) {
                continue;
            }

            $sorteio = random_int(1, 100);
            if ($sorteio <= 78) {
                $valor = (float) $ag->preco_previsto;
                $pagamentos = random_int(0, 5)
                    ? [['forma' => $formas[array_rand($formas)], 'valor' => $valor]]
                    : [['forma' => 'pix', 'valor' => round($valor / 2, 2)], ['forma' => 'credito', 'valor' => $valor - round($valor / 2, 2)]];
                $agSvc->concluir($ag, $admin, $valor, $pagamentos, random_int(0, 3) ? null : 'Fórmula: 7.1 + 20vol, pausa 35min');
                // Datas reais do atendimento (o service usa "agora")
                DB::table('pagamentos')->where('agendamento_id', $ag->id)->update(['pago_em' => $ag->fim]);
                DB::table('agendamentos')->where('id', $ag->id)->update(['concluido_em' => $ag->fim]);
            } elseif ($sorteio <= 88) {
                $agSvc->cancelar($ag, $admin, CanceladoPor::Cliente, 'Imprevisto');
            } else {
                $agSvc->marcarFalta($ag, $admin);
            }
        }

        // Alguns reagendamentos futuros
        Agendamento::where('status', StatusAgendamento::Confirmado)->where('inicio', '>', now()->addDay())->inRandomOrder()->limit(4)->get()
            ->each(function (Agendamento $ag) use ($reag, $disp, $admin) {
                $slots = $disp->slots($ag->servico, $ag->profissional_id, $ag->inicio->copy()->addDays(2), DisponibilidadeService::MODO_PAINEL);
                if ($slots) {
                    $hora = $slots[array_rand($slots)]['hora'];
                    try {
                        $reag->reagendar($ag, Carbon::parse($ag->inicio->copy()->addDays(2)->toDateString() . ' ' . $hora), null, $admin, 'cliente', 'Imprevisto no trabalho');
                    } catch (AgendamentoException) {
                    }
                }
            });

        // Comissões antigas já pagas
        DB::table('comissoes')->join('agendamentos', 'agendamentos.id', '=', 'comissoes.agendamento_id')
            ->where('agendamentos.inicio', '<', now()->startOfMonth())
            ->update(['status' => 'paga', 'pago_em' => now()->startOfMonth()]);

        // Despesas dos últimos meses
        foreach (range(0, 2) as $m) {
            $mes = now()->startOfMonth()->subMonths($m);
            Despesa::create(['descricao' => 'Aluguel do salão', 'categoria' => 'aluguel', 'valor' => 1800, 'data' => $mes->copy()->day(5), 'pago' => true]);
            Despesa::create(['descricao' => 'Energia', 'categoria' => 'energia', 'valor' => random_int(280, 420), 'data' => $mes->copy()->day(10), 'pago' => $m > 0]);
            Despesa::create(['descricao' => 'Produtos de coloração', 'categoria' => 'produtos', 'valor' => random_int(600, 1200), 'data' => $mes->copy()->day(15), 'pago' => true]);
            Despesa::create(['descricao' => 'Internet', 'categoria' => 'internet', 'valor' => 120, 'data' => $mes->copy()->day(20), 'pago' => $m > 0]);
        }

        // Lista de espera e uma duplicada de exemplo
        $servicos = $profissionais->flatMap->servicos->unique('id');
        foreach ($clientes->random(3) as $c) {
            ListaEspera::create([
                'cliente_id' => $c->id, 'servico_id' => $servicos->random()->id,
                'data_desejada' => today()->addDays(random_int(1, 7)), 'periodo_preferido' => collect(['manha', 'tarde', 'qualquer'])->random(),
            ]);
        }
        $base = $clientes->first();
        Cliente::factory()->create(['nome' => $base->nome . ' ' . 'Silva', 'origem' => 'whatsapp']);

        $cliSvc->recalcularTodas();
        auth()->logout();
    }
}
