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
            $table->string('differentiator');
            $table->string('public_path')->nullable();
            $table->enum('type', ['image', 'video']);
            $table->boolean('is_ready')->default(0);
            $table->enum('last_response', ['initializing', 'processing', 'error', 'success', 'deleted'])->nullable();
            $table->string('last_upload_token')->nullable();
            $table->timestamps();

            $table->unique(['transmorphable_id', 'transmorphable_type', 'differentiator'], 'transmorphable_id_type_differentiator_unique');
        });
    }
};
