<?php

namespace App\Models\Concerns;

use App\Support\Atividade;

/**
 * Registra em `log_atividades` quem criou/alterou/excluiu o registro e o que mudou.
 * A ação pode ser rotulada pelo Service via Atividade::acao('confirmou', fn () => ...).
 */
trait RegistraAtividade
{
    protected static array $camposIgnoradosNoLog = ['updated_at', 'created_at', 'password', 'remember_token', 'periodo'];

    public static function bootRegistraAtividade(): void
    {
        static::created(function ($model) {
            Atividade::registrar(Atividade::acaoAtual() ?? 'criou', $model, null, $model->dadosParaLog($model->getAttributes()));
        });

        static::updated(function ($model) {
            $alterados = $model->dadosParaLog($model->getChanges());
            if (! $alterados) {
                return;
            }
            $antes = array_intersect_key($model->dadosParaLog($model->getOriginal()), $alterados);
            Atividade::registrar(Atividade::acaoAtual() ?? 'alterou', $model, $antes, $alterados);
        });

        static::deleted(function ($model) {
            Atividade::registrar(Atividade::acaoAtual() ?? 'excluiu', $model, $model->dadosParaLog($model->getAttributes()), null);
        });
    }

    protected function dadosParaLog(array $dados): array
    {
        $dados = array_diff_key($dados, array_flip(static::$camposIgnoradosNoLog));

        return array_map(function ($v) {
            if ($v instanceof \BackedEnum) {
                return $v->value;
            }
            if ($v instanceof \DateTimeInterface) {
                return $v->format('Y-m-d H:i:s');
            }

            return $v;
        }, $dados);
    }
}
