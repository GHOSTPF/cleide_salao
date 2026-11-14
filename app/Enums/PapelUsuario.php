<?php

namespace App\Enums;

enum PapelUsuario: string
{
    case Admin = 'admin';
    case Profissional = 'profissional';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administradora',
            self::Profissional => 'Profissional',
        };
    }
}
