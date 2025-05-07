<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultPresetSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('presets')->insert([
            'name' => 'Quick Party',
            'description' => 'The perfect option for a party where time is limited but fun is priority.',
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

        DB::table('presets')->insert([
            'name' => 'Marathon',
            'description' => 'Having a long party? This preset is perfect for long sessions where you want to keep the fun going.',
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

        DB::table('presets')->insert([
            'name' => 'Request night',
            'description' => 'A preset for a night where everyone can request their favorite songs without any voting.',
            'mix_id' => null,
            'is_system' => true,
            'batch_size' => 10,
            'num_rounds' => null,
            'requires_approval' => false,
            'voting_enabled' => false,
            'kill_percentage_percent' => 30,
            'priority_boost_new' => true,
            'auto_remove_negative' => true,
            'emoji_chat_enabled' => true,
        ]);
    }
}
