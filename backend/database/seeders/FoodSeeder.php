<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $foods = [
            ['name' => 'Apple', 'calories_per_100g' => 52],
            ['name' => 'Banana', 'calories_per_100g' => 89],
            ['name' => 'Chicken Breast', 'calories_per_100g' => 165],
            ['name' => 'White Rice (Cooked)', 'calories_per_100g' => 130],
            ['name' => 'Brown Rice (Cooked)', 'calories_per_100g' => 112],
            ['name' => 'Whole Egg', 'calories_per_100g' => 155],
            ['name' => 'Egg White', 'calories_per_100g' => 52],
            ['name' => 'Ground Beef (80% Lean)', 'calories_per_100g' => 254],
            ['name' => 'Broccoli', 'calories_per_100g' => 34],
            ['name' => 'Salmon', 'calories_per_100g' => 208],
            ['name' => 'Oats', 'calories_per_100g' => 389],
            ['name' => 'Avocado', 'calories_per_100g' => 160],
            ['name' => 'Peanut Butter', 'calories_per_100g' => 588],
            ['name' => 'Olive Oil', 'calories_per_100g' => 884],
            ['name' => 'Whole Wheat Bread', 'calories_per_100g' => 247],
            ['name' => 'Firm Tofu', 'calories_per_100g' => 144],
            ['name' => 'Spinach', 'calories_per_100g' => 23],
            ['name' => 'Black Beans', 'calories_per_100g' => 132],
            ['name' => 'Sweet Potato', 'calories_per_100g' => 86],
            ['name' => 'Greek Yogurt (Non-fat)', 'calories_per_100g' => 59],
            ['name' => 'Almonds', 'calories_per_100g' => 579],
            ['name' => 'Cheddar Cheese', 'calories_per_100g' => 402],
            ['name' => 'Tuna (Canned in Water)', 'calories_per_100g' => 86],
            ['name' => 'Milk (Whole)', 'calories_per_100g' => 61],
            ['name' => 'Bacon', 'calories_per_100g' => 541],
        ];

        DB::table('foods')->insert($foods);
    }
}
