<?php

namespace App\Console\Commands;

use App\Services\ClienteService;
use Illuminate\Console\Command;

class RecalcularClientes extends Command
{
    protected $signature = 'clientes:recalcular';

    protected $description = 'Recalcula as colunas desnormalizadas das clientes (visitas, gasto, faltas...) a partir de agendamentos + pagamentos.';

    public function handle(ClienteService $svc): int
    {
        $total = $svc->recalcularTodas();
        $this->info($total . ' cliente(s) recalculada(s).');

        return self::SUCCESS;
    }
}
