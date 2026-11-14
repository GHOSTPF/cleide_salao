<?php

use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site público (sem login)
|--------------------------------------------------------------------------
*/
Route::get('/', [Site\HomeController::class, 'index'])->name('home');
Route::get('/horarios-disponiveis', [Site\DisponibilidadeController::class, 'index'])->middleware('throttle:60,1')->name('horarios');
Route::post('/agendar', [Site\AgendamentoController::class, 'store'])->middleware('throttle:5,1')->name('agendar');
Route::post('/lista-espera', [Site\AgendamentoController::class, 'listaEspera'])->middleware('throttle:5,1')->name('lista-espera');
Route::get('/agendamento/confirmado/{token}', [Site\AgendamentoController::class, 'confirmado'])->name('agendamento.confirmado');
Route::post('/agendamento/{token}/whatsapp-enviado', [Site\AgendamentoController::class, 'whatsappEnviado'])->middleware('throttle:20,1')->name('agendamento.whatsapp');
Route::get('/agendamento/{token}/calendario.ics', [Site\AgendamentoController::class, 'ics'])->name('agendamento.ics');

require __DIR__ . '/painel.php';
require __DIR__ . '/auth.php';
