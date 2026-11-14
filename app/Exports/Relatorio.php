<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Relatório tabular simples: as mesmas linhas alimentam o Excel e o PDF.
 */
class Relatorio implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        public readonly string $titulo,
        public readonly array $colunas,
        public readonly array $linhas,
    ) {}

    public function array(): array
    {
        return $this->linhas;
    }

    public function headings(): array
    {
        return $this->colunas;
    }

    public function title(): string
    {
        return mb_substr($this->titulo, 0, 31);
    }
}
