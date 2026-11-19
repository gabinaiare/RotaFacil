<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('cpf', 11)->unique()->after('name');
        $table->string('telefone', 20)->after('cpf');
        $table->string('login', 60)->unique()->after('email');
        $table->string('cidade_estado', 80)->nullable()->after('login');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropUnique(['cpf']);
        $table->dropUnique(['login']);
        $table->dropColumn(['cpf', 'telefone', 'login', 'cidade_estado']);
    });
}


};