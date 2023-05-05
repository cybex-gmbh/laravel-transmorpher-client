<?php

namespace Transmorpher\Migrations;

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
        Schema::create('transmorpher_uploads', function (Blueprint $table) {
            $table->id();
            $table->enum('state', ['initializing', 'processing', 'error', 'success', 'deleted']);
            $table->string('message');
            $table->string('token')->unique()->nullable();
            $table->foreignId('transmorpher_media_id')->constrained();
            $table->timestamps();
        });
    }
};
