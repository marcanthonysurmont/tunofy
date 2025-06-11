<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mix_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mix_id')->constrained()->onDelete('cascade');
            $table->integer('songs_played')->default(0);
            $table->integer('songs_liked')->default(0);
            $table->integer('songs_disliked')->default(0);
            $table->integer('songs_killed')->default(0);
            $table->decimal('minutes_played')->default(0);
            $table->integer('total_votes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mix_stats');
    }
};
