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
        Schema::create('potreros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->decimal('area', 10, 2)->nullable()->comment('Área en hectáreas');
            $table->string('tipo_pasto')->nullable();
            $table->integer('capacidad_animales')->nullable()->comment('Cuántos animales puede albergar');
            $table->integer('animales_actuales')->default(0)->comment('Cuántos animales tiene actualmente');
            $table->enum('estado', ['activo', 'en_descanso', 'en_mantenimiento'])->default('activo');
            $table->date('fecha_ultima_rotacion')->nullable();
            $table->integer('dias_descanso')->default(30)->comment('Días de descanso requeridos');
            $table->text('observaciones')->nullable();
            
            // Relaciones
            $table->foreignId('finca_id')->constrained('fincas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potreros');
    }
};