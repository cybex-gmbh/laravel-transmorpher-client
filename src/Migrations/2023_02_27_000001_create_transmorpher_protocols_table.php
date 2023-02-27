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
        Schema::create('transmorpher_protocols', function (Blueprint $table) {
            $table->id();
            $table->enum('state', ['processing', 'error', 'success', 'deleted']);
            $table->string('message')->nullable();
            $table->string('id_token')->unique();
            $table->foreignId('transmorpher_media_id')->constrained();
            $table->timestamps();
        });
    }
};
