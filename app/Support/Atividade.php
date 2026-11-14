<?php

namespace App\Support;

use App\Models\LogAtividade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Contexto para o log de atividades: permite que um Service rotule as alterações
 * feitas dentro de um bloco com uma ação semântica ("confirmou", "reagendou"...).
 */
class Atividade
{
    private static array $pilha = [];

    public static function acao(string $acao, callable $callback): mixed
    {
        self::$pilha[] = $acao;
        try {
            return $callback();
        } finally {
            array_pop(self::$pilha);
        }
    }

    public static function acaoAtual(): ?string
    {
        return self::$pilha ? end(self::$pilha) : null;
    }

    public static function registrar(string $acao, ?Model $entidade = null, ?array $antes = null, ?array $depois = null, ?string $tipo = null): void
    {
        LogAtividade::create([
            'user_id' => Auth::id(),
            'acao' => $acao,
            'entidade_tipo' => $tipo ?? ($entidade ? class_basename($entidade) : 'Sistema'),
            'entidade_id' => $entidade?->getKey(),
            'dados_antes' => $antes,
            'dados_depois' => $depois,
            'ip' => app()->runningInConsole() ? null : Request::ip(),
        ]);
    }
}
