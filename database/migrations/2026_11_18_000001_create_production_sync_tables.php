<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producciones', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique();
            $table->unsignedBigInteger('version')->default(1);
        });
        DB::table('producciones')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('producciones')->where('id', $row->id)->update(['client_uuid' => (string) Str::uuid()]);
            }
        });
        Schema::table('producciones', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable(false)->change();
        });
        Schema::create('production_sync_heads', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence')->default(0);
        });
        Schema::create('production_sync_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->uuid('record_uuid');
            $table->string('action', 12);
            $table->json('payload');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'sequence']);
        });
        Schema::create('production_sync_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('operation_uuid');
            $table->uuid('device_uuid');
            $table->uuid('record_uuid');
            $table->char('request_hash', 64);
            $table->json('result');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'operation_uuid']);
        });
        Schema::create('production_sync_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('scope');
            $table->unsignedBigInteger('sequence');
            $table->json('payload');
            $table->unsignedInteger('page_size');
            $table->timestamp('expires_at');
        });
        Schema::create('production_code_counters', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('value');
        });
        $highest = 0;
        DB::table('producciones')->select(['id', 'codigo'])->orderBy('id')->chunkById(500, function ($rows) use (&$highest) {
            foreach ($rows as $row) {
                if (preg_match('/^PROD-(\d+)$/', $row->codigo, $matches)) {
                    $highest = max($highest, (int) $matches[1]);
                }
            }
        });
        DB::table('production_code_counters')->insert(['name' => 'production', 'value' => $highest]);
    }

    public function down(): void
    {
        foreach (['production_sync_snapshots', 'production_sync_receipts', 'production_sync_changes', 'production_sync_heads', 'production_code_counters'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('producciones', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn(['client_uuid', 'version']);
        });
    }
};
