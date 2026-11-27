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
        foreach (['fincas', 'animales'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('client_uuid')->nullable()->unique();
                $table->unsignedBigInteger('version')->default(1);
            });
            DB::table($name)->orderBy('id')->chunkById(500, function ($rows) use ($name) {
                foreach ($rows as $row) {
                    DB::table($name)->where('id', $row->id)->update(['client_uuid' => (string) Str::uuid()]);
                }
            });
            Schema::table($name, fn (Blueprint $table) => $table->uuid('client_uuid')->nullable(false)->change());
        }
        Schema::create('workspace_sync_heads', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence')->default(0);
        });
        Schema::create('workspace_sync_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->string('module', 12);
            $table->uuid('record_uuid');
            $table->json('payload');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'sequence']);
        });
        Schema::create('workspace_sync_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('operation_uuid');
            $table->uuid('device_uuid');
            $table->char('request_hash', 64);
            $table->json('result');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'operation_uuid']);
        });
        Schema::create('workspace_sync_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->json('payload');
            $table->timestamp('expires_at');
        });
        Schema::create('workspace_code_counters', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('value');
        });
        foreach (['fincas' => 'FIN', 'animales' => 'ANI'] as $name => $prefix) {
            $highest = (int) (DB::table($name)->max('id') ?? 0);
            DB::table($name)->orderBy('id')->chunkById(500, function ($rows) use (&$highest, $prefix) {
                foreach ($rows as $row) {
                    if (preg_match('/^'.$prefix.'-(\d+)$/', $row->codigo, $matches)) {
                        $highest = max($highest, (int) $matches[1]);
                    }
                }
            });
            DB::table('workspace_code_counters')->insert(['name' => $name, 'value' => $highest]);
        }
    }

    public function down(): void
    {
        foreach (['workspace_sync_snapshots', 'workspace_sync_receipts', 'workspace_sync_changes', 'workspace_sync_heads', 'workspace_code_counters'] as $name) {
            Schema::dropIfExists($name);
        }
        foreach (['fincas', 'animales'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropUnique(['client_uuid']);
                $table->dropColumn(['client_uuid', 'version']);
            });
        }
    }
};
