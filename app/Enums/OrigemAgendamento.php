<?php

namespace App\Enums;

enum OrigemAgendamento: string
{
    case Site = 'site';
    case Painel = 'painel';
    case Whatsapp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'Site',
            self::Painel => 'Painel',
            self::Whatsapp => 'WhatsApp',
        };
    }
}
