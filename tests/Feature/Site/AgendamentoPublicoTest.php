<?php

namespace Tests\Feature\Site;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ListaEspera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaCenario;
use Tests\TestCase;

class AgendamentoPublicoTest extends TestCase
{
    use CriaCenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->congelarAgora('2026-11-16 08:00:00');
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'nome' => 'Maria Souza',
            'telefone' => '(83) 99999-1234',
            'data' => '2026-11-17',
            'hora' => '09:00',
            'consentimento' => '1',
        ];
    }

    public function test_home_carrega_com_servicos_e_formulario(): void
    {
        $servico = $this->criarServico(['nome' => 'Terapia dos Fios']);
        $this->criarProfissional([$servico]);

        $this->get('/')->assertOk()->assertSee('Terapia dos Fios')->assertSee('Reserve sua data')->assertSee('Sem preferência');
    }

    public function test_endpoint_de_horarios_retorna_json(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);

        $this->getJson('/horarios-disponiveis?servico_id=' . $servico->id . '&data=2026-11-17')
            ->assertOk()
            ->assertJsonFragment(['hora' => '09:00', 'profissionais' => [$prof->id]]);

        $this->getJson('/horarios-disponiveis?data=2026-11-17')->assertStatus(422);
    }

    public function test_agendar_grava_e_redireciona_para_confirmacao_com_link_whatsapp(): void
    {
        $servico = $this->criarServico(['nome' => 'Corte e Escova']);
        $prof = $this->criarProfissional([$servico]);

        $resposta = $this->post('/agendar', $this->dados(['servico_id' => $servico->id, 'profissional_id' => $prof->id]));

        $ag = Agendamento::firstOrFail();
        $resposta->assertRedirect(route('agendamento.confirmado', $ag->token_publico));
        $this->assertSame(StatusAgendamento::Pendente, $ag->status);
        $this->assertSame('5583999991234', $ag->cliente->telefone);

        $this->followRedirects($resposta)
            ->assertOk()
            ->assertSee($ag->codigo)
            ->assertSee('Recebemos seu pedido!')
            ->assertSee('https://api.whatsapp.com/send?phone=5583986669294&amp;text=', false)
            ->assertSee('Adicionar ao Google Agenda');

        // Registro do clique no WhatsApp (sendBeacon)
        $this->post(route('agendamento.whatsapp', $ag->token_publico))->assertNoContent();
        $this->assertNotNull($ag->fresh()->whatsapp_enviado_em);
    }

    public function test_cliente_recorrente_nao_tem_dados_expostos(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $this->criarCliente(['nome' => 'Maria Souza Cadastro', 'telefone' => '5583999991234', 'email' => 'segredo@ex.com']);

        $resposta = $this->followingRedirects()->post('/agendar', $this->dados(['servico_id' => $servico->id, 'profissional_id' => $prof->id]));

        $resposta->assertSee('Que bom te ver de novo!')->assertDontSee('segredo@ex.com')->assertDontSee('Maria Souza Cadastro');
        $this->assertSame(1, Cliente::count());
    }

    public function test_validacoes_honeypot_e_lgpd(): void
    {
        $servico = $this->criarServico();
        $this->criarProfissional([$servico]);

        $this->post('/agendar', $this->dados(['servico_id' => $servico->id, 'consentimento' => null]))->assertSessionHasErrors('consentimento');
        $this->post('/agendar', $this->dados(['servico_id' => $servico->id, 'website' => 'http://spam']))->assertSessionHasErrors('website');
        $this->post('/agendar', $this->dados(['servico_id' => $servico->id, 'telefone' => '123']))->assertSessionHasErrors('telefone');
        $this->post('/agendar', $this->dados(['servico_id' => $servico->id, 'data' => '2026-11-10']))->assertSessionHasErrors('data');

        $this->assertSame(0, Agendamento::count());
    }

    public function test_conflito_volta_com_mensagem_amigavel(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00');

        $this->post('/agendar', $this->dados(['servico_id' => $servico->id, 'profissional_id' => $prof->id]))
            ->assertSessionHasErrors(['agendamento' => 'Esse horário acabou de ser reservado. Escolha outro.']);
    }

    public function test_rate_limit_do_formulario(): void
    {
        $servico = $this->criarServico();
        $this->criarProfissional([$servico]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/agendar', $this->dados(['servico_id' => $servico->id, 'consentimento' => null]));
        }
        $this->post('/agendar', $this->dados(['servico_id' => $servico->id]))->assertStatus(429);
    }

    public function test_lista_de_espera_pelo_site(): void
    {
        $servico = $this->criarServico();
        $this->criarProfissional([$servico]);

        $this->post('/lista-espera', $this->dados(['servico_id' => $servico->id, 'periodo_preferido' => 'manha']))
            ->assertSessionHas('lista_espera_ok');

        $entrada = ListaEspera::firstOrFail();
        $this->assertSame('manha', $entrada->periodo_preferido);
        $this->assertSame('aguardando', $entrada->status);
    }

    public function test_ics_e_token_invalido(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $ag = $this->criarAgendamento($this->criarCliente(), $prof, $servico, '2026-11-17 09:00');

        $this->get(route('agendamento.ics', $ag->token_publico))->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->get('/agendamento/confirmado/' . str_repeat('x', 40))->assertNotFound();
    }
}
