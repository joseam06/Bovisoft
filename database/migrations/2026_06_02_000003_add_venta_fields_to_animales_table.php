<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los campos de venta a la tabla animales.
     * Cuando un animal se marca como "vendido" desde el módulo Finanzas,
     * se registra el ingreso_id para poder trazar la transacción completa.
     */
    public function up(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->date('fecha_venta')->nullable()->after('estado');
            $table->decimal('precio_venta', 14, 2)->nullable()->after('fecha_venta');
            $table->foreignId('ingreso_venta_id')
                ->nullable()
                ->after('precio_venta')
                ->constrained('ingresos')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->dropForeign(['ingreso_venta_id']);
            $table->dropColumn(['fecha_venta', 'precio_venta', 'ingreso_venta_id']);
        });
    }
};