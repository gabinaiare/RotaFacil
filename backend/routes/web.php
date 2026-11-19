<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PassageiroController;
use App\Http\Controllers\PassagemController;
use App\Http\Controllers\VeiculoController;
use App\Http\Controllers\ViagemController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

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

    // Telas ainda não construídas. Cada uma sai desta lista quando ganhar seu controller.
    $provisorias = [
        'encomendas' => 'Encomendas',
        'perfil'     => 'Meu perfil',
        'relatorios' => 'Relatórios',
    ];

    foreach ($provisorias as $caminho => $titulo) {
        Route::get("/{$caminho}", fn () => view('em-construcao', ['titulo' => $titulo]))
            ->name("{$caminho}.index");
    }
});
