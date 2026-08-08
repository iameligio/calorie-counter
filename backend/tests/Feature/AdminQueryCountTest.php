<?php

namespace Tests\Feature;

use App\Filament\Resources\FoodResource\Pages\ListFoods;
use App\Models\Food;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminQueryCountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The foods table renders a `creator.name` column. Filament batches that
     * relationship automatically today (one `where id in (...)` for the page),
     * which is why no explicit eager load is configured on the resource. This
     * pins that behaviour: if a Filament upgrade or a resource change ever
     * turns it into a per-row lookup, this fails instead of quietly costing a
     * query per row.
     */
    public function test_foods_list_loads_creators_in_one_batch_not_per_row(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        foreach (range(1, 15) as $i) {
            $creator = User::factory()->create();
            Food::create([
                'name' => "Custom Food {$i}",
                'calories_per_100g' => 100,
                'source' => 'user',
                'created_by' => $creator->id,
            ]);
        }

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(ListFoods::class)->assertOk();

        $userQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $q) => str_contains($q, 'from "users"'))
            ->values();

        DB::disableQueryLog();

        // 15 rows with 15 distinct creators. A per-row lookup would be ~15.
        $this->assertLessThanOrEqual(
            3,
            $userQueries->count(),
            'Creator lookups did not batch: '.$userQueries->implode(' ||| ')
        );
    }
}
