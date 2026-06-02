<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // ING-000001

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('finca_id')->constrained('fincas')->onDelete('cascade');
            $table->foreignId('animal_id')->nullable()->constrained('animales')->onDelete('set null');

            // Tipo de ingreso
            $table->enum('tipo', [
                'venta_animal',
                'venta_leche',
                'subsidio',
                'arrendamiento',
                'otro',
            ])->default('otro');

            $table->decimal('monto', 14, 2);
            $table->date('fecha');
            $table->string('descripcion')->nullable();

            // Datos del comprador (solo para venta_animal / venta_leche)
            $table->string('comprador_nombre')->nullable();
            $table->string('comprador_telefono')->nullable();
            $table->string('comprador_documento')->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};