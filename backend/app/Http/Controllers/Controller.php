<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** Permite voltar para a tela de origem (Início, Passageiros) em vez da tela padrão */
    protected function retorno(Request $request, string $padrao): string
    {
        return match ($request->input('retorno')) {
            'inicio' => route('dashboard'),
            'passageiros' => route('passageiros.index'),
            default => $padrao,
        };
    }
}