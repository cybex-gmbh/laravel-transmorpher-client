<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transmorpher_media', function (Blueprint $table) {
            $table->enum('type', ['image', 'video', 'document'])->change();
        });
    }
};
