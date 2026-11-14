<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Rotinas internas (somente banco de dados — nenhuma mensagem é enviada)
|--------------------------------------------------------------------------
| Cron no servidor:  * * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
*/

Schedule::command('agendamentos:expirar-pendentes')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('lista-espera:expirar')->dailyAt('00:10');
Schedule::command('clientes:recalcular')->weeklyOn(1, '03:00'); // segunda 03:00 (também pode ser rodado manualmente)
