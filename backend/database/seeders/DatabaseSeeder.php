<?php

namespace Database\Seeders;

use App\Models\Passagem;
use App\Models\Passageiro;
use App\Models\User;
use App\Models\Viagem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Motorista de demonstração (login: miguel / senha: 12345678)
        $motorista = User::create([
            'name' => 'Miguel Queiroz',
            'cpf' => '12345678901',
            'telefone' => '84999990000',
            'email' => 'miguel@rotafacil.test',
            'login' => 'miguel',
            'cidade_estado' => 'Alexandria, RN',
            'password' => '12345678',
        ]);

        // Veículos
        $van16 = $motorista->veiculos()->create([
            'identificacao' => 'Van 1',
            'placa' => 'RGA2B34',
            'modelo' => 'Mercedes-Benz Sprinter',
            'tipo' => 'van',
            'capacidade' => 16,
        ]);
        $van6 = $motorista->veiculos()->create([
            'identificacao' => 'Van 2',
            'placa' => 'QYT4F56',
            'modelo' => 'Renault Master',
            'tipo' => 'van',
            'capacidade' => 6,
        ]);

        // Passageiros (CPFs fictícios, só para teste)
        $nomes = ['Ana Souza', 'Bruno Lima', 'Carla Mendes', 'Diego Alves',
                  'Elisa Rocha', 'Fabio Santos', 'Gabriela Costa', 'Henrique Dias'];
        $passageiros = [];
        foreach ($nomes as $i => $nome) {
            $passageiros[] = $motorista->passageiros()->create([
                'nome' => $nome,
                'cpf' => str_pad((string) (1000 + $i), 11, '0', STR_PAD_LEFT),
                'telefone' => '849990' . str_pad((string) (1000 + $i), 5, '0', STR_PAD_LEFT),
            ]);
        }

        // Viagens
        $viagem1 = $motorista->viagens()->create([
            'veiculo_id' => $van16->id,
            'codigo' => 'V-' . Str::upper(Str::random(6)),
            'origem' => 'São Miguel',
            'destino' => 'Pau dos Ferros',
            'saida' => now()->addDays(2)->setTime(6, 30),
            'valor' => 25,
            'capacidade' => $van16->capacidade,
        ]);
        $viagem2 = $motorista->viagens()->create([
            'veiculo_id' => $van6->id,
            'codigo' => 'V-' . Str::upper(Str::random(6)),
            'origem' => 'São Miguel',
            'destino' => 'Mossoró',
            'saida' => now()->addDays(3)->setTime(5, 0),
            'valor' => 75,
            'capacidade' => $van6->capacidade,
        ]);

        // Passagens da viagem 1: 5 pagas e 3 fiado
        $assentos = [1, 2, 3, 5, 6, 9, 10, 12];
        foreach ($assentos as $i => $assento) {
            $forma = $i < 5 ? ($i % 2 ? 'dinheiro' : 'pix') : 'fiado';
            $this->vender($viagem1, $passageiros[$i], $assento, $forma);
        }

        // Passagens da viagem 2: 1 paga e 2 fiado
        foreach ([1, 2, 3] as $i => $assento) {
            $this->vender($viagem2, $passageiros[$i], $assento, $i === 0 ? 'pix' : 'fiado');
        }

        // Encomendas com histórico
        $enc1 = $motorista->encomendas()->create([
            'viagem_id' => $viagem1->id,
            'codigo' => 'E-' . Str::upper(Str::random(6)),
            'remetente_nome' => 'Maria Oliveira',
            'remetente_telefone' => '84988880001',
            'destinatario_nome' => 'José Pereira',
            'destinatario_telefone' => '84988880002',
            'descricao' => 'Caixa de roupas',
            'origem' => 'São Miguel',
            'destino' => 'Pau dos Ferros',
            'valor' => 20,
            'situacao' => 'em_transporte',
        ]);
        $enc1->movimentacoes()->create(['situacao_anterior' => null, 'nova_situacao' => 'cadastrada']);
        $enc1->movimentacoes()->create(['situacao_anterior' => 'cadastrada', 'nova_situacao' => 'em_transporte']);

        $enc2 = $motorista->encomendas()->create([
            'codigo' => 'E-' . Str::upper(Str::random(6)),
            'remetente_nome' => 'Paulo Nunes',
            'remetente_telefone' => '84988880003',
            'destinatario_nome' => 'Lúcia Nunes',
            'destinatario_telefone' => '84988880004',
            'descricao' => 'Documentos',
            'origem' => 'Mossoró',
            'destino' => 'São Miguel',
            'valor' => 10,
            'situacao' => 'entregue',
            'data_entrega' => now()->subDay(),
        ]);
        $enc2->movimentacoes()->create(['situacao_anterior' => null, 'nova_situacao' => 'cadastrada']);
        $enc2->movimentacoes()->create(['situacao_anterior' => 'cadastrada', 'nova_situacao' => 'em_transporte']);
        $enc2->movimentacoes()->create(['situacao_anterior' => 'em_transporte', 'nova_situacao' => 'entregue']);
    }

    private function vender(Viagem $viagem, Passageiro $passageiro, int $assento, string $forma): void
    {
        $passagem = $viagem->passagens()->create([
            'passageiro_id' => $passageiro->id,
            'codigo' => 'P-' . Str::upper(Str::random(6)),
            'assento' => $assento,
            'valor' => $viagem->valor,
        ]);

        $fiado = $forma === 'fiado';
        $passagem->pagamentos()->create([
            'forma' => $forma,
            'situacao' => $fiado ? 'pendente' : 'pago',
            'valor' => $viagem->valor,
            'data_pagamento' => $fiado ? null : now(),
        ]);
    }
}