<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultPresetSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('presets')->insert([
            'name' => 'Default',
            'mix_id' => null,
            'is_system' => true,
            'batch_size' => 10,
            'num_rounds' => null,
            'requires_approval' => false,
            'voting_enabled' => true,
            'kill_percentage_percent' => 30,
            'priority_boost_new' => true,
            'auto_remove_negative' => true,
            'emoji_chat_enabled' => true,
        ]);
    }
}
