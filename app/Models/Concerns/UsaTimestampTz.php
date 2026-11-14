<?php

namespace App\Models\Concerns;

/**
 * Grava datas com offset explícito em colunas timestamptz (não depende do fuso da sessão).
 */
trait UsaTimestampTz
{
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:sP';
    }
}
