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
       Schema::create('encomendas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('viagem_id')->nullable()->constrained('viagens')->nullOnDelete();
    $table->string('codigo', 12)->unique();
    $table->string('remetente_nome', 120);
    $table->string('remetente_telefone', 20);
    $table->string('destinatario_nome', 120);
    $table->string('destinatario_telefone', 20);
    $table->string('descricao', 255);
    $table->string('origem', 80);
    $table->string('destino', 80);
    $table->decimal('valor', 10, 2)->nullable();
    $table->enum('situacao', ['cadastrada', 'em_transporte', 'disponivel_retirada', 'entregue'])
          ->default('cadastrada');
    $table->dateTime('data_entrega')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encomendas');
    }
};
