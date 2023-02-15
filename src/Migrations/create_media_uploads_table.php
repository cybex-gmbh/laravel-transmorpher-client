<?php

namespace Cybex\Transmorpher\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_uploads', function (Blueprint $table) {
            $table->id();
            $table->morphs('uploadable');
            $table->string('differentiator');
            $table->string('id_token')->unique();
            $table->timestamps();

            $table->unique(['uploadable_id', 'uploadable_type', 'differentiator']);
        });
    }
};