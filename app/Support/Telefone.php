<?php

namespace App\Support;

class Telefone
{
    /**
     * Normaliza para só dígitos com DDI 55.
     * "(83) 99999-9999", "83999999999", "+55 83 99999-9999" => "5583999999999"
     */
    public static function normalizar(?string $telefone): string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefone);
        $digitos = ltrim($digitos, '0'); // remove prefixo de operadora/zero à esquerda

        if (strlen($digitos) >= 12 && str_starts_with($digitos, '55')) {
            return $digitos;
        }

        if (in_array(strlen($digitos), [10, 11], true)) {
            return '55' . $digitos;
        }

        return $digitos;
    }

    /** Válido = 55 + DDD (2) + 8 ou 9 dígitos. */
    public static function valido(?string $telefone): bool
    {
        return (bool) preg_match('/^55[1-9]{2}9?\d{8}$/', self::normalizar($telefone));
    }

    /** "5583999999999" => "(83) 99999-9999" */
    public static function formatar(?string $telefone): string
    {
        $d = self::normalizar($telefone);
        if (str_starts_with($d, '55') && strlen($d) >= 12) {
            $d = substr($d, 2);
        }

        if (strlen($d) === 11) {
            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
        }
        if (strlen($d) === 10) {
            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
        }

        return (string) $telefone;
    }
}
