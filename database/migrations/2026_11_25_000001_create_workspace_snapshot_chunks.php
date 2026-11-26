<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {
        Schema::create('workspace_snapshot_chunks',function(Blueprint $table){
            $table->id();$table->uuid('snapshot_id');$table->unsignedInteger('part');$table->mediumText('payload');
            $table->foreign('snapshot_id')->references('id')->on('workspace_sync_snapshots')->cascadeOnDelete();
            $table->unique(['snapshot_id','part']);
        });
    }
    public function down():void {Schema::dropIfExists('workspace_snapshot_chunks');}
};
