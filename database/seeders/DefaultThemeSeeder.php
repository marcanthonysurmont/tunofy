<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultThemeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('theme_setting_definitions')->insert([
            'name' => 'Default',
        ]);

        DB::table('theme_setting_definitions')->insert([
            'name' => 'Party',
            'settings' => json_encode([
                'fireworks' => true,
                'background_enabled' => true,
                'falling_confetti' => true,
            ]),
        ]);
    }

}
