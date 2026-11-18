<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VeiculoController;
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

    // Telas ainda não construídas. Cada uma sai desta lista quando ganhar seu controller.
    $provisorias = [
        'viagens'     => 'Minhas viagens',
        'passagens'   => 'Passagens',
        'encomendas'  => 'Encomendas',
        'passageiros' => 'Passageiros',
        'perfil'      => 'Meu perfil',
        'relatorios'  => 'Relatórios',
    ];

    foreach ($provisorias as $caminho => $titulo) {
        Route::get("/{$caminho}", fn () => view('em-construcao', ['titulo' => $titulo]))
            ->name("{$caminho}.index");
    }
});
