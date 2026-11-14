<?php

namespace App\Enums;

enum OrigemCliente: string
{
    case Site = 'site';
    case Balcao = 'balcao';
    case Whatsapp = 'whatsapp';
    case Indicacao = 'indicacao';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'Site',
            self::Balcao => 'Balcão',
            self::Whatsapp => 'WhatsApp',
            self::Indicacao => 'Indicação',
        };
    }
}
