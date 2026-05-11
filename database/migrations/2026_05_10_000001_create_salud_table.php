<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salud', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // SAL-000001

            $table->foreignId('animal_id')->constrained('animales')->onDelete('cascade');
            $table->foreignId('finca_id')->constrained('fincas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->enum('tipo', [
                'vacunacion', 'desparasitacion', 'tratamiento',
                'cirugia', 'revision', 'otro',
            ])->default('vacunacion');

            $table->string('nombre_producto');
            $table->string('enfermedad_prevenida')->nullable();
            $table->text('diagnostico')->nullable();           // ← nuevo

            $table->date('fecha_aplicacion');

            $table->decimal('dosis', 8, 2)->nullable();
            $table->string('unidad_dosis', 20)->nullable();
            $table->string('via_aplicacion', 50)->nullable();

            $table->string('lote_medicamento', 100)->nullable();
            $table->string('laboratorio')->nullable();
            $table->string('veterinario')->nullable();

            $table->decimal('costo', 10, 2)->nullable();

            $table->date('proxima_aplicacion')->nullable();

            $table->integer('dias_carencia')->default(0);
            $table->date('fin_carencia')->nullable();          // calculada

            $table->enum('estado', [
                'completado', 'en_tratamiento', 'pendiente', 'cancelado',
            ])->default('completado');

            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salud');
    }
};