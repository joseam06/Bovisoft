<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salud', function (Blueprint $table) {
            $table->enum('categoria', ['preventivo', 'clinico', 'reproductivo', 'seguimiento'])
                  ->nullable()
                  ->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('salud', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};