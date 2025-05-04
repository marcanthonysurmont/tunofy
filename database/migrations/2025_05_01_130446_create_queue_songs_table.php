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
            $table->integer('round_number');
            $table->integer('order');
            $table->integer('priority_boost')->default(0);
            $table->integer('vote_score')->default(0);      
            $table->boolean('is_killed')->default(false);  
            $table->boolean('is_playing')->default(false);
            $table->timestamps();
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
