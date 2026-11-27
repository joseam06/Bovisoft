<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    private const ORIGINAL = ['vacunacion','desparasitacion','tratamiento','cirugia','revision','otro'];
    private const TYPES = ['vacunacion','vitaminizacion','desparasitacion','bioseguridad','enfermedad','tratamiento','diagnostico','cirugia','revision','sincronizacion','hormonas','protocolo','preparacion_iatf','alerta','control','carencia','otro'];

    public function up(): void { $this->change(self::TYPES); }

    public function down(): void
    {
        if (DB::table('salud')->whereNotIn('tipo', self::ORIGINAL)->exists()) {
            throw new RuntimeException('Hay tipos sanitarios ampliados. Se conserva la restricción actual para no perder registros.');
        }
        $this->change(self::ORIGINAL);
    }

    private function change(array $types): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Laravel implements PostgreSQL enums as varchar + a named CHECK.
            DB::statement('ALTER TABLE salud DROP CONSTRAINT IF EXISTS salud_tipo_check');
            $list = implode(',', array_map(fn ($type) => "'".$type."'", $types));
            DB::statement('ALTER TABLE salud ADD CONSTRAINT salud_tipo_check CHECK (tipo IN ('.$list.'))');
        } else {
            Schema::table('salud', fn (Blueprint $table) => $table->enum('tipo', $types)->default('vacunacion')->change());
        }
    }
};
