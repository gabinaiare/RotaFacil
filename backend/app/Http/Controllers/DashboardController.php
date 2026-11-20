<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $motorista = $request->user();

        // Tudo parte do motorista logado: cada um vê só os próprios dados
        $viagens = $motorista->viagens()
            ->with('veiculo')
            ->withCount(['passagens as ocupados' => fn ($q) => $q->where('situacao', 'ativa')])
            ->where('situacao', 'agendada')
            ->where('saida', '>=', now())
            ->orderBy('saida')
            ->limit(5)
            ->get();

        $veiculos = $motorista->veiculos()->orderBy('modelo')->get();

        // Para os painéis de venda e de nova encomenda, que abrem no próprio Início
        $vendaveis = $motorista->viagens()
            ->where('situacao', 'agendada')
            ->with(['veiculo', 'passagens' => fn ($q) => $q->where('situacao', 'ativa')])
            ->orderBy('saida')
            ->get();

        $passageiros = $motorista->passageiros()->orderBy('nome')->get();

        return view('dashboard', compact('viagens', 'veiculos', 'vendaveis', 'passageiros'));
    }
}
