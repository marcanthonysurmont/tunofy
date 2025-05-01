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
        Schema::create('mixes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->string('session_code')->nullable();
            $table->boolean('is_public');
            $table->boolean('is_active')->default(false);
            $table->foreignId('co_dj_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('playback_device_id')->nullable();
            $table->foreignId('preset_id')->nullable()->default(1)->constrained()->onDelete('set null');
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mixes');
    }
};
