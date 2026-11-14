<?php

namespace App\Support;

class Dinheiro
{
    /** 1234.5 => "R$ 1.234,50" */
    public static function formatar(float|int|string|null $valor): string
    {
        return 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }

    /** "1.234,50" | "1234.50" | "1234,5" => 1234.50 */
    public static function paraDecimal(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_int($valor) || is_float($valor)) {
            return round((float) $valor, 2);
        }

        $v = preg_replace('/[^\d,.\-]/', '', (string) $valor);
        if (str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }

        return is_numeric($v) ? round((float) $v, 2) : null;
    }
}
