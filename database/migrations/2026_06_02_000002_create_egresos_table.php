<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egresos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // EGR-000001

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('finca_id')->constrained('fincas')->onDelete('cascade');
            $table->foreignId('animal_id')->nullable()->constrained('animales')->onDelete('set null');

            // Categoría del egreso
            $table->enum('categoria', [
                'salud_animal',
                'compra_animal',
                'insumos_alimentacion',
                'mano_obra',
                'mantenimiento',
                'otro',
            ])->default('otro');

            $table->decimal('monto', 14, 2);
            $table->date('fecha');
            $table->string('descripcion')->nullable();
            $table->text('observaciones')->nullable();

            // Referencia opcional al registro de salud (para cruzar con módulo Salud)
            $table->foreignId('salud_id')->nullable()->constrained('salud')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos');
    }
};