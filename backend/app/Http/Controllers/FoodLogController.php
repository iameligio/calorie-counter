<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\FoodLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FoodLogController extends Controller
{
    /** A log stops being deletable once it is this many days old. */
    private const DELETABLE_DAYS = 7;

    /** Upper bound on a single history query, so one request can't read every row. */
    private const MAX_RANGE_DAYS = 366;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'start_date' => 'nullable|date|required_with:end_date',
            'end_date' => [
                'nullable',
                'date',
                'required_with:start_date',
                'after_or_equal:start_date',
                // Without a ceiling, ?start_date=1900-01-01 loads a user's
                // entire history into memory in one unpaginated response.
                'before_or_equal:'.$this->rangeCeiling($request),
            ],
        ], [
            'end_date.before_or_equal' => 'The date range may not exceed '.self::MAX_RANGE_DAYS.' days.',
        ]);

        $query = FoodLog::with('food')->where('user_id', $request->user()->id);

        $hasRange = filled($validated['start_date'] ?? null) && filled($validated['end_date'] ?? null);

        if ($hasRange) {
            $start = Carbon::parse($validated['start_date'])->startOfDay();
            $end = Carbon::parse($validated['end_date'])->endOfDay();
            $query->whereBetween('consumed_at', [$start, $end]);

            // Carbon 3 returns a float here, and $end sits at 23:59:59, so
            // diffing against it yields 0.9999… for a same-day range. Compare
            // midnight to midnight and count inclusively.
            $daysCount = (int) round($start->diffInDays($end->copy()->startOfDay())) + 1;
        } else {
            $query->whereDate('consumed_at', $validated['date'] ?? today()->toDateString());
            $daysCount = 1;
        }

        $logs = $query->orderBy('consumed_at', 'desc')->get();

        return response()->json([
            'logs' => $logs,
            'summary' => [
                'total_calories' => (int) $logs->sum('calories'),
                'total_target' => (int) ($request->user()->calorie_target * $daysCount),
                'days_count' => (int) $daysCount,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'food_id' => 'required|integer',
            'grams' => 'required|integer|min:1|max:10000',
            'consumed_at' => [
                'nullable',
                'date',
                'before_or_equal:today',
                // Anything older than the deletion window would be a row the
                // owner could never remove.
                'after_or_equal:'.now()->subDays(self::DELETABLE_DAYS)->toDateString(),
            ],
        ]);

        // Fetched once. Validating with `exists` and then re-fetching would run
        // the same lookup twice.
        $food = Food::find($validated['food_id']);

        if (! $food) {
            throw ValidationException::withMessages([
                'food_id' => ['The selected food is invalid.'],
            ]);
        }

        if ($food->source === 'user' && $food->created_by !== $request->user()->id) {
            abort(403, 'Unauthorized action. This custom food does not belong to you.');
        }

        $log = $request->user()->logs()->create([
            'food_id' => $food->id,
            'grams' => $validated['grams'],
            'calories' => (int) round(($validated['grams'] / 100) * $food->calories_per_100g),
            'consumed_at' => $validated['consumed_at'] ?? now(),
        ]);

        return response()->json($log->load('food'), 201);
    }

    public function destroy(Request $request, $id)
    {
        $log = $request->user()->logs()->findOrFail($id);

        if ($log->consumed_at->lt(now()->subDays(self::DELETABLE_DAYS)->startOfDay())) {
            return response()->json(['message' => 'Logs older than a week cannot be deleted.'], 403);
        }

        $log->delete();

        return response()->noContent();
    }

    /**
     * Latest end_date permitted for the requested start_date.
     */
    private function rangeCeiling(Request $request): string
    {
        $start = $request->input('start_date');

        if (! $start) {
            return today()->addDay()->toDateString();
        }

        try {
            return Carbon::parse($start)->addDays(self::MAX_RANGE_DAYS - 1)->toDateString();
        } catch (\Exception) {
            // start_date is unparseable; its own `date` rule reports the error.
            return today()->addDay()->toDateString();
        }
    }
}
