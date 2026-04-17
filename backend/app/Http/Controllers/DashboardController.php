<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $date = Carbon::parse($request->query('date', today()->toDateString()))->startOfDay();
        
        $totalCalories = FoodLog::where('user_id', $user->id)
            ->whereDate('consumed_at', $date)
            ->sum('calories');
            
        return response()->json([
            'date' => $date->toDateString(),
            'total_calories' => (int) $totalCalories,
            'calorie_target' => $user->calorie_target,
            'remaining_calories' => max(0, $user->calorie_target - $totalCalories)
        ]);
    }
}
