<?php

namespace Tests\Feature\Services;

use App\Enums\StatusAgendamento;
use App\Exceptions\AgendamentoException;
use App\Services\ReagendamentoService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaCenario;
use Tests\TestCase;

class ReagendamentoServiceTest extends TestCase
{
    use CriaCenario, RefreshDatabase;

    private ReagendamentoService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->congelarAgora('2026-11-16 08:00:00');
        $this->configurar([
            'profissional_pode_reagendar' => true,
            'antecedencia_cancelamento_horas' => 12,
            'limite_reagendamentos_por_agendamento' => 2,
        ]);
        $this->svc = app(ReagendamentoService::class);
    }

    private function em(string $quando): Carbon
    {
        return Carbon::parse($quando, config('app.timezone'));
    }

    public function test_cria_novo_ligado_ao_antigo_e_libera_o_horario(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $cliente = $this->criarCliente();
        $admin = $this->criarAdmin();
        $antigo = $this->criarAgendamento($cliente, $prof, $servico, '2026-11-17 09:00');

        // Desloca só 30 min: o novo sobrepõe o antigo, o que só funciona porque o antigo sai da constraint
        $novo = $this->svc->reagendar($antigo, $this->em('2026-11-17 09:30'), null, $admin, 'cliente', 'imprevisto');

        $antigo->refresh();
        $this->assertSame(StatusAgendamento::Reagendado, $antigo->status);
        $this->assertSame($novo->id, $antigo->reagendado_para_id);
        $this->assertSame($antigo->id, $novo->reagendado_de_id);
        $this->assertSame($antigo->codigo . '-R1', $novo->codigo);
        $this->assertNotSame($antigo->token_publico, $novo->token_publico);
        $this->assertSame(StatusAgendamento::Confirmado, $novo->status); // mantém confirmado
        $this->assertSame('cliente', $antigo->reagendado_por);
        $this->assertSame(1, $cliente->fresh()->total_reagendamentos);
        $this->assertStringContainsString('motivo: imprevisto', $antigo->historico()->latest('id')->first()->observacao);

        // Segundo reagendamento continua a cadeia
        $novo2 = $this->svc->reagendar($novo, $this->em('2026-11-18 14:00'), null, $admin, 'salao');
        $this->assertSame($antigo->codigo . '-R2', $novo2->codigo);
        $this->assertCount(3, $this->svc->cadeia($novo));
        $this->assertSame([$antigo->id, $novo->id, $novo2->id], $this->svc->cadeia($novo2)->pluck('id')->all());
    }

    public function test_pendente_continua_pendente(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $antigo = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00', StatusAgendamento::Pendente);

        $novo = $this->svc->reagendar($antigo, $this->em('2026-11-17 15:00'), null, $this->criarAdmin(), 'salao');
        $this->assertSame(StatusAgendamento::Pendente, $novo->status);
    }

    public function test_novo_horario_ocupado_e_recusado_e_nada_muda(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $antigo = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00');
        $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 15:00');

        try {
            $this->svc->reagendar($antigo, $this->em('2026-11-17 15:00'), null, $this->criarAdmin(), 'salao');
            $this->fail('Deveria recusar horário ocupado');
        } catch (AgendamentoException) {
        }

        $this->assertSame(StatusAgendamento::Confirmado, $antigo->fresh()->status);
    }

    public function test_profissional_respeita_prazo_e_limite_admin_ignora(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico], [], comUsuario: true);
        $user = $prof->user;
        $admin = $this->criarAdmin();

        // Prazo: faltam 3h (< 12h)
        $proximo = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-16 11:00');
        $this->assertNotNull($this->svc->impedimento($proximo, $user));
        $this->assertNull($this->svc->impedimento($proximo, $admin));

        // Limite: 2 reagendamentos
        $ag = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-18 09:00');
        $r1 = $this->svc->reagendar($ag, $this->em('2026-11-18 10:00'), null, $user, 'profissional');
        $r2 = $this->svc->reagendar($r1, $this->em('2026-11-18 11:00'), null, $user, 'profissional');
        $this->assertStringContainsString('Limite', $this->svc->impedimento($r2, $user));

        $r3 = $this->svc->reagendar($r2, $this->em('2026-11-18 14:00'), null, $admin, 'salao');
        $this->assertStringEndsWith('-R3', $r3->codigo);

        // Configuração desligada
        $this->configurar(['profissional_pode_reagendar' => false]);
        $this->assertStringContainsString('desativado', $this->svc->impedimento($r3, $user));
    }

    public function test_profissional_nao_move_para_outra_agenda(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico], [], comUsuario: true);
        $outra = $this->criarProfissional([$servico]);
        $ag = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-18 09:00');

        $this->expectException(AgendamentoException::class);
        $this->svc->reagendar($ag, $this->em('2026-11-18 10:00'), $outra, $prof->user, 'profissional');
    }
}
