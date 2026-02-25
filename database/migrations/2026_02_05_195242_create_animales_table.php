<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre')->nullable();
            $table->enum('tipo', ['vaca', 'ternero', 'toro', 'novilla']);
            $table->enum('sexo', ['macho', 'hembra']);
            $table->string('raza')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->decimal('peso_actual', 8, 2)->nullable();
            $table->string('color')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('activo');
            $table->foreignId('finca_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animales');
    }
};