<?php

namespace App\Console\Commands;

use App\Services\ListaEsperaService;
use Illuminate\Console\Command;

class ExpirarListaEspera extends Command
{
    protected $signature = 'lista-espera:expirar';

    protected $description = 'Expira entradas da lista de espera cuja data desejada já passou.';

    public function handle(ListaEsperaService $svc): int
    {
        $total = $svc->expirar();
        $this->info($total . ' entrada(s) expirada(s).');

        return self::SUCCESS;
    }
}
