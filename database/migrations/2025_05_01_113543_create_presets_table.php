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
        Schema::create('presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Custom');
            $table->unsignedBigInteger('mix_id')->nullable();
            $table->boolean('is_system')->default(false);
            $table->integer('batch_size')->default(10);
            $table->integer('max_songs')->nullable();
            $table->integer('num_rounds')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('voting_enabled')->default(true);
            $table->integer('kill_percentage_percent')->default(30);
            $table->boolean('priority_boost_new')->default(true);
            $table->boolean('auto_remove_negative')->default(true);
            $table->boolean('emoji_chat_enabled')->default(true);
            $table->timestamps();

            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presets');
    }
};
