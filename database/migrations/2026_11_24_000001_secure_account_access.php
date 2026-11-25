<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stop before changing the schema; never merge or rename existing users.
        $seen = [];
        foreach (DB::table('users')->select('id', 'email')->orderBy('id')->cursor() as $row) {
            $email = mb_strtolower(trim($row->email));
            if (isset($seen[$email])) {
                throw new RuntimeException('Hay correos equivalentes entre cuentas. Requiere revisión antes de migrar; no se fusionaron usuarios.');
            }
            $seen[$email] = true;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_identity')->nullable()->unique();
            $table->boolean('legacy_email_access')->default(false);
            $table->boolean('password_login_enabled')->default(true);
            $table->timestamp('password_set_at')->nullable();
            $table->unsignedBigInteger('credentials_version')->default(1);
            $table->string('pending_email')->nullable();
            $table->char('pending_email_token_hash', 64)->nullable();
            $table->timestamp('pending_email_expires_at')->nullable();
        });
        DB::table('users')->select('id', 'email', 'google_id')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('users')->where('id', $row->id)->update([
                    'email_identity' => mb_strtolower(trim($row->email)),
                    'legacy_email_access' => true,
                    // The old Google flow did not record password provenance.
                    'password_set_at' => $row->google_id === null ? now() : null,
                ]);
            }
        });
        Schema::table('users', fn (Blueprint $table) => $table->string('email_identity')->nullable(false)->change());
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email_identity']);
            $table->dropColumn(['email_identity', 'legacy_email_access', 'password_login_enabled', 'password_set_at', 'credentials_version', 'pending_email', 'pending_email_token_hash', 'pending_email_expires_at']);
        });
    }
};
