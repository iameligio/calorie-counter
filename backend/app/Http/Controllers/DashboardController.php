<?php

namespace App\Http\Controllers;

use App\Models\FoodLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Validated rather than parsed straight from the query string —
        // Carbon::parse on arbitrary input throws and surfaces as a 500.
        $validated = $request->validate([
            'date' => 'nullable|date',
        ]);

        $user = $request->user();
        $date = Carbon::parse($validated['date'] ?? today()->toDateString())->startOfDay();

        $totalCalories = (int) FoodLog::where('user_id', $user->id)
            ->whereDate('consumed_at', $date)
            ->sum('calories');

        return response()->json([
            'date' => $date->toDateString(),
            'total_calories' => $totalCalories,
            'calorie_target' => $user->calorie_target,
            'remaining_calories' => max(0, $user->calorie_target - $totalCalories),
        ]);
    }
}
