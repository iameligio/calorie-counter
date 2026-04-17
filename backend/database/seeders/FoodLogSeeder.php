<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Food;
use App\Models\FoodLog;
use Carbon\Carbon;

class FoodLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $foods = Food::all();

        if ($users->isEmpty() || $foods->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // Create logs for the last 30 days
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now()->subDays($i);
                
                // Random number of items per day (between 3 and 6)
                $itemsCount = rand(3, 6);
                
                for ($j = 0; $j < $itemsCount; $j++) {
                    $food = $foods->random();
                    $grams = rand(50, 400);
                    $calories = round(($food->calories_per_100g / 100) * $grams);

                    FoodLog::create([
                        'user_id' => $user->id,
                        'food_id' => $food->id,
                        'grams' => $grams,
                        'calories' => $calories,
                        'consumed_at' => $date->copy()->setHour(rand(7, 21))->setMinute(rand(0, 59)),
                    ]);
                }
            }
        }
    }
}
