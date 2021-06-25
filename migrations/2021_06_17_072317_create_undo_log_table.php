<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUndoLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('undo_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('xid', 100);
            $table->string('context', 255)->default('');
            $table->binary('rollback_info');
            $table->unsignedTinyInteger('log_status')->default(0)->comment('0:normal status,1:defense status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('undo_log');
    }
}
