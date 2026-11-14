<?php

namespace App\Enums;

enum CanceladoPor: string
{
    case Cliente = 'cliente';
    case Salao = 'salao';
    case Sistema = 'sistema';

    public function label(): string
    {
        return match ($this) {
            self::Cliente => 'Cliente pediu',
            self::Salao => 'Salão',
            self::Sistema => 'Sistema (expiração)',
        };
    }
}
