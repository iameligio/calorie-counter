<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodLog;
use App\Models\Food;

class FoodLogController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        
        $query = FoodLog::with('food')->where('user_id', $request->user()->id);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('consumed_at', [
                $validated['start_date'] . ' 00:00:00',
                $validated['end_date'] . ' 23:59:59'
            ]);
        } else {
            $date = $validated['date'] ?? today()->toDateString();
            $query->whereDate('consumed_at', $date);
        }
        
        $logs = $query->orderBy('consumed_at', 'desc')->get();
        
        $totalCalories = $logs->sum('calories');
        // Calculate days in range for target aggregation
        $daysCount = 1;
        if ($request->has('start_date') && $request->has('end_date')) {
            $daysCount = \Carbon\Carbon::parse($validated['start_date'])->diffInDays(\Carbon\Carbon::parse($validated['end_date'])) + 1;
        }

        return response()->json([
            'logs' => $logs,
            'summary' => [
                'total_calories' => (int) $totalCalories,
                'total_target' => (int) ($request->user()->calorie_target * $daysCount),
                'days_count' => $daysCount
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'food_id' => 'required|exists:foods,id',
            'grams' => 'required|integer|min:1',
            'consumed_at' => 'nullable|date|before_or_equal:today',
        ]);
        
        $food = Food::findOrFail($validated['food_id']);
        
        if ($food->source === 'user' && $food->created_by !== $request->user()->id) {
            abort(403, 'Unauthorized action. This custom food does not belong to you.');
        }
        
        $calories = (int) round(($validated['grams'] / 100) * $food->calories_per_100g);
        
        $log = $request->user()->logs()->create([
            'food_id' => $food->id,
            'grams' => $validated['grams'],
            'calories' => $calories,
            'consumed_at' => $validated['consumed_at'] ?? now(),
        ]);
        
        return response()->json($log->load('food'), 201);
    }

    public function destroy(Request $request, $id)
    {
        $log = $request->user()->logs()->findOrFail($id);
        
        // Prevent deleting logs older than 7 days
        if ($log->consumed_at->lt(now()->subDays(7)->startOfDay())) {
            return response()->json(['message' => 'Logs older than a week cannot be deleted.'], 403);
        }

        $log->delete();
        return response()->noContent();
    }
}
