<?php

namespace Tests\Feature\Services;

use App\Enums\StatusAgendamento;
use App\Exceptions\AgendamentoException;
use App\Models\Agendamento;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\CriaCenario;
use Tests\TestCase;

/**
 * Prova que a exclusion constraint impede agendamento duplo mesmo quando duas requisições
 * passam pela validação da aplicação ao mesmo tempo. Usa dados commitados (sem transação de teste)
 * e uma segunda conexão real ao PostgreSQL.
 */
class ConcorrenciaTest extends TestCase
{
    use CriaCenario, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->congelarAgora('2026-11-16 08:00:00');
        config(['database.connections.pgsql_concorrente' => config('database.connections.pgsql')]);
    }

    protected function tearDown(): void
    {
        DB::purge('pgsql_concorrente');

        // Os dados desta classe são commitados: limpa para não vazar para os testes com RefreshDatabase
        $tabelas = collect(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename <> 'migrations'"))
            ->pluck('tablename')->map(fn ($t) => '"' . $t . '"')->implode(', ');
        DB::statement('TRUNCATE ' . $tabelas . ' RESTART IDENTITY CASCADE');

        parent::tearDown();
    }

    private function linha(int $clienteId, int $profId, int $servicoId, string $inicio, string $fim): array
    {
        return [
            'codigo' => 'CL-' . strtoupper(Str::random(5)),
            'token_publico' => Str::random(40),
            'cliente_id' => $clienteId,
            'profissional_id' => $profId,
            'servico_id' => $servicoId,
            'inicio' => $inicio,
            'fim' => $fim,
            'fim_bloqueio' => $fim,
            'status' => 'pendente',
            'origem' => 'site',
            'preco_previsto' => 100,
        ];
    }

    public function test_duas_conexoes_simultaneas_nao_reservam_o_mesmo_horario(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $c1 = $this->criarCliente();
        $c2 = $this->criarCliente();

        $a = DB::connection('pgsql');
        $b = DB::connection('pgsql_concorrente');

        // Conexão A reserva 09:00–10:00, ainda sem commit
        $a->beginTransaction();
        $a->table('agendamentos')->insert($this->linha($c1->id, $prof->id, $servico->id, '2026-11-17 09:00-03', '2026-11-17 10:00-03'));

        // Conexão B tenta 09:30–10:30 ao mesmo tempo: fica bloqueada esperando A (lock_timeout para não travar o teste)
        $b->statement("SET lock_timeout = '1500ms'");
        try {
            $b->table('agendamentos')->insert($this->linha($c2->id, $prof->id, $servico->id, '2026-11-17 09:30-03', '2026-11-17 10:30-03'));
            $this->fail('A segunda conexão não deveria conseguir inserir enquanto a primeira segura o horário');
        } catch (QueryException $e) {
            $this->assertSame('55P03', $e->errorInfo[0]); // lock_not_available: aguardando a reserva concorrente
        }

        $a->commit();

        // Após o commit de A, B recebe violação da exclusion constraint
        try {
            $b->table('agendamentos')->insert($this->linha($c2->id, $prof->id, $servico->id, '2026-11-17 09:30-03', '2026-11-17 10:30-03'));
            $this->fail('Deveria violar agendamentos_sem_conflito');
        } catch (QueryException $e) {
            $this->assertSame('23P01', $e->errorInfo[0]);
        }

        $this->assertSame(1, Agendamento::count());
    }

    public function test_duas_requisicoes_que_passaram_pela_validacao_resultam_em_um_unico_agendamento(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);

        // Simula as duas requisições já validadas: ambas chegam ao INSERT
        $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00', StatusAgendamento::Pendente);

        try {
            $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00', StatusAgendamento::Pendente);
            $this->fail('Deveria recusar');
        } catch (AgendamentoException $e) {
            $this->assertSame('Esse horário acabou de ser reservado. Escolha outro.', $e->getMessage());
        }

        $this->assertSame(1, Agendamento::count());
    }

    public function test_cancelados_e_reagendados_nao_bloqueiam_e_outra_profissional_pode(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $outra = $this->criarProfissional([$servico]);

        $ag = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00');
        $this->criarAgendamento($this->criarCliente(), $outra, $servico, '2026-11-17 09:00'); // outra profissional: ok

        $ag->update(['status' => StatusAgendamento::Cancelado]);
        $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00'); // horário liberado

        $this->assertSame(3, Agendamento::count());
    }
}
