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
       Schema::create('viagens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('veiculo_id')->constrained('veiculos');
    $table->string('codigo', 12)->unique();
    $table->string('origem', 80);
    $table->string('destino', 80);
    $table->dateTime('saida');
    $table->decimal('valor', 10, 2);
    $table->unsignedSmallInteger('capacidade');
    $table->enum('situacao', ['agendada', 'em_andamento', 'concluida', 'cancelada'])
          ->default('agendada');
    $table->timestamps();

    $table->unique(['veiculo_id', 'saida']);
    $table->index(['user_id', 'saida']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viagens');
    }
};
