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
        Schema::table('animales', function (Blueprint $table) {
            // Agregar columna potrero_id (nullable porque el animal puede no estar en un potrero)
            $table->foreignId('potrero_id')
                ->nullable()
                ->after('finca_id')
                ->constrained('potreros')
                ->onDelete('set null');
            
            // Agregar campo numero (opcional, para identificación del animal)
            $table->string('numero')->nullable()->after('codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->dropForeign(['potrero_id']);
            $table->dropColumn('potrero_id');
            $table->dropColumn('numero');
        });
    }
};