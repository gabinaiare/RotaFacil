<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('passagens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('viagem_id')->constrained('viagens');
    $table->foreignId('passageiro_id')->constrained('passageiros');
    $table->string('codigo', 12)->unique();
    $table->unsignedSmallInteger('assento');
    $table->decimal('valor', 10, 2);
    $table->enum('situacao', ['ativa', 'cancelada'])->default('ativa');
    $table->dateTime('data_cancelamento')->nullable();
    $table->string('motivo_cancelamento', 200)->nullable();
    $table->timestamps();

    // Coluna calculada pelo próprio MySQL: vale o número do assento só enquanto
    // a passagem está ativa; se cancelada, vira NULL.
    $table->unsignedSmallInteger('assento_ativo')->nullable()
          ->storedAs("IF(situacao = 'ativa', assento, NULL)");

    // O banco recusa 2 passagens ativas no mesmo assento da mesma viagem (MS-2).
    $table->unique(['viagem_id', 'assento_ativo']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passagens');
    }
};
