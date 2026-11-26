<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O MySQL usava o índice composto como apoio da chave estrangeira.
        // Criamos um índice próprio antes de removê-lo.
        Schema::table('viagens', function (Blueprint $table) {
            $table->index('veiculo_id', 'viagens_veiculo_id_idx');
        });

        Schema::table('viagens', function (Blueprint $table) {
            $table->dropUnique(['veiculo_id', 'saida']);

            // Vale a data/hora de saída, exceto se a viagem foi cancelada
            $table->dateTime('saida_ativa')->nullable()
                ->storedAs("IF(situacao = 'cancelada', NULL, saida)");

            $table->unique(['veiculo_id', 'saida_ativa']);
        });
    }

    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->dropUnique(['veiculo_id', 'saida_ativa']);
            $table->dropColumn('saida_ativa');
            $table->unique(['veiculo_id', 'saida']);
        });

        Schema::table('viagens', function (Blueprint $table) {
            $table->dropIndex('viagens_veiculo_id_idx');
        });
    }
};
