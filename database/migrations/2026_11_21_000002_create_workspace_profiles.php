<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workspace_profiles', function(Blueprint $table){
            $table->id();$table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('client_uuid')->unique();$table->unsignedBigInteger('version')->default(1);$table->string('name');$table->timestamps();$table->softDeletes();
        });
        DB::table('users')->select(['id','name'])->orderBy('id')->chunkById(500,function($rows){foreach($rows as $row)DB::table('workspace_profiles')->insert([
            'user_id'=>$row->id,'name'=>$row->name,'client_uuid'=>(string)Str::uuid(),'version'=>1,'created_at'=>now(),'updated_at'=>now()]);});
    }
    public function down(): void { Schema::dropIfExists('workspace_profiles'); }
};
