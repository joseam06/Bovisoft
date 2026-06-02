<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('animal_id')->constrained('animales')->onDelete('cascade');
            $table->foreignId('finca_id')->constrained('fincas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('fecha');
            $table->enum('sesion', ['manana', 'tarde', 'noche'])->nullable();
            $table->decimal('litros', 8, 2);
            $table->enum('calidad', ['normal', 'buena', 'excelente', 'rechazada'])->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producciones');
    }
};