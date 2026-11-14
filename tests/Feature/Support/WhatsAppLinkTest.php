<?php

namespace Tests\Feature\Support;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Support\Setting;
use App\Support\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WhatsAppLinkTest extends TestCase
{
    use RefreshDatabase;

    private function agendamentoEmMemoria(?string $obs = 'quero clarear 2 tons'): Agendamento
    {
        $ag = new Agendamento([
            'codigo' => 'CL-8F3K2',
            'nome_informado' => 'Maria Souza',
            'inicio' => Carbon::parse('2026-11-20 09:00', config('app.timezone')),
            'observacoes_cliente' => $obs,
        ]);
        $ag->setRelation('cliente', new Cliente(['nome' => 'Maria Souza', 'telefone' => '5583999999999']));
        $ag->setRelation('servico', new Servico(['nome' => 'Mechas Personalizadas']));
        $ag->setRelation('profissional', new Profissional(['nome' => 'Ana Paula', 'apelido' => 'Ana']));

        return $ag;
    }

    public function test_mensagem_para_o_salao_no_formato_combinado(): void
    {
        $esperado = implode("\n", [
            'Olá, Cleide Luna Studio! ✨',
            'Acabei de agendar pelo site:',
            '📌 Código: CL-8F3K2',
            '👤 Nome: Maria Souza',
            '📱 WhatsApp: (83) 99999-9999',
            '💇 Serviço: Mechas Personalizadas',
            '💁 Profissional: Ana',
            '📅 Data: 20/11/2026 (sexta)',
            '🕘 Horário: 09:00',
            '📝 Obs.: quero clarear 2 tons',
        ]);

        $this->assertSame($esperado, WhatsAppLink::mensagemParaSalao($this->agendamentoEmMemoria()));
        $this->assertStringNotContainsString('Obs.', WhatsAppLink::mensagemParaSalao($this->agendamentoEmMemoria(null)));
    }

    public function test_link_usa_numero_configurado_e_rawurlencode(): void
    {
        $link = WhatsAppLink::paraSalao($this->agendamentoEmMemoria());

        $this->assertStringStartsWith('https://wa.me/5583986669294?text=', $link);
        $texto = substr($link, strlen('https://wa.me/5583986669294?text='));
        $this->assertStringContainsString('%0A', $texto); // quebra de linha
        $this->assertStringContainsString('%20', $texto); // espaço como %20 (não "+")
        $this->assertStringNotContainsString('+', $texto);
        $this->assertSame(WhatsAppLink::mensagemParaSalao($this->agendamentoEmMemoria()), rawurldecode($texto));

        Setting::set('whatsapp_salao', '+55 (83) 91234-5678');
        $this->assertStringStartsWith('https://wa.me/5583912345678?text=', WhatsAppLink::paraSalao($this->agendamentoEmMemoria()));
    }

    public function test_link_sem_mensagem(): void
    {
        $this->assertSame('https://wa.me/5583999999999', WhatsAppLink::link('(83) 99999-9999'));
    }
}
