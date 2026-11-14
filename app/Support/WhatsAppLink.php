<?php

namespace App\Support;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ListaEspera;
use Illuminate\Support\Carbon;

/**
 * Monta mensagens e links https://wa.me/{numero}?text={mensagem}.
 * Nenhuma integração de API: o link só abre o WhatsApp com a mensagem pronta.
 */
class WhatsAppLink
{
    private const DIAS = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];

    public static function link(string $numero, string $mensagem = ''): string
    {
        $numero = Telefone::normalizar($numero);

        if ($mensagem === '') {
            return 'https://wa.me/' . $numero;
        }

        // Com texto, vai direto ao api.whatsapp.com: o redirecionamento do wa.me
        // troca emojis por "�" no WhatsApp Web/Desktop.
        return 'https://api.whatsapp.com/send?phone=' . $numero . '&text=' . rawurlencode($mensagem);
    }

    public static function numeroSalao(): string
    {
        return Telefone::normalizar((string) Setting::get('whatsapp_salao'));
    }

    public static function dataExtenso(Carbon $data): string
    {
        return $data->format('d/m/Y') . ' (' . self::DIAS[$data->dayOfWeek] . ')';
    }

    /** Mensagem que a cliente envia ao salão logo após agendar pelo site. */
    public static function mensagemParaSalao(Agendamento $ag): string
    {
        $linhas = [
            'Olá, ' . Setting::get('nome_salao') . '! 🌸',
            'Tudo bem? Acabei de fazer meu agendamento pelo site e gostaria de confirmar com vocês 💕',
            '',
            '✨ *Meu agendamento* ✨',
            '',
            '🔖 *Código:* ' . $ag->codigo,
            '👤 *Nome:* ' . ($ag->nome_informado ?: $ag->cliente->nome),
            '📱 *WhatsApp:* ' . Telefone::formatar($ag->cliente->telefone),
            '💇 *Serviço:* ' . $ag->servico->nome,
            '💁 *Profissional:* ' . $ag->profissional->nome_exibicao,
            '📅 *Data:* ' . self::dataExtenso($ag->inicio),
            '⏰ *Horário:* ' . $ag->inicio->format('H:i'),
        ];
        if (filled($ag->observacoes_cliente)) {
            $linhas[] = '📝 *Observações:* ' . $ag->observacoes_cliente;
        }
        $linhas[] = '';
        $linhas[] = 'Fico no aguardo da confirmação. Desde já, agradeço o carinho! 🤍';

        return implode("\n", $linhas);
    }

    public static function paraSalao(Agendamento $ag): string
    {
        return self::link(self::numeroSalao(), self::mensagemParaSalao($ag));
    }

    /** Link genérico para o salão (ex.: cliente bloqueada para agendamento online). */
    public static function falarComSalao(string $mensagem = 'Olá! Gostaria de agendar um horário.'): string
    {
        return self::link(self::numeroSalao(), $mensagem);
    }

    public static function paraCliente(Cliente $cliente, string $mensagem = ''): string
    {
        return self::link($cliente->telefone, $mensagem);
    }

    public static function confirmarComCliente(Agendamento $ag): string
    {
        $msg = 'Olá, ' . $ag->cliente->primeiro_nome . '! Aqui é do ' . Setting::get('nome_salao') . ' 💛' . "\n"
            . 'Recebemos seu agendamento ' . $ag->codigo . ':' . "\n"
            . '💇 ' . $ag->servico->nome . ' com ' . $ag->profissional->nome_exibicao . "\n"
            . '📅 ' . self::dataExtenso($ag->inicio) . ' às ' . $ag->inicio->format('H:i') . "\n"
            . 'Podemos confirmar?';

        return self::paraCliente($ag->cliente, $msg);
    }

    public static function avisoReagendamento(Agendamento $novo): string
    {
        $msg = 'Olá, ' . $novo->cliente->primeiro_nome . '! Aqui é do ' . Setting::get('nome_salao') . ' 💛' . "\n"
            . 'Seu horário foi remarcado:' . "\n"
            . '💇 ' . $novo->servico->nome . ' com ' . $novo->profissional->nome_exibicao . "\n"
            . '📅 ' . self::dataExtenso($novo->inicio) . ' às ' . $novo->inicio->format('H:i') . "\n"
            . '📌 Código: ' . $novo->codigo;

        return self::paraCliente($novo->cliente, $msg);
    }

    public static function retorno(Cliente $cliente, string $servico): string
    {
        $msg = 'Oi, ' . $cliente->primeiro_nome . '! Tudo bem? Aqui é do ' . Setting::get('nome_salao') . ' 💛' . "\n"
            . 'Já está na hora de renovar seu ' . $servico . '. Vamos agendar? É só escolher o melhor dia pra você ✨';

        return self::paraCliente($cliente, $msg);
    }

    public static function aniversario(Cliente $cliente): string
    {
        $msg = 'Feliz aniversário, ' . $cliente->primeiro_nome . '! 🎉💛' . "\n"
            . 'Todo o time do ' . Setting::get('nome_salao') . ' deseja um ano lindo, cheio de brilho. Conte com a gente para realçar a sua beleza!';

        return self::paraCliente($cliente, $msg);
    }

    public static function reativacao(Cliente $cliente): string
    {
        $msg = 'Oi, ' . $cliente->primeiro_nome . '! Que saudade 💛 Aqui é do ' . Setting::get('nome_salao') . '.' . "\n"
            . 'Faz um tempinho que você não vem nos visitar. Que tal agendar um momento só seu?';

        return self::paraCliente($cliente, $msg);
    }

    public static function listaEspera(ListaEspera $entrada): string
    {
        $msg = 'Oi, ' . $entrada->cliente->primeiro_nome . '! Aqui é do ' . Setting::get('nome_salao') . ' 💛' . "\n"
            . 'Você está na nossa lista de espera para ' . $entrada->servico->nome . ' no dia ' . self::dataExtenso($entrada->data_desejada) . '.' . "\n"
            . 'Abriu um horário! Quer que a gente reserve pra você?';

        return self::paraCliente($entrada->cliente, $msg);
    }
}
