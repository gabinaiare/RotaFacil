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
     Schema::create('veiculos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('identificacao', 60);
    $table->string('placa', 8);
    $table->string('modelo', 80);
    $table->enum('tipo', ['van', 'onibus']);
    $table->unsignedSmallInteger('capacidade');
    $table->enum('situacao', ['disponivel', 'indisponivel'])->default('disponivel');
    $table->timestamps();

    $table->unique(['user_id', 'placa']);
    $table->unique(['user_id', 'identificacao']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
