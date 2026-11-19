<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passageiros', function (Blueprint $table) {
            $table->string('cpf', 11)->nullable()->change();
            $table->string('email', 120)->nullable()->after('telefone');
            $table->unique(['user_id', 'telefone']);
        });
    }

    public function down(): void
    {
        Schema::table('passageiros', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'telefone']);
            $table->dropColumn('email');
            $table->string('cpf', 11)->nullable(false)->change();
        });
    }
};
