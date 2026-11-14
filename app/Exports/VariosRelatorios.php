<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class VariosRelatorios implements WithMultipleSheets
{
    /** @param  Relatorio[]  $relatorios */
    public function __construct(public readonly array $relatorios) {}

    public function sheets(): array
    {
        return $this->relatorios;
    }
}
