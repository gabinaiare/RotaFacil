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
        Schema::create('movimentacoes_encomenda', function (Blueprint $table) {
    $table->id();
    $table->foreignId('encomenda_id')->constrained('encomendas')->cascadeOnDelete();
    $table->string('situacao_anterior', 30)->nullable();
    $table->string('nova_situacao', 30);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_encomenda');
    }
};
