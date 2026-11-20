<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $colunas = [
        'cpf' => 14,
        'telefone' => 20,
        'numero_cnh' => 20,
        'categoria_cnh' => 3,
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ($this->colunas as $nome => $tamanho) {
                if (! Schema::hasColumn('users', $nome)) {
                    $table->string($nome, $tamanho)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        // Não remove nada: as colunas podem ter existido antes desta migration.
    }
};
