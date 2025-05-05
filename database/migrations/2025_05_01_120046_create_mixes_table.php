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
            $table->timestamp('session_code_expires_at')->nullable();
            $table->enum('session_code_permission', ['view', 'contribute', 'edit'])->nullable();
            $table->boolean('is_public');
            $table->boolean('is_active')->default(false);
            $table->foreignId('co_dj_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('preset_id')->nullable()->default(1);
            $table->string('avatar')->nullable();
            $table->integer('mix_count')->default(0);
            $table->timestamps();

            $table->index(['session_code', 'session_code_expires_at']);
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
