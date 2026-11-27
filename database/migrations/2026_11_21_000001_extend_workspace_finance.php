<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        $duplicates = DB::table('ingresos')->select('animal_id')->whereNull('deleted_at')->where('tipo', 'venta_animal')->whereNotNull('animal_id')
            ->groupBy('animal_id')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicates) throw new RuntimeException('Existen varias ventas activas para un animal. Revisa los vínculos antes de migrar; no se modificaron importes.');
        foreach (['ingresos' => 'ING', 'egresos' => 'EGR'] as $name => $prefix) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('client_uuid')->nullable()->unique();
                $table->unsignedBigInteger('version')->default(1);
            });
            $highest = (int) (DB::table($name)->max('id') ?? 0);
            DB::table($name)->orderBy('id')->chunkById(500, function ($rows) use ($name, $prefix, &$highest) {
                foreach ($rows as $row) {
                    DB::table($name)->where('id', $row->id)->update(['client_uuid' => (string) Str::uuid()]);
                    if (preg_match('/^'.$prefix.'-(\d+)$/', $row->codigo, $m)) $highest = max($highest, (int) $m[1]);
                }
            });
            Schema::table($name, fn (Blueprint $table) => $table->uuid('client_uuid')->nullable(false)->change());
            DB::table('workspace_code_counters')->insert(['name' => $name, 'value' => $highest]);
        }
        Schema::table('ingresos', fn (Blueprint $table) => $table->foreignId('active_sale_animal_id')->nullable()->unique()->constrained('animales')->restrictOnDelete());
        DB::table('ingresos')->whereNull('deleted_at')->where('tipo', 'venta_animal')->whereNotNull('animal_id')
            ->update(['active_sale_animal_id' => DB::raw('animal_id')]);
    }
    public function down(): void
    {
        Schema::table('ingresos', function (Blueprint $table) {
            $table->dropForeign(['active_sale_animal_id']);$table->dropUnique(['active_sale_animal_id']);$table->dropColumn('active_sale_animal_id');
        });
        foreach (['ingresos', 'egresos'] as $name) {
            DB::table('workspace_code_counters')->where('name', $name)->delete();
            Schema::table($name, function (Blueprint $table) {$table->dropUnique(['client_uuid']);$table->dropColumn(['client_uuid','version']);});
        }
    }
};
