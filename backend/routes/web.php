<?php

use App\Http\Controllers\RecuperarAcessoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EncomendaController;
use App\Http\Controllers\PassageiroController;
use App\Http\Controllers\PassagemController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\ViagemController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/recuperar-acesso', [RecuperarAcessoController::class, 'mostrarPedido'])->name('senha.pedir');
    Route::post('/recuperar-acesso', [RecuperarAcessoController::class, 'enviarLink'])
        ->middleware('throttle:5,1')->name('senha.enviar');

    Route::get('/redefinir-senha/{token}', [RecuperarAcessoController::class, 'mostrarRedefinicao'])->name('password.reset');
    Route::post('/redefinir-senha', [RecuperarAcessoController::class, 'redefinir'])->name('senha.redefinir');
    Route::get('/cadastro', [AuthController::class, 'mostrarCadastro'])->name('cadastro');
    Route::post('/cadastro', [AuthController::class, 'cadastrar']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/inicio', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/veiculos', [VeiculoController::class, 'index'])->name('veiculos.index');
    Route::post('/veiculos', [VeiculoController::class, 'store'])->name('veiculos.store');
    Route::put('/veiculos/{id}', [VeiculoController::class, 'update'])->whereNumber('id')->name('veiculos.update');

    Route::get('/passageiros', [PassageiroController::class, 'index'])->name('passageiros.index');
    Route::post('/passageiros', [PassageiroController::class, 'store'])->name('passageiros.store');
    Route::put('/passageiros/{id}', [PassageiroController::class, 'update'])->whereNumber('id')->name('passageiros.update');

    Route::get('/viagens', [ViagemController::class, 'index'])->name('viagens.index');
    Route::post('/viagens', [ViagemController::class, 'store'])->name('viagens.store');
    Route::put('/viagens/{id}', [ViagemController::class, 'update'])->whereNumber('id')->name('viagens.update');
    Route::post('/viagens/{id}/cancelar', [ViagemController::class, 'cancelar'])->whereNumber('id')->name('viagens.cancelar');
    Route::post('/viagens/{id}/concluir', [ViagemController::class, 'concluir'])->whereNumber('id')->name('viagens.concluir');

    Route::get('/passagens', [PassagemController::class, 'index'])->name('passagens.index');
    Route::post('/passagens', [PassagemController::class, 'store'])->name('passagens.store');
    Route::post('/passagens/{id}/cancelar', [PassagemController::class, 'cancelar'])->whereNumber('id')->name('passagens.cancelar');
    Route::post('/passagens/{id}/pagamento', [PassagemController::class, 'receber'])->whereNumber('id')->name('passagens.receber');
    Route::get('/passagens/{id}/comprovante', [PassagemController::class, 'comprovante'])->whereNumber('id')->name('passagens.comprovante');

    Route::get('/encomendas', [EncomendaController::class, 'index'])->name('encomendas.index');
    Route::post('/encomendas', [EncomendaController::class, 'store'])->name('encomendas.store');
    Route::put('/encomendas/{id}', [EncomendaController::class, 'update'])->whereNumber('id')->name('encomendas.update');
    Route::post('/encomendas/{id}/status', [EncomendaController::class, 'atualizarStatus'])->whereNumber('id')->name('encomendas.status');
    Route::post('/encomendas/{id}/pagamento', [EncomendaController::class, 'receber'])->whereNumber('id')->name('encomendas.receber');
    Route::get('/encomendas/{id}/comprovante', [EncomendaController::class, 'comprovante'])->whereNumber('id')->name('encomendas.comprovante');

    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil.index');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

    Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios.index');
    Route::get('/relatorios/csv', [RelatorioController::class, 'csv'])->name('relatorios.csv');
  });