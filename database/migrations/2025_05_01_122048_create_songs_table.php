<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mix_id')->constrained()->onDelete('cascade');
            $table->string('spotify_id')->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('duration_ms')->nullable();
            $table->dateTime('last_fetched_at')->nullable();
            $table->string('name');
            $table->string('artist');
            $table->string('image_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
