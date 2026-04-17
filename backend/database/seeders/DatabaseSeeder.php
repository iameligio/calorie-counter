<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@myfitnesspal.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'calorie_target' => 2000,
                'is_admin' => true,
            ]
        );

        $this->call([
            FoodSeeder::class,
            FoodLogSeeder::class,
        ]);
    }
}
