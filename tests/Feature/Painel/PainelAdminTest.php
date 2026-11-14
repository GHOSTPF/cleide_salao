<?php

namespace Tests\Feature\Painel;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Despesa;
use App\Models\ListaEspera;
use App\Models\Pagamento;
use App\Services\AgendamentoService;
use App\Support\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaCenario;
use Tests\TestCase;

class PainelAdminTest extends TestCase
{
    use CriaCenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Model::preventLazyLoading(); // qualquer N+1 nas telas quebra o teste
        $this->congelarAgora('2026-11-16 10:00:00');
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);
        parent::tearDown();
    }

    private function cenario(): array
    {
        $admin = $this->criarAdmin();
        $servico = $this->criarServico(['nome' => 'Mechas', 'retorno_sugerido_dias' => 30]);
        $prof = $this->criarProfissional([$servico], ['nome' => 'Ana'], comUsuario: true);
        $cliente = $this->criarCliente(['nome' => 'Maria Souza', 'data_nascimento' => '1990-11-18']);
        $this->criarCliente(['nome' => 'Maria Souza Lima']);
        $svc = app(AgendamentoService::class);

        $passado = $this->criarAgendamento($cliente, $prof, $servico, '2026-10-01 09:00');
        $svc->concluir($passado, $admin, 100, [['forma' => 'pix', 'valor' => 100]], 'Fórmula 7.1');
        $hoje = $this->criarAgendamento($cliente, $prof, $servico, '2026-11-16 08:30', StatusAgendamento::Pendente); // já terminou (sem desfecho)
        $futuro = $this->criarAgendamento($cliente, $prof, $servico, '2026-11-18 14:00');
        $futuro->update(['nome_divergente' => true, 'nome_informado' => 'Mari']);
        ListaEspera::create(['cliente_id' => $cliente->id, 'servico_id' => $servico->id, 'data_desejada' => '2026-11-18', 'vaga_liberada_em' => now()]);
        Despesa::create(['descricao' => 'Aluguel', 'categoria' => 'aluguel', 'valor' => 1500, 'data' => '2026-11-05', 'pago' => true]);

        return compact('admin', 'servico', 'prof', 'cliente', 'passado', 'hoje', 'futuro');
    }

    public function test_todas_as_telas_do_admin_abrem(): void
    {
        $c = $this->cenario();
        $this->actingAs($c['admin']);

        $urls = [
            route('admin.dashboard'),
            route('admin.agenda'),
            route('admin.agenda', ['visao' => 'semana']),
            route('admin.agenda', ['profissional_id' => $c['prof']->id, 'data' => '2026-11-18']),
            route('admin.agendamentos.index'),
            route('admin.agendamentos.index', ['status' => 'concluido', 'q' => 'Maria', 'de' => '2026-01-01', 'ate' => '2026-12-31']),
            route('admin.pendentes'),
            route('admin.agendamentos.create'),
            route('admin.agendamentos.create', ['cliente_id' => $c['cliente']->id]),
            route('admin.agendamentos.create', ['lista_espera_id' => ListaEspera::first()->id]),
            route('admin.agendamentos.show', $c['passado']),
            route('admin.agendamentos.show', $c['futuro']),
            route('admin.agendamentos.reagendar', $c['futuro']),
            route('admin.clientes.index'),
            route('admin.clientes.index', ['filtro' => 'aniversariantes', 'ordem' => 'gasto']),
            route('admin.clientes.show', $c['cliente']),
            route('admin.clientes.edit', $c['cliente']),
            route('admin.clientes.duplicadas'),
            route('admin.relacionamento.retornos'),
            route('admin.relacionamento.aniversariantes'),
            route('admin.relacionamento.aniversariantes', ['periodo' => 'mes']),
            route('admin.relacionamento.inativas'),
            route('admin.relacionamento.lista-espera'),
            route('admin.servicos.index'),
            route('admin.servicos.create'),
            route('admin.servicos.edit', $c['servico']),
            route('admin.categorias.index'),
            route('admin.profissionais.index'),
            route('admin.profissionais.create'),
            route('admin.feriados.index'),
            route('admin.financeiro.index'),
            route('admin.financeiro.index', ['aba' => 'despesas']),
            route('admin.financeiro.comissoes'),
            route('admin.financeiro.despesas.create'),
            route('admin.site.configuracoes'),
            route('admin.site.galeria'),
            route('admin.site.depoimentos'),
            route('admin.atividades'),
            route('perfil.edit'),
        ];
        foreach (['dados', 'acesso', 'servicos', 'horarios', 'bloqueios'] as $aba) {
            $urls[] = route('admin.profissionais.edit', [$c['prof'], 'aba' => $aba]);
        }

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        $this->getJson(route('admin.horarios', ['servico_id' => $c['servico']->id, 'profissional_id' => $c['prof']->id, 'data' => '2026-11-17']))
            ->assertOk()->assertJsonFragment(['hora' => '09:00']);
        $this->getJson(route('admin.clientes.buscar', ['q' => 'Maria']))->assertOk()->assertJsonCount(2);
    }

    public function test_dashboard_mostra_alertas(): void
    {
        $c = $this->cenario();

        $this->actingAs($c['admin'])->get(route('admin.dashboard'))
            ->assertSee('aguardando confirmação')
            ->assertSee('Atendimentos sem desfecho')
            ->assertSee('Lista de espera: abriu vaga!')
            ->assertSee('Nome diferente do cadastro')
            ->assertSee('possível(is) cliente(s) duplicada(s)', false);
    }

    public function test_exportacoes_excel_e_pdf(): void
    {
        $c = $this->cenario();
        $this->actingAs($c['admin']);

        foreach (['admin.clientes.exportar', 'admin.agendamentos.exportar', 'admin.financeiro.exportar'] as $rota) {
            foreach (['xlsx', 'pdf'] as $formato) {
                $resposta = $this->get(route($rota, ['formato' => $formato]));
                $resposta->assertOk();
                $this->assertStringContainsString('.' . $formato, $resposta->headers->get('content-disposition'));
            }
        }
    }

    public function test_fluxo_novo_agendamento_concluir_dividido_e_estornar(): void
    {
        $c = $this->cenario();
        $this->actingAs($c['admin']);

        // Novo agendamento combinado no WhatsApp, cliente nova
        $this->post(route('admin.agendamentos.store'), [
            'nome' => 'Joana Prado', 'telefone' => '(83) 98888-1111', 'servico_id' => $c['servico']->id,
            'profissional_id' => $c['prof']->id, 'data' => '2026-11-17', 'hora' => '10:00', 'origem' => 'whatsapp',
        ])->assertRedirect();
        $novo = Agendamento::latest('id')->first();
        $this->assertSame('whatsapp', $novo->origem->value);
        $this->assertSame('whatsapp', Cliente::where('telefone', '5583988881111')->first()->origem->value);

        // Encaixe fora do expediente exige confirmação explícita
        $this->post(route('admin.agendamentos.store'), [
            'cliente_id' => $c['cliente']->id, 'servico_id' => $c['servico']->id, 'profissional_id' => $c['prof']->id,
            'data' => '2026-11-17', 'hora' => '19:00', 'origem' => 'painel', 'encaixe' => '1',
        ])->assertSessionHasErrors('confirmar_encaixe');
        $this->post(route('admin.agendamentos.store'), [
            'cliente_id' => $c['cliente']->id, 'servico_id' => $c['servico']->id, 'profissional_id' => $c['prof']->id,
            'data' => '2026-11-17', 'hora' => '19:00', 'origem' => 'painel', 'encaixe' => '1', 'confirmar_encaixe' => '1',
        ])->assertSessionHas('sucesso');

        // Concluir com pagamento dividido
        $this->post(route('admin.agendamentos.concluir', $c['hoje']), [
            'valor_cobrado' => '150,00',
            'pagamentos' => [['forma' => 'pix', 'valor' => '100'], ['forma' => 'dinheiro', 'valor' => '50,00']],
            'anotacao' => 'Matizador 9.1',
        ])->assertSessionHas('sucesso');
        $this->assertSame(2, Pagamento::where('agendamento_id', $c['hoje']->id)->count());

        $this->post(route('admin.agendamentos.estornar', $c['hoje']), ['motivo' => 'teste'])->assertSessionHas('sucesso');
        $this->assertSame(0, Pagamento::where('agendamento_id', $c['hoje']->id)->count());
    }

    public function test_configuracoes_alteram_whatsapp_do_salao(): void
    {
        $c = $this->cenario();
        $dados = array_merge(Setting::todas(), ['whatsapp_salao' => '(83) 91234-5678', 'instagram' => '@novo']);

        $this->actingAs($c['admin'])->put(route('admin.site.configuracoes.update'), $dados)->assertSessionHas('sucesso');

        $this->assertSame('5583912345678', Setting::get('whatsapp_salao'));
        $this->assertSame('novo', Setting::get('instagram'));
    }

    public function test_mesclar_pela_tela(): void
    {
        $c = $this->cenario();
        $outra = Cliente::where('nome', 'Maria Souza Lima')->first();

        $this->actingAs($c['admin'])->post(route('admin.clientes.mesclar'), ['principal_id' => $c['cliente']->id, 'secundaria_id' => $outra->id])
            ->assertRedirect(route('admin.clientes.show', $c['cliente']));
        $this->assertSoftDeleted($outra);
    }
}
