<?php

namespace Transmorpher\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transmorpher_media', function (Blueprint $table) {
            $table->id();
            $table->morphs('transmorphable');
            $table->string('topic');
            $table->string('public_path')->nullable();
            $table->enum('type', ['image', 'video']);
            $table->boolean('is_ready');
            $table->enum('latest_upload_state', ['deleted', 'error', 'initializing', 'processing', 'success', 'uploading'])->nullable();
            $table->string('latest_upload_token')->unique()->nullable();
            $table->timestamps();

            $table->unique(['transmorphable_id', 'transmorphable_type', 'topic'], 'transmorphable_id_type_topic_unique');
        });
    }
};
