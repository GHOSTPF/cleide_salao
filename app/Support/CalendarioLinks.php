<?php

namespace App\Support;

use App\Models\Agendamento;

class CalendarioLinks
{
    public static function titulo(Agendamento $ag): string
    {
        return $ag->servico->nome . ' - ' . Setting::get('nome_salao');
    }

    public static function descricao(Agendamento $ag): string
    {
        return 'Código: ' . $ag->codigo . "\n" . 'Profissional: ' . $ag->profissional->nome_exibicao . "\n" . 'WhatsApp do salão: +' . WhatsAppLink::numeroSalao();
    }

    public static function google(Agendamento $ag): string
    {
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action' => 'TEMPLATE',
            'text' => self::titulo($ag),
            'dates' => $fmt($ag->inicio) . '/' . $fmt($ag->fim),
            'details' => self::descricao($ag),
            'location' => (string) Setting::get('endereco'),
            'ctz' => config('app.timezone'),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function ics(Agendamento $ag): string
    {
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $esc = fn (string $s) => str_replace(["\\", ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $s);

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Cleide Luna Studio//Agendamentos//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $ag->token_publico . '@cleideluna',
            'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $fmt($ag->inicio),
            'DTEND:' . $fmt($ag->fim),
            'SUMMARY:' . $esc(self::titulo($ag)),
            'DESCRIPTION:' . $esc(self::descricao($ag)),
            'LOCATION:' . $esc((string) Setting::get('endereco')),
            'END:VEVENT',
            'END:VCALENDAR',
        ]) . "\r\n";
    }
}
