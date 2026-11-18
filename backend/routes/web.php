<?php

use App\Http\Controllers\AuthController;
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

    // Página provisória, será trocada pela tela "Visão geral"
    Route::get('/inicio', function () {
        return '<h1>Olá, ' . e(auth()->user()->name) . '</h1>'
            . '<form method="POST" action="' . route('logout') . '">'
            . csrf_field()
            . '<button>Sair</button></form>';
    })->name('dashboard');
});