<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queue_songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mix_id')->constrained()->onDelete('cascade');
            $table->foreignId('song_id')->constrained()->onDelete('cascade');
            $table->foreignId('playback_session_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'playing', 'finished', 'interrupted'])->default('pending');
            $table->boolean('is_killed')->default(false);  
            $table->integer('round_number');
            $table->integer('order');
            $table->integer('like_count')->default(0);
            $table->integer('dislike_count')->default(0);
            $table->integer('priority_boost')->default(0);
            $table->dateTime('played_at')->nullable();
            $table->timestamps();

            $table->index(['mix_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_songs');
    }
};
