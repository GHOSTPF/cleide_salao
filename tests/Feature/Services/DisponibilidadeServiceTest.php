<?php

namespace Tests\Feature\Services;

use App\Enums\StatusAgendamento;
use App\Models\BloqueioAgenda;
use App\Services\DisponibilidadeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaCenario;
use Tests\TestCase;

class DisponibilidadeServiceTest extends TestCase
{
    use CriaCenario, RefreshDatabase;

    private DisponibilidadeService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->congelarAgora('2026-11-16 08:00:00'); // segunda
        $this->configurar(['intervalo_slots_minutos' => 30, 'antecedencia_minima_horas' => 2, 'dias_maximos_agendamento' => 60]);
        $this->svc = app(DisponibilidadeService::class);
    }

    private function horas(array $slots): array
    {
        return array_column($slots, 'hora');
    }

    public function test_respeita_expediente_e_intervalo_de_almoco(): void
    {
        $servico = $this->criarServico(['duracao_minutos' => 60]);
        $this->criarProfissional([$servico]);

        $horas = $this->horas($this->svc->slots($servico, null, '2026-11-17'));

        $this->assertSame('09:00', $horas[0]);
        $this->assertSame('17:00', end($horas)); // 17:00 + 60 = 18:00 cabe
        $this->assertNotContains('17:30', $horas);
        $this->assertContains('11:00', $horas);
        $this->assertNotContains('11:30', $horas); // invadiria o almoço
        $this->assertNotContains('12:00', $horas);
        $this->assertNotContains('12:30', $horas);
        $this->assertContains('13:00', $horas);
    }

    public function test_dia_sem_expediente_nao_tem_horarios(): void
    {
        $servico = $this->criarServico();
        $this->criarProfissional([$servico]);

        $this->assertSame([], $this->svc->slots($servico, null, '2026-11-22')); // domingo
    }

    public function test_servico_longo_no_fim_do_dia(): void
    {
        $servico = $this->criarServico(['duracao_minutos' => 240]);
        $this->criarProfissional([$servico]);

        $horas = $this->horas($this->svc->slots($servico, null, '2026-11-17'));

        // Manhã: 4h não cabem antes do almoço (09–12). Tarde: 13:00–17:00 e 13:30–17:30 e 14:00–18:00
        $this->assertSame(['13:00', '13:30', '14:00'], $horas);
    }

    public function test_bloqueio_da_profissional_e_do_salao(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);

        BloqueioAgenda::create(['profissional_id' => $prof->id, 'inicio' => '2026-11-17 09:00', 'fim' => '2026-11-17 10:30', 'motivo' => 'Médico']);
        $horas = $this->horas($this->svc->slots($servico, $prof->id, '2026-11-17'));
        $this->assertNotContains('09:00', $horas);
        $this->assertNotContains('10:00', $horas); // 10:00–11:00 colide com bloqueio até 10:30
        $this->assertContains('10:30', $horas);

        // Feriado: salão inteiro fechado
        BloqueioAgenda::create(['profissional_id' => null, 'inicio' => '2026-11-18 00:00', 'fim' => '2026-11-19 00:00', 'motivo' => 'Feriado']);
        $this->assertSame([], $this->svc->slots($servico, null, '2026-11-18'));
    }

    public function test_conflito_com_agendamento_existente_considera_intervalo_apos(): void
    {
        $servico = $this->criarServico(['duracao_minutos' => 60, 'intervalo_apos_minutos' => 30]);
        $prof = $this->criarProfissional([$servico]);
        $cliente = $this->criarCliente();

        // Ocupa 09:00–10:00 + 30min de preparo => bloqueado até 10:30
        $this->criarAgendamento($cliente, $prof, $servico, '2026-11-17 09:00');

        $horas = $this->horas($this->svc->slots($servico, $prof->id, '2026-11-17'));
        $this->assertNotContains('09:00', $horas);
        $this->assertNotContains('09:30', $horas);
        $this->assertNotContains('10:00', $horas);
        $this->assertContains('10:30', $horas);
        // 08:00? não existe. 13:00 em diante livre
        $this->assertContains('13:00', $horas);
    }

    public function test_agendamento_cancelado_libera_horario(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $ag = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00');

        $this->assertNotContains('09:00', $this->horas($this->svc->slots($servico, $prof->id, '2026-11-17')));

        $ag->update(['status' => StatusAgendamento::Cancelado]);
        $this->assertContains('09:00', $this->horas($this->svc->slots($servico, $prof->id, '2026-11-17')));
    }

    public function test_antecedencia_minima_e_janela_maxima_no_site(): void
    {
        $servico = $this->criarServico();
        $this->criarProfissional([$servico]);
        $this->congelarAgora('2026-11-17 09:10:00');

        $horas = $this->horas($this->svc->slots($servico, null, '2026-11-17'));
        $this->assertNotContains('11:00', $horas); // < 11:10
        $this->assertSame('13:00', $horas[0]); // 11:30 invade o almoço

        $this->assertSame([], $this->svc->slots($servico, null, '2026-11-16')); // passado
        $this->assertSame([], $this->svc->slots($servico, null, now()->addDays(61)->toDateString()));

        // No painel não há antecedência mínima
        $painel = $this->horas($this->svc->slots($servico, null, '2026-11-17', DisponibilidadeService::MODO_PAINEL));
        $this->assertContains('09:30', $painel);
    }

    public function test_sem_preferencia_une_profissionais_e_pivot_sobrescreve_duracao(): void
    {
        $servico = $this->criarServico(['duracao_minutos' => 60]);
        $a = $this->criarProfissional([$servico]);
        $b = $this->criarProfissional([$servico]);
        $this->criarAgendamento($this->criarCliente(), $a, $servico, '2026-11-17 09:00');

        $slots = collect($this->svc->slots($servico, null, '2026-11-17'))->keyBy('hora');
        $this->assertSame([$b->id], $slots['09:00']['profissionais']);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $slots['10:00']['profissionais']);

        // B faz o serviço em 3h (sobrescrita no pivot): último horário à tarde passa a ser 15:00
        $b->servicos()->updateExistingPivot($servico->id, ['duracao_minutos' => 180]);
        $horasB = $this->horas($this->svc->slots($servico->fresh(), $b->id, '2026-11-17'));
        $this->assertSame('15:00', end($horasB));
    }

    public function test_verificar_horario_aponta_motivo(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $inicio = now()->setDate(2026, 11, 17)->setTime(20, 0);

        $this->assertSame('Horário fora do expediente da profissional.', $this->svc->verificarHorario($servico, $prof, $inicio, DisponibilidadeService::MODO_PAINEL));
        // Encaixe (admin) permite fora do expediente
        $this->assertNull($this->svc->verificarHorario($servico, $prof, $inicio, DisponibilidadeService::MODO_PAINEL, null, true));
    }
}
