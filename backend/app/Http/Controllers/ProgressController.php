<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $period = $request->query('period', 'week'); // 'week' or 'month'
        $days = ($period === 'month') ? 30 : 7;
        
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        // Get daily totals
        $history = FoodLog::where('user_id', $user->id)
            ->whereBetween('consumed_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(consumed_at) as date'),
                DB::raw('SUM(calories) as total_calories')
            )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // Fill in missing dates with zero
        $formattedHistory = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->toDateString();
            $logEntry = $history->firstWhere('date', $dateStr);
            
            $formattedHistory[] = [
                'date' => $dateStr,
                'label' => $currentDate->format('D'), // Mon, Tue, etc.
                'total_calories' => $logEntry ? (int) $logEntry->total_calories : 0,
                'target' => $user->calorie_target
            ];
            
            $currentDate->addDay();
        }

        // Calculate Streak
        $streak = $this->calculateStreak($user->id);

        return response()->json([
            'period' => $period,
            'history' => $formattedHistory,
            'streak' => $streak,
            'current_target' => $user->calorie_target
        ]);
    }

    private function calculateStreak($userId)
    {
        $dates = FoodLog::where('user_id', $userId)
            ->select(DB::raw('DATE(consumed_at) as date'))
            ->groupBy('date')
            ->orderBy('date', 'DESC')
            ->get()
            ->pluck('date')
            ->toArray();

        if (empty($dates)) return 0;

        $streak = 0;
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // If no log today or yesterday, streak is broken
        if (!in_array($today, $dates) && !in_array($yesterday, $dates)) {
            return 0;
        }

        $checkDate = in_array($today, $dates) ? now() : now()->subDay();
        
        foreach ($dates as $date) {
            if ($date === $checkDate->toDateString()) {
                $streak++;
                $checkDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}
