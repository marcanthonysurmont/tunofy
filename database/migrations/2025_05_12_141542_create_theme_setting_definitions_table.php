<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_setting_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_setting_definitions');
    }
};
