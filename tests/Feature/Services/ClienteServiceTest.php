<?php

namespace Tests\Feature\Services;

use App\Enums\FormaPagamento;
use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\AtendimentoAnotacao;
use App\Models\Cliente;
use App\Services\AgendamentoService;
use App\Services\ClienteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CriaCenario;
use Tests\TestCase;

class ClienteServiceTest extends TestCase
{
    use CriaCenario, RefreshDatabase;

    private ClienteService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->congelarAgora();
        $this->svc = app(ClienteService::class);
    }

    public function test_mesmo_telefone_em_formatos_diferentes_e_a_mesma_cliente(): void
    {
        $a = $this->svc->upsertPorTelefone(['nome' => 'Maria Souza', 'telefone' => '(83) 99999-1111'], consentimento: true);
        $b = $this->svc->upsertPorTelefone(['nome' => 'Maria Souza', 'telefone' => '+55 83 99999-1111']);
        $c = $this->svc->upsertPorTelefone(['nome' => 'Maria Souza', 'telefone' => '83999991111']);

        $this->assertTrue($a['nova']);
        $this->assertFalse($b['nova']);
        $this->assertSame($a['cliente']->id, $b['cliente']->id);
        $this->assertSame($a['cliente']->id, $c['cliente']->id);
        $this->assertSame(1, Cliente::count());
        $this->assertSame('5583999991111', $a['cliente']->telefone);
        $this->assertNotNull($a['cliente']->consentimento_lgpd_em);
    }

    public function test_nome_nunca_e_sobrescrito_e_divergencia_e_marcada(): void
    {
        $this->svc->upsertPorTelefone(['nome' => 'Maria Souza', 'telefone' => '83999991111']);
        $r = $this->svc->upsertPorTelefone(['nome' => 'Maria', 'telefone' => '83999991111']);

        $this->assertSame('Maria Souza', $r['cliente']->fresh()->nome);
        $this->assertTrue($r['nome_divergente']);

        // Mesma grafia com acento/caixa/espaços diferentes não é divergência
        $r2 = $this->svc->upsertPorTelefone(['nome' => '  maria   SOUZA ', 'telefone' => '83999991111']);
        $this->assertFalse($r2['nome_divergente']);
    }

    public function test_email_e_nascimento_so_preenchem_se_vazios(): void
    {
        $this->svc->upsertPorTelefone(['nome' => 'Ana', 'telefone' => '83999992222']);
        $this->svc->upsertPorTelefone(['nome' => 'Ana', 'telefone' => '83999992222', 'email' => 'ana@ex.com', 'data_nascimento' => '1990-05-10']);
        $this->svc->upsertPorTelefone(['nome' => 'Ana', 'telefone' => '83999992222', 'email' => 'outro@ex.com', 'data_nascimento' => '2000-01-01']);

        $cliente = Cliente::first();
        $this->assertSame('ana@ex.com', $cliente->email);
        $this->assertSame('1990-05-10', $cliente->data_nascimento->toDateString());
    }

    public function test_mesclar_move_historico_e_recalcula_totais(): void
    {
        $servico = $this->criarServico(['preco' => 100]);
        $prof = $this->criarProfissional([$servico]);
        $admin = $this->criarAdmin();
        $principal = $this->criarCliente(['nome' => 'Joana Lima', 'email' => null]); // sem e-mail: herda o da secundária
        $secundaria = $this->criarCliente(['nome' => 'Joana Lima Santos', 'email' => 'joana@ex.com']);

        $agSvc = app(AgendamentoService::class);
        $ag1 = $this->criarAgendamento($principal, $prof, $servico, '2026-11-10 09:00');
        $ag2 = $this->criarAgendamento($secundaria, $prof, $servico, '2026-11-11 09:00');
        $agSvc->concluir($ag1, $admin, 100, [['forma' => 'pix', 'valor' => 100]]);
        $agSvc->concluir($ag2, $admin, 150, [['forma' => 'dinheiro', 'valor' => 150]], 'Coloração 7.1');
        $falta = $this->criarAgendamento($secundaria, $prof, $servico, '2026-11-12 09:00');
        $agSvc->marcarFalta($falta, $admin);

        $resultado = $this->svc->mesclar($principal, $secundaria->fresh());

        $this->assertSame(3, Agendamento::where('cliente_id', $principal->id)->count());
        $this->assertSame(1, AtendimentoAnotacao::where('cliente_id', $principal->id)->count());
        $this->assertSame(2, $resultado->total_visitas);
        $this->assertEquals(250, (float) $resultado->total_gasto);
        $this->assertSame(1, $resultado->total_faltas);
        $this->assertSame('2026-11-11', $resultado->ultima_visita_em->toDateString());
        $this->assertSame('2026-11-10', $resultado->primeira_visita_em->toDateString());
        $this->assertSame('joana@ex.com', $resultado->email);

        $sec = Cliente::withTrashed()->find($secundaria->id);
        $this->assertTrue($sec->trashed());
        $this->assertSame($principal->id, $sec->mesclada_em_id);

        // Agendar com o telefone antigo cai na cliente principal
        $r = $this->svc->upsertPorTelefone(['nome' => 'Joana', 'telefone' => $secundaria->telefone]);
        $this->assertSame($principal->id, $r['cliente']->id);
    }

    public function test_recalcular_reconstroi_a_partir_das_tabelas_fonte(): void
    {
        $servico = $this->criarServico();
        $prof = $this->criarProfissional([$servico]);
        $cliente = $this->criarCliente();
        $ag = $this->criarAgendamento($cliente, $prof, $servico, '2026-11-10 09:00');
        app(AgendamentoService::class)->concluir($ag, $this->criarAdmin(), 80, [['forma' => FormaPagamento::Credito->value, 'valor' => 80]]);

        // Bagunça os contadores e recalcula
        $cliente->forceFill(['total_visitas' => 99, 'total_gasto' => 0, 'total_faltas' => 7])->save();
        $this->assertSame(1, $this->svc->recalcularTodas() >= 1 ? 1 : 0);

        $cliente->refresh();
        $this->assertSame(1, $cliente->total_visitas);
        $this->assertEquals(80, (float) $cliente->total_gasto);
        $this->assertSame(0, $cliente->total_faltas);
    }

    public function test_possiveis_duplicadas_e_ignorar(): void
    {
        $a = $this->criarCliente(['nome' => 'Fernanda Albuquerque']);
        $b = $this->criarCliente(['nome' => 'Fernanda Albuquerqe']);
        $this->criarCliente(['nome' => 'Patrícia Gomes']);

        $pares = $this->svc->possiveisDuplicadas();
        $this->assertCount(1, $pares);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], [$pares[0]->a->id, $pares[0]->b->id]);

        $this->svc->ignorarDuplicidade($b, $a);
        $this->assertCount(0, $this->svc->possiveisDuplicadas());
    }

    public function test_status_ativos_da_constraint(): void
    {
        $this->assertSame(['pendente', 'confirmado', 'concluido'], StatusAgendamento::ativos());
    }
}
