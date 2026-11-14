<?php

namespace Tests\Feature\Painel;

use App\Enums\StatusAgendamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaCenario;
use Tests\TestCase;

class PainelProfissionalTest extends TestCase
{
    use CriaCenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::preventLazyLoading();
        $this->congelarAgora('2026-11-16 10:00:00');
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);
        parent::tearDown();
    }

    public function test_telas_e_conclusao_do_proprio_atendimento(): void
    {
        $servico = $this->criarServico(['preco' => 120]);
        $eu = $this->criarProfissional([$servico], ['percentual_comissao' => 50], comUsuario: true);
        $cliente = $this->criarCliente();
        $hoje = $this->criarAgendamento($cliente, $eu, $servico, '2026-11-16 09:00');
        $this->criarAgendamento($cliente, $eu, $servico, '2026-11-18 09:00', StatusAgendamento::Pendente);
        $this->configurar(['profissional_gerencia_bloqueios' => true]);

        $this->actingAs($eu->user);
        foreach ([
            route('profissional.agenda'),
            route('profissional.agenda', ['aba' => 'proximos']),
            route('profissional.agenda', ['aba' => 'historico']),
            route('profissional.agendamentos.show', $hoje),
            route('profissional.clientes.index'),
            route('profissional.clientes.show', $cliente),
            route('profissional.resumo'),
            route('profissional.bloqueios.index'),
            route('perfil.edit'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->post(route('profissional.agendamentos.concluir', $hoje), [
            'valor_cobrado' => '120',
            'pagamentos' => [['forma' => 'credito', 'valor' => '120']],
            'anotacao' => 'Descoloração 30vol',
        ])->assertSessionHas('sucesso');

        $this->assertSame(StatusAgendamento::Concluido, $hoje->fresh()->status);
        $this->get(route('profissional.resumo'))->assertSee('R$ 60,00');

        // Profissional não estorna
        $this->post(route('admin.agendamentos.estornar', $hoje))->assertForbidden();
    }
}
