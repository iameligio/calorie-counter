<?php

namespace App\Http\Controllers;

use App\Models\FoodLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProgressController extends Controller
{
    /**
     * How far back the streak scan reaches. A streak longer than this reports
     * as this value — the alternative is an unbounded scan of every log a
     * user has ever written, on every dashboard load.
     */
    private const MAX_STREAK_LOOKBACK_DAYS = 366;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', Rule::in(['week', 'month'])],
        ]);

        $user = $request->user();
        $period = $validated['period'] ?? 'week';
        $days = $period === 'month' ? 30 : 7;

        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

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
                'target' => $user->calorie_target,
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'period' => $period,
            'history' => $formattedHistory,
            'streak' => $this->calculateStreak($user->id),
            'current_target' => $user->calorie_target,
        ]);
    }

    private function calculateStreak($userId)
    {
        $dates = FoodLog::where('user_id', $userId)
            ->where('consumed_at', '>=', now()->subDays(self::MAX_STREAK_LOOKBACK_DAYS)->startOfDay())
            ->select(DB::raw('DATE(consumed_at) as date'))
            ->groupBy('date')
            ->orderBy('date', 'DESC')
            ->limit(self::MAX_STREAK_LOOKBACK_DAYS)
            ->pluck('date')
            ->toArray();

        if (empty($dates)) {
            return 0;
        }

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // If no log today or yesterday, streak is broken
        if (! in_array($today, $dates) && ! in_array($yesterday, $dates)) {
            return 0;
        }

        $streak = 0;
        $checkDate = in_array($today, $dates) ? now() : now()->subDay();

        foreach ($dates as $date) {
            if ($date !== $checkDate->toDateString()) {
                break;
            }

            $streak++;
            $checkDate->subDay();
        }

        return $streak;
    }
}
