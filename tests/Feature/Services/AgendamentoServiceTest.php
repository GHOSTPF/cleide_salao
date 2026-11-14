<?php

namespace Tests\Feature\Services;

use App\Enums\CanceladoPor;
use App\Enums\StatusAgendamento;
use App\Exceptions\AgendamentoException;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Comissao;
use App\Models\ListaEspera;
use App\Models\LogAtividade;
use App\Models\Pagamento;
use App\Services\AgendamentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaCenario;
use Tests\TestCase;

class AgendamentoServiceTest extends TestCase
{
    use CriaCenario, RefreshDatabase;

    private AgendamentoService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->congelarAgora('2026-11-16 08:00:00');
        $this->configurar(['limite_agendamentos_pendentes_por_cliente' => 2, 'faltas_para_bloqueio' => 3]);
        $this->svc = app(AgendamentoService::class);
    }

    private function dadosSite(array $extra = []): array
    {
        return $extra + [
            'nome' => 'Maria Souza',
            'telefone' => '(83) 99999-1234',
            'data' => '2026-11-17',
            'hora' => '09:00',
            'observacoes' => 'quero clarear 2 tons',
            'consentimento' => true,
        ];
    }

    public function test_cria_pelo_site_como_pendente_com_codigo_token_e_historico(): void
    {
        $servico = $this->criarServico(['duracao_minutos' => 90, 'intervalo_apos_minutos' => 15, 'preco' => 150]);
        $prof = $this->criarProfissional([$servico]);

        $r = $this->svc->criarPeloSite($this->dadosSite(['servico_id' => $servico->id, 'profissional_id' => $prof->id]));
        $ag = $r['agendamento'];

        $this->assertFalse($r['cliente_recorrente']);
        $this->assertSame(StatusAgendamento::Pendente, $ag->status);
        $this->assertMatchesRegularExpression('/^CL-[A-Z0-9]{5}$/', $ag->codigo);
        $this->assertSame(40, strlen($ag->token_publico));
        $this->assertSame('2026-11-17 10:30', $ag->fim->format('Y-m-d H:i'));
        $this->assertSame('2026-11-17 10:45', $ag->fim_bloqueio->format('Y-m-d H:i'));
        $this->assertEquals(150, (float) $ag->preco_previsto);
        $this->assertSame(1, $ag->historico()->count());
        $this->assertNotNull($ag->cliente->consentimento_lgpd_em);
        $this->assertTrue(LogAtividade::where('entidade_tipo', 'Agendamento')->where('entidade_id', $ag->id)->exists());
    }

    public function test_cliente_recorrente_com_nome_diferente_fica_marcado(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $this->criarCliente(['nome' => 'Maria Souza', 'telefone' => '5583999991234']);

        $r = $this->svc->criarPeloSite($this->dadosSite(['nome' => 'Maria', 'servico_id' => $servico->id, 'profissional_id' => $prof->id]));

        $this->assertTrue($r['cliente_recorrente']);
        $this->assertTrue($r['agendamento']->nome_divergente);
        $this->assertSame('Maria', $r['agendamento']->nome_informado);
        $this->assertSame('Maria Souza', $r['agendamento']->cliente->nome);
        $this->assertSame(1, Cliente::count());
    }

    public function test_sem_preferencia_escolhe_profissional_com_menos_agendamentos_no_dia(): void
    {
        $servico = $this->criarServico();
        $a = $this->criarProfissional([$servico], ['ordem' => 1]);
        $b = $this->criarProfissional([$servico], ['ordem' => 2]);
        $this->criarAgendamento($this->criarCliente(), $a, $servico, '2026-11-17 14:00');

        $r = $this->svc->criarPeloSite($this->dadosSite(['servico_id' => $servico->id, 'profissional_id' => null]));

        $this->assertSame($b->id, $r['agendamento']->profissional_id);
    }

    public function test_bloqueios_de_negocio_no_site(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $outra = $this->criarProfissional([$servico]);

        // Cliente bloqueada para agendamento online
        $this->criarCliente(['telefone' => '5583988887777', 'bloqueada_online' => true]);
        try {
            $this->svc->criarPeloSite($this->dadosSite(['telefone' => '83988887777', 'servico_id' => $servico->id, 'profissional_id' => $prof->id]));
            $this->fail('Deveria bloquear');
        } catch (AgendamentoException $e) {
            $this->assertSame('Para agendar, fale com a gente pelo WhatsApp.', $e->getMessage());
            $this->assertTrue($e->sugerirWhatsapp);
        }

        // Mesmo horário com outra profissional
        $this->svc->criarPeloSite($this->dadosSite(['servico_id' => $servico->id, 'profissional_id' => $prof->id]));
        try {
            $this->svc->criarPeloSite($this->dadosSite(['servico_id' => $servico->id, 'profissional_id' => $outra->id]));
            $this->fail('Deveria bloquear choque de horário da cliente');
        } catch (AgendamentoException $e) {
            $this->assertSame('Você já tem um agendamento nesse horário.', $e->getMessage());
        }

        // Limite de pendentes (2)
        $this->svc->criarPeloSite($this->dadosSite(['hora' => '14:00', 'servico_id' => $servico->id, 'profissional_id' => $prof->id]));
        $this->expectException(AgendamentoException::class);
        $this->svc->criarPeloSite($this->dadosSite(['hora' => '16:00', 'servico_id' => $servico->id, 'profissional_id' => $prof->id]));
    }

    public function test_horario_ocupado_retorna_mensagem_amigavel(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00');

        $this->expectExceptionMessage(AgendamentoException::CONFLITO);
        $this->svc->criarPeloSite($this->dadosSite(['servico_id' => $servico->id, 'profissional_id' => $prof->id]));
    }

    public function test_confirmar_e_cancelar(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $cliente = $this->criarCliente();
        $admin = $this->criarAdmin();
        $ag = $this->criarAgendamento($cliente, $prof, $servico, '2026-11-17 09:00', StatusAgendamento::Pendente);

        $ag = $this->svc->confirmar($ag, $admin);
        $this->assertSame(StatusAgendamento::Confirmado, $ag->status);
        $this->assertNotNull($ag->confirmado_em);

        // Entrada na lista de espera para o mesmo dia (sem vaga até o cancelamento)
        $espera = ListaEspera::create(['cliente_id' => $this->criarCliente()->id, 'servico_id' => $servico->id, 'data_desejada' => '2026-11-17', 'periodo_preferido' => 'manha']);

        $ag = $this->svc->cancelar($ag, $admin, CanceladoPor::Cliente, 'imprevisto');
        $this->assertSame(StatusAgendamento::Cancelado, $ag->status);
        $this->assertSame(CanceladoPor::Cliente, $ag->cancelado_por);
        $this->assertSame(1, $cliente->fresh()->total_cancelamentos);
        $this->assertNotNull($espera->fresh()->vaga_liberada_em);
        $this->assertSame(2, $ag->historico()->count()); // confirmar + cancelar (o cenário insere sem histórico)

        $this->expectException(AgendamentoException::class);
        $this->svc->confirmar($ag, $admin); // cancelado não volta
    }

    public function test_falta_so_apos_inicio_incrementa_e_bloqueia_ao_atingir_limite(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $cliente = $this->criarCliente();
        $admin = $this->criarAdmin();

        $futuro = $this->criarAgendamento($cliente, $prof, $servico, '2026-11-20 09:00');
        try {
            $this->svc->marcarFalta($futuro, $admin);
            $this->fail('Falta antes do início não é permitida');
        } catch (AgendamentoException) {
        }

        foreach (['2026-11-10 09:00', '2026-11-11 09:00', '2026-11-12 09:00'] as $quando) {
            $this->svc->marcarFalta($this->criarAgendamento($cliente, $prof, $servico, $quando), $admin);
        }

        $cliente->refresh();
        $this->assertSame(3, $cliente->total_faltas);
        $this->assertTrue($cliente->bloqueada_online);

        // Desfazer falta (admin)
        $ultimo = Agendamento::where('status', StatusAgendamento::NaoCompareceu)->latest('id')->first();
        $this->svc->desfazerFalta($ultimo, $admin);
        $this->assertSame(StatusAgendamento::Confirmado, $ultimo->fresh()->status);
        $this->assertSame(2, $cliente->fresh()->total_faltas);
    }

    public function test_pendente_nao_pode_ir_para_falta(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $ag = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-10 09:00', StatusAgendamento::Pendente);

        $this->expectException(AgendamentoException::class);
        $this->svc->marcarFalta($ag, $this->criarAdmin());
    }

    public function test_concluir_com_pagamento_dividido_gera_comissao_snapshot_e_atualiza_cliente(): void
    {
        $servico = $this->criarServico(['preco' => 200]);
        $prof = $this->criarProfissional([$servico], ['percentual_comissao' => 40]);
        $cliente = $this->criarCliente();
        $admin = $this->criarAdmin();
        $ag = $this->criarAgendamento($cliente, $prof, $servico, '2026-11-14 09:00');

        $ag = $this->svc->concluir($ag, $admin, '180,00', [
            ['forma' => 'pix', 'valor' => '100'],
            ['forma' => 'credito', 'valor' => '80,00'],
        ], 'Descoloração 30vol + matizador 9.1');

        $this->assertSame(StatusAgendamento::Concluido, $ag->status);
        $this->assertEquals(180, (float) $ag->valor_cobrado);
        $this->assertEquals(20, (float) $ag->desconto);
        $this->assertSame(2, Pagamento::where('agendamento_id', $ag->id)->count());
        $this->assertEquals(72, (float) $ag->comissao->valor);

        // Mudar o percentual depois não altera a comissão (snapshot)
        $prof->update(['percentual_comissao' => 10]);
        $this->assertEquals(40, (float) Comissao::first()->percentual);

        $cliente->refresh();
        $this->assertSame(1, $cliente->total_visitas);
        $this->assertEquals(180, (float) $cliente->total_gasto);
        $this->assertSame('2026-11-14', $cliente->primeira_visita_em->toDateString());
        $this->assertSame('2026-11-14', $cliente->ultima_visita_em->toDateString());
        $this->assertSame(1, $cliente->anotacoes()->count());
    }

    public function test_concluir_exige_soma_correta_e_horario_iniciado(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $admin = $this->criarAdmin();
        $passado = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-14 09:00');
        $futuro = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-20 09:00');

        try {
            $this->svc->concluir($passado, $admin, 100, [['forma' => 'pix', 'valor' => 90]]);
            $this->fail('Soma divergente');
        } catch (AgendamentoException $e) {
            $this->assertStringContainsString('não bate', $e->getMessage());
        }

        $this->expectExceptionMessage('após o horário de início');
        $this->svc->concluir($futuro, $admin, 100, [['forma' => 'pix', 'valor' => 100]]);
    }

    public function test_estorno_reverte_pagamento_comissao_e_totais(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $cliente = $this->criarCliente();
        $admin = $this->criarAdmin();
        $ag = $this->criarAgendamento($cliente, $prof, $servico, '2026-11-14 09:00');
        $this->svc->concluir($ag, $admin, 100, [['forma' => 'pix', 'valor' => 100]]);

        $ag = $this->svc->estornar($ag, $admin, 'valor lançado errado');

        $this->assertSame(StatusAgendamento::Confirmado, $ag->status);
        $this->assertNull($ag->valor_cobrado);
        $this->assertSame(0, Pagamento::count());
        $this->assertSame(0, Comissao::count());
        $cliente->refresh();
        $this->assertSame(0, $cliente->total_visitas);
        $this->assertEquals(0, (float) $cliente->total_gasto);
        $this->assertTrue(LogAtividade::where('acao', 'estornou')->exists());
    }

    public function test_expirar_pendentes_cancela_pelo_sistema(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $ag = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-20 09:00', StatusAgendamento::Pendente);
        $ag->forceFill(['created_at' => now()->subHours(30)])->saveQuietly();

        $this->assertSame(0, $this->svc->expirarPendentes()); // padrão 0 = nunca

        $this->configurar(['expirar_pendente_horas' => 24]);
        $this->assertSame(1, $this->svc->expirarPendentes());
        $this->assertSame(CanceladoPor::Sistema, $ag->fresh()->cancelado_por);
    }
}
