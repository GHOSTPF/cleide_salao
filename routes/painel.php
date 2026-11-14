<?php

use App\Http\Controllers\Painel\Admin;
use App\Http\Controllers\Painel\PerfilController;
use App\Http\Controllers\Painel\Profissional;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Painel interno (auth + papel)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('painel')->group(function () {
    Route::get('/', fn () => redirect(auth()->user()->rotaInicial()))->name('painel');
    Route::get('perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::patch('perfil', [PerfilController::class, 'update'])->name('perfil.update');

    /*
    | Admin (dona do salão)
    */
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('agenda', [Admin\AgendaController::class, 'index'])->name('agenda');
        Route::get('horarios', [Admin\AgendaController::class, 'horarios'])->name('horarios');

        // Agendamentos
        Route::get('pendentes', [Admin\AgendamentoController::class, 'pendentes'])->name('pendentes');
        Route::get('agendamentos/exportar', [Admin\ExportacaoController::class, 'agendamentos'])->name('agendamentos.exportar');
        Route::get('agendamentos', [Admin\AgendamentoController::class, 'index'])->name('agendamentos.index');
        Route::get('agendamentos/novo', [Admin\AgendamentoController::class, 'create'])->name('agendamentos.create');
        Route::post('agendamentos', [Admin\AgendamentoController::class, 'store'])->name('agendamentos.store');
        Route::get('agendamentos/{agendamento}', [Admin\AgendamentoController::class, 'show'])->name('agendamentos.show');
        Route::patch('agendamentos/{agendamento}', [Admin\AgendamentoController::class, 'update'])->name('agendamentos.update');
        Route::post('agendamentos/{agendamento}/confirmar', [Admin\AgendamentoController::class, 'confirmar'])->name('agendamentos.confirmar');
        Route::post('agendamentos/{agendamento}/cancelar', [Admin\AgendamentoController::class, 'cancelar'])->name('agendamentos.cancelar');
        Route::post('agendamentos/{agendamento}/falta', [Admin\AgendamentoController::class, 'falta'])->name('agendamentos.falta');
        Route::post('agendamentos/{agendamento}/desfazer-falta', [Admin\AgendamentoController::class, 'desfazerFalta'])->name('agendamentos.desfazer-falta');
        Route::post('agendamentos/{agendamento}/concluir', [Admin\AgendamentoController::class, 'concluir'])->name('agendamentos.concluir');
        Route::post('agendamentos/{agendamento}/estornar', [Admin\AgendamentoController::class, 'estornar'])->name('agendamentos.estornar');
        Route::get('agendamentos/{agendamento}/reagendar', [Admin\AgendamentoController::class, 'reagendarForm'])->name('agendamentos.reagendar');
        Route::post('agendamentos/{agendamento}/reagendar', [Admin\AgendamentoController::class, 'reagendar']);
        Route::post('agendamentos/{agendamento}/nome-divergente', [Admin\AgendamentoController::class, 'nomeDivergente'])->name('agendamentos.nome-divergente');
        Route::post('agendamentos/{agendamento}/anotacao', [Admin\AgendamentoController::class, 'anotacao'])->name('agendamentos.anotacao');

        // Clientes
        Route::get('clientes/buscar', [Admin\ClienteController::class, 'buscar'])->name('clientes.buscar');
        Route::get('clientes/exportar', [Admin\ExportacaoController::class, 'clientes'])->name('clientes.exportar');
        Route::get('clientes/duplicadas', [Admin\ClienteController::class, 'duplicadas'])->name('clientes.duplicadas');
        Route::post('clientes/mesclar', [Admin\ClienteController::class, 'mesclar'])->name('clientes.mesclar');
        Route::post('clientes/ignorar-duplicidade', [Admin\ClienteController::class, 'ignorarDuplicidade'])->name('clientes.ignorar');
        Route::get('clientes', [Admin\ClienteController::class, 'index'])->name('clientes.index');
        Route::get('clientes/{cliente}', [Admin\ClienteController::class, 'show'])->name('clientes.show');
        Route::get('clientes/{cliente}/editar', [Admin\ClienteController::class, 'edit'])->name('clientes.edit');
        Route::put('clientes/{cliente}', [Admin\ClienteController::class, 'update'])->name('clientes.update');
        Route::post('clientes/{cliente}/bloquear', [Admin\ClienteController::class, 'bloquear'])->name('clientes.bloquear');
        Route::post('clientes/{cliente}/desbloquear', [Admin\ClienteController::class, 'desbloquear'])->name('clientes.desbloquear');
        Route::post('clientes/{cliente}/anotacao', [Admin\ClienteController::class, 'anotacao'])->name('clientes.anotacao');

        // Relacionamento
        Route::get('relacionamento/retornos', [Admin\RelacionamentoController::class, 'retornos'])->name('relacionamento.retornos');
        Route::get('relacionamento/aniversariantes', [Admin\RelacionamentoController::class, 'aniversariantes'])->name('relacionamento.aniversariantes');
        Route::get('relacionamento/inativas', [Admin\RelacionamentoController::class, 'inativas'])->name('relacionamento.inativas');
        Route::get('relacionamento/lista-espera', [Admin\RelacionamentoController::class, 'listaEspera'])->name('relacionamento.lista-espera');
        Route::post('relacionamento/lista-espera/{entrada}/status', [Admin\RelacionamentoController::class, 'statusListaEspera'])->name('relacionamento.lista-espera.status');

        // Catálogo e equipe
        Route::resource('categorias', Admin\CategoriaController::class)->except(['show', 'create', 'edit'])->parameters(['categorias' => 'categoria']);
        Route::resource('servicos', Admin\ServicoController::class)->except(['show'])->parameters(['servicos' => 'servico']);
        Route::resource('profissionais', Admin\ProfissionalController::class)->except(['show'])->parameters(['profissionais' => 'profissional']);
        Route::put('profissionais/{profissional}/acesso', [Admin\ProfissionalController::class, 'acesso'])->name('profissionais.acesso');
        Route::put('profissionais/{profissional}/servicos', [Admin\ProfissionalController::class, 'servicos'])->name('profissionais.servicos');
        Route::put('profissionais/{profissional}/horarios', [Admin\ProfissionalController::class, 'horarios'])->name('profissionais.horarios');
        Route::post('profissionais/{profissional}/bloqueios', [Admin\ProfissionalController::class, 'bloquear'])->name('profissionais.bloqueios.store');
        Route::delete('bloqueios/{bloqueio}', [Admin\ProfissionalController::class, 'desbloquear'])->name('bloqueios.destroy');
        Route::get('feriados', [Admin\FeriadoController::class, 'index'])->name('feriados.index');
        Route::post('feriados', [Admin\FeriadoController::class, 'store'])->name('feriados.store');
        Route::delete('feriados/{bloqueio}', [Admin\FeriadoController::class, 'destroy'])->name('feriados.destroy');

        // Financeiro
        Route::get('financeiro', [Admin\FinanceiroController::class, 'index'])->name('financeiro.index');
        Route::get('financeiro/exportar', [Admin\ExportacaoController::class, 'financeiro'])->name('financeiro.exportar');
        Route::resource('financeiro/despesas', Admin\DespesaController::class)->except(['show', 'index'])->names('financeiro.despesas')->parameters(['despesas' => 'despesa']);
        Route::get('financeiro/comissoes', [Admin\ComissaoController::class, 'index'])->name('financeiro.comissoes');
        Route::post('financeiro/comissoes/pagar', [Admin\ComissaoController::class, 'pagar'])->name('financeiro.comissoes.pagar');

        // Site
        Route::get('site/galeria', [Admin\GaleriaController::class, 'index'])->name('site.galeria');
        Route::post('site/galeria', [Admin\GaleriaController::class, 'store'])->name('site.galeria.store');
        Route::put('site/galeria/{foto}', [Admin\GaleriaController::class, 'update'])->name('site.galeria.update');
        Route::delete('site/galeria/{foto}', [Admin\GaleriaController::class, 'destroy'])->name('site.galeria.destroy');
        Route::get('site/depoimentos', [Admin\DepoimentoController::class, 'index'])->name('site.depoimentos');
        Route::post('site/depoimentos', [Admin\DepoimentoController::class, 'store'])->name('site.depoimentos.store');
        Route::put('site/depoimentos/{depoimento}', [Admin\DepoimentoController::class, 'update'])->name('site.depoimentos.update');
        Route::delete('site/depoimentos/{depoimento}', [Admin\DepoimentoController::class, 'destroy'])->name('site.depoimentos.destroy');
        Route::get('site/configuracoes', [Admin\ConfiguracaoController::class, 'edit'])->name('site.configuracoes');
        Route::put('site/configuracoes', [Admin\ConfiguracaoController::class, 'update'])->name('site.configuracoes.update');

        Route::get('atividades', [Admin\AtividadeController::class, 'index'])->name('atividades');
    });

    /*
    | Profissional
    */
    Route::middleware('role:profissional')->prefix('profissional')->name('profissional.')->group(function () {
        Route::get('/', [Profissional\AgendaController::class, 'index'])->name('agenda');
        Route::get('horarios', [Profissional\AgendaController::class, 'horarios'])->name('horarios');
        Route::get('agendamentos/{agendamento}', [Profissional\AgendaController::class, 'show'])->name('agendamentos.show');
        Route::post('agendamentos/{agendamento}/confirmar', [Profissional\AgendaController::class, 'confirmar'])->name('agendamentos.confirmar');
        Route::post('agendamentos/{agendamento}/concluir', [Profissional\AgendaController::class, 'concluir'])->name('agendamentos.concluir');
        Route::post('agendamentos/{agendamento}/falta', [Profissional\AgendaController::class, 'falta'])->name('agendamentos.falta');
        Route::post('agendamentos/{agendamento}/cancelar', [Profissional\AgendaController::class, 'cancelar'])->name('agendamentos.cancelar');
        Route::get('agendamentos/{agendamento}/reagendar', [Profissional\AgendaController::class, 'reagendarForm'])->name('agendamentos.reagendar');
        Route::post('agendamentos/{agendamento}/reagendar', [Profissional\AgendaController::class, 'reagendar']);
        Route::post('agendamentos/{agendamento}/anotacao', [Profissional\AgendaController::class, 'anotacao'])->name('agendamentos.anotacao');

        Route::get('clientes', [Profissional\ClienteController::class, 'index'])->name('clientes.index');
        Route::get('clientes/{cliente}', [Profissional\ClienteController::class, 'show'])->name('clientes.show');

        Route::get('resumo', [Profissional\ResumoController::class, 'index'])->name('resumo');

        Route::get('bloqueios', [Profissional\BloqueioController::class, 'index'])->name('bloqueios.index');
        Route::post('bloqueios', [Profissional\BloqueioController::class, 'store'])->name('bloqueios.store');
        Route::delete('bloqueios/{bloqueio}', [Profissional\BloqueioController::class, 'destroy'])->name('bloqueios.destroy');
    });
});
