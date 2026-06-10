<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Populates the foods table with Foundation Foods + SR Legacy entries from the
 * USDA FoodData Central API (https://fdc.nal.usda.gov).
 *
 * All values are per 100 g, so they map directly to our schema.
 *
 * Get a free API key (instant email delivery) at:
 *   https://api.nal.usda.gov/
 * Then add to backend/.env:
 *   USDA_API_KEY=your_key_here
 *
 * Run:
 *   php artisan db:seed --class=UsdaFoodSeeder
 *
 * Re-running is safe: foods are upserted on name+source to avoid duplicates.
 */
class UsdaFoodSeeder extends Seeder
{
    private const BASE_URL = 'https://api.nal.usda.gov/fdc/v1';

    // USDA nutrient numbers we care about
    // Energy: SR Legacy uses 208, Foundation uses 957 (Atwater General) or 958 (Atwater Specific)
    private const NUTRIENT_ENERGY_IDS = ['208', '957', '958'];
    private const NUTRIENT_PROTEIN  = '203';
    private const NUTRIENT_FAT      = '204';
    private const NUTRIENT_CARBS    = '205';

    // Foundation Foods are very detailed; SR Legacy covers ~7 700 common foods.
    private const DATA_TYPES = ['Foundation', 'SR Legacy'];

    private const PAGE_SIZE = 200; // USDA max per request

    public function run(): void
    {
        $apiKey = env('USDA_API_KEY');

        if (! $apiKey) {
            $this->command->error(
                "No USDA API key found.\n" .
                "Get a free key at https://api.nal.usda.gov/ and add:\n" .
                "  USDA_API_KEY=your_key_here\n" .
                "to backend/.env, then re-run this seeder."
            );
            return;
        }

        $inserted = 0;
        $skipped  = 0;

        foreach (self::DATA_TYPES as $dataType) {
            $this->command->info("Fetching {$dataType} foods...");
            $page = 1;

            do {
                $response = Http::timeout(30)->get(self::BASE_URL . '/foods/list', [
                    'api_key'   => $apiKey,
                    'dataType'  => $dataType,
                    'pageSize'  => self::PAGE_SIZE,
                    'pageNumber' => $page,
                    'nutrients' => array_merge(
                        self::NUTRIENT_ENERGY_IDS,
                        [self::NUTRIENT_PROTEIN, self::NUTRIENT_FAT, self::NUTRIENT_CARBS]
                    ),
                ]);

                if ($response->failed()) {
                    $this->command->error("API error on page {$page}: " . $response->status());
                    break;
                }

                $foods = $response->json();

                if (empty($foods)) {
                    break; // no more pages
                }

                $rows = [];
                $now  = now()->toDateTimeString();

                foreach ($foods as $food) {
                    $nutrients = $this->indexNutrients($food['foodNutrients'] ?? []);

                    // Pick first matching energy nutrient (208 for SR Legacy, 957/958 for Foundation)
                    $energyValue = 0;
                    foreach (self::NUTRIENT_ENERGY_IDS as $eid) {
                        if (isset($nutrients[$eid]) && $nutrients[$eid] > 0) {
                            $energyValue = $nutrients[$eid];
                            break;
                        }
                    }
                    $caloriesPer100g = (int) round($energyValue);

                    // Skip foods with no calorie data — they're usually incomplete entries
                    if ($caloriesPer100g === 0) {
                        $skipped++;
                        continue;
                    }

                    $rows[] = [
                        'name'              => $this->cleanName($food['description'] ?? 'Unknown'),
                        'calories_per_100g' => $caloriesPer100g,
                        'protein'           => round($nutrients[self::NUTRIENT_PROTEIN] ?? 0, 2),
                        'carbs'             => round($nutrients[self::NUTRIENT_CARBS]   ?? 0, 2),
                        'fat'               => round($nutrients[self::NUTRIENT_FAT]     ?? 0, 2),
                        'source'            => 'usda',
                        'is_verified'       => 1,
                        'created_by'        => null,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }

                // Upsert: if the same name+source already exists, update nutrients
                if (! empty($rows)) {
                    DB::table('foods')->upsert(
                        $rows,
                        ['name', 'source'],
                        ['calories_per_100g', 'protein', 'carbs', 'fat', 'updated_at']
                    );
                    $inserted += count($rows);
                }

                $this->command->line("  Page {$page}: " . count($rows) . " foods processed (total so far: {$inserted})");

                $page++;

                // Be a good API citizen — avoid hammering the endpoint
                usleep(200_000); // 200 ms between pages

            } while (count($foods) === self::PAGE_SIZE);
        }

        $this->command->info("Done! {$inserted} foods upserted, {$skipped} skipped (no calorie data).");
    }

    /**
     * Re-index the foodNutrients array by nutrient number for O(1) lookup.
     * The /foods/list endpoint uses 'number' and 'amount' (not nutrientNumber/value).
     */
    private function indexNutrients(array $foodNutrients): array
    {
        $map = [];
        foreach ($foodNutrients as $n) {
            $number = $n['number'] ?? null;
            if ($number !== null) {
                $map[$number] = $n['amount'] ?? 0;
            }
        }
        return $map;
    }

    /**
     * USDA names are verbose: "Apples, raw, with skin" → keep as-is but trim
     * excess whitespace and truncate to 255 chars (column limit).
     */
    private function cleanName(string $name): string
    {
        return substr(trim($name), 0, 255);
    }
}
