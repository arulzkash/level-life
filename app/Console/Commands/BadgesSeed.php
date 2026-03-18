<?php

namespace App\Console\Commands;

use App\Models\Badge;
use Illuminate\Console\Command;

class BadgesSeed extends Command
{
    protected $signature = 'badges:seed';
    protected $description = 'Seed or update default badges';

    public function handle(): int
    {
        $badges = [
            ['key' => 'streak_3', 'name' => 'Warm-up', 'category' => 'streak', 'description' => 'Reach a 3-day streak'],
            ['key' => 'streak_7', 'name' => 'Consistent', 'category' => 'streak', 'description' => 'Reach a 7-day streak'],
            ['key' => 'streak_14', 'name' => 'Disciplined', 'category' => 'streak', 'description' => 'Reach a 14-day streak'],
            ['key' => 'streak_30', 'name' => 'Iron Will', 'category' => 'streak', 'description' => 'Reach a 30-day streak'],
            ['key' => 'streak_60', 'name' => 'Unbreakable', 'category' => 'streak', 'description' => 'Reach a 60-day streak'],
            ['key' => 'streak_100', 'name' => 'Legend', 'category' => 'streak', 'description' => 'Reach a 100-day streak'],
            ['key' => 'streak_150', 'name' => 'Titan', 'category' => 'streak', 'description' => 'Reach a 150-day streak'],
            ['key' => 'streak_200', 'name' => 'Mythic', 'category' => 'streak', 'description' => 'Reach a 200-day streak'],
            ['key' => 'streak_365', 'name' => 'Eternal', 'category' => 'streak', 'description' => 'Reach a 365-day streak'],
            ['key' => 'streak_500', 'name' => 'Transcendent', 'category' => 'streak', 'description' => 'Reach a 500-day streak'],

            ['key' => 'second_wind', 'name' => 'Second Wind', 'category' => 'recovery', 'description' => 'Used a freeze to keep streak alive'],
            ['key' => 'comeback_kid', 'name' => 'Comeback Kid', 'category' => 'recovery', 'description' => 'Recovered after a reset and reached 7 again'],
        ];

        foreach ($badges as $b) {
            Badge::updateOrCreate(['key' => $b['key']], $b);
        }

        $this->info('Badges seeded.');
        return self::SUCCESS;
    }
}
