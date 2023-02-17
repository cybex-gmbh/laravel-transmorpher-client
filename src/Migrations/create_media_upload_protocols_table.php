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
        Schema::create('media_upload_protocols', function (Blueprint $table) {
            $table->id();
            $table->enum('state', ['processing', 'error', 'success', 'deleted']);
            $table->string('public_path')->nullable();
            $table->foreignId('media_upload_id')->constrained();
            $table->timestamps();
        });
    }
};
