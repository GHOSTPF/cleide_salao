<?php

namespace App\Enums;

enum FormaPagamento: string
{
    case Pix = 'pix';
    case Dinheiro = 'dinheiro';
    case Debito = 'debito';
    case Credito = 'credito';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Dinheiro => 'Dinheiro',
            self::Debito => 'Cartão de débito',
            self::Credito => 'Cartão de crédito',
        };
    }
}
