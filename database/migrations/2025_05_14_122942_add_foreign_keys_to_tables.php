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
        Schema::table('presets', function (Blueprint $table) {
            $table->foreign('mix_id')->references('id')->on('mixes')->onDelete('cascade');
        });

        Schema::table('mixes', function (Blueprint $table) {
            $table->foreign('preset_id')->references('id')->on('presets')->onDelete('set null');
            $table->foreign('theme_setting_definition_id')->references('id')->on('theme_setting_definitions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presets', function (Blueprint $table) {
            $table->dropForeign(['mix_id']);
        });

        Schema::table('mixes', function (Blueprint $table) {
            $table->dropForeign(['preset_id']);
        });
    }
};
