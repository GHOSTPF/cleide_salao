<?php

namespace App\Console\Commands;

use App\Services\AgendamentoService;
use Illuminate\Console\Command;

class ExpirarPendentes extends Command
{
    protected $signature = 'agendamentos:expirar-pendentes';

    protected $description = 'Cancela (cancelado_por = sistema) agendamentos pendentes não confirmados dentro de expirar_pendente_horas (0 = nunca).';

    public function handle(AgendamentoService $svc): int
    {
        $total = $svc->expirarPendentes();
        $this->info($total . ' agendamento(s) pendente(s) expirado(s).');

        return self::SUCCESS;
    }
}
