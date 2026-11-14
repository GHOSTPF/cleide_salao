<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erro de regra de negócio com mensagem amigável para exibir ao usuário.
 */
class AgendamentoException extends RuntimeException
{
    public const CONFLITO = 'Esse horário acabou de ser reservado. Escolha outro.';

    public function __construct(string $message, public readonly bool $sugerirWhatsapp = false)
    {
        parent::__construct($message);
    }

    public static function conflito(): self
    {
        return new self(self::CONFLITO);
    }
}
