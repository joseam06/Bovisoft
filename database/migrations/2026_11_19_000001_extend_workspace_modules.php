<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        foreach (['potreros' => 'POT', 'salud' => 'SAL'] as $name => $prefix) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('client_uuid')->nullable()->unique();
                $table->unsignedBigInteger('version')->default(1);
            });
            $highest = (int) (DB::table($name)->max('id') ?? 0);
            DB::table($name)->orderBy('id')->chunkById(500, function ($rows) use ($name, $prefix, &$highest) {
                foreach ($rows as $row) {
                    DB::table($name)->where('id', $row->id)->update(['client_uuid' => (string) Str::uuid()]);
                    if (preg_match('/^'.$prefix.'-(\d+)$/', $row->codigo, $match)) $highest = max($highest, (int) $match[1]);
                }
            });
            Schema::table($name, fn (Blueprint $table) => $table->uuid('client_uuid')->nullable(false)->change());
            DB::table('workspace_code_counters')->insert(['name' => $name, 'value' => $highest]);
        }
    }

    public function down(): void
    {
        foreach (['potreros', 'salud'] as $name) {
            DB::table('workspace_code_counters')->where('name', $name)->delete();
            Schema::table($name, function (Blueprint $table) {
                $table->dropUnique(['client_uuid']);
                $table->dropColumn(['client_uuid', 'version']);
            });
        }
    }
};
