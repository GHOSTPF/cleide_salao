<?php

namespace Tests\Feature\Painel;

use App\Enums\PapelUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaCenario;
use Tests\TestCase;

class PermissoesTest extends TestCase
{
    use CriaCenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->congelarAgora('2026-11-16 08:00:00');
    }

    public function test_registro_publico_nao_existe(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'x@x.com'])->assertStatus(404);
    }

    public function test_login_redireciona_conforme_o_papel(): void
    {
        $admin = User::factory()->create(['papel' => PapelUsuario::Admin, 'password' => 'segredo123']);
        $prof = $this->criarProfissional([], [], comUsuario: true);
        $prof->user->update(['password' => 'segredo123']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'segredo123'])->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');
        $this->post('/login', ['email' => $prof->user->email, 'password' => 'segredo123'])->assertRedirect(route('profissional.agenda'));
    }

    public function test_usuario_inativo_nao_faz_login_e_e_deslogado(): void
    {
        $user = User::factory()->create(['papel' => PapelUsuario::Admin, 'password' => 'segredo123', 'ativo' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'segredo123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $ativo = $this->criarAdmin();
        $this->actingAs($ativo)->get(route('admin.dashboard'))->assertOk();
        $ativo->update(['ativo' => false]);
        $this->actingAs($ativo->fresh())->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_areas_por_papel(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $prof = $this->criarProfissional([], [], comUsuario: true);
        $this->actingAs($prof->user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($prof->user)->get(route('admin.financeiro.index'))->assertForbidden();
        $this->actingAs($prof->user)->get(route('profissional.agenda'))->assertOk();

        $this->actingAs($this->criarAdmin())->get(route('profissional.agenda'))->assertForbidden();
    }

    public function test_profissional_so_acessa_os_proprios_agendamentos_e_clientes(): void
    {
        $servico = $this->criarServico();
        $eu = $this->criarProfissional([$servico], [], comUsuario: true);
        $outra = $this->criarProfissional([$servico]);
        $minhaCliente = $this->criarCliente();
        $clienteDaOutra = $this->criarCliente();
        $meu = $this->criarAgendamento($minhaCliente, $eu, $servico, '2026-11-17 09:00');
        $dela = $this->criarAgendamento($clienteDaOutra, $outra, $servico, '2026-11-17 09:00');

        $this->actingAs($eu->user);
        $this->get(route('profissional.agendamentos.show', $meu))->assertOk();
        $this->get(route('profissional.agendamentos.show', $dela))->assertForbidden();
        $this->post(route('profissional.agendamentos.confirmar', $dela))->assertForbidden();
        $this->get(route('profissional.clientes.show', $minhaCliente))->assertOk()->assertDontSee('Total gasto');
        $this->get(route('profissional.clientes.show', $clienteDaOutra))->assertForbidden();
        $this->get(route('profissional.clientes.index'))->assertSee($minhaCliente->nome)->assertDontSee($clienteDaOutra->nome);
    }

    public function test_reagendamento_e_cancelamento_pela_profissional_dependem_de_configuracao(): void
    {
        $servico = $this->criarServico();
        $eu = $this->criarProfissional([$servico], [], comUsuario: true);
        $ag = $this->criarAgendamento($this->criarCliente(), $eu, $servico, '2026-11-18 09:00');
        $this->actingAs($eu->user);

        $this->configurar(['profissional_pode_reagendar' => false, 'profissional_pode_cancelar' => false]);
        $this->get(route('profissional.agendamentos.reagendar', $ag))->assertForbidden();
        $this->post(route('profissional.agendamentos.cancelar', $ag), ['cancelado_por' => 'cliente'])->assertForbidden();

        $this->configurar(['profissional_pode_reagendar' => true]);
        $this->get(route('profissional.agendamentos.reagendar', $ag))->assertOk();

        $this->post(route('profissional.agendamentos.reagendar', $ag), [
            'data' => '2026-11-18', 'hora' => '14:00', 'pedido_por' => 'cliente', 'motivo' => 'imprevisto',
        ])->assertRedirect()->assertSessionHas('link_whatsapp');
        $this->assertSame('reagendado', $ag->fresh()->status->value);
    }

    public function test_bloqueios_da_profissional_dependem_de_configuracao(): void
    {
        $eu = $this->criarProfissional([], [], comUsuario: true);
        $this->actingAs($eu->user);

        $this->configurar(['profissional_gerencia_bloqueios' => false]);
        $this->get(route('profissional.bloqueios.index'))->assertForbidden();

        $this->configurar(['profissional_gerencia_bloqueios' => true]);
        $this->post(route('profissional.bloqueios.store'), ['data_inicio' => '2026-11-20', 'data_fim' => '2026-11-20', 'motivo' => 'Folga'])->assertSessionHas('sucesso');
        $this->assertSame(1, $eu->bloqueios()->count());
    }
}
