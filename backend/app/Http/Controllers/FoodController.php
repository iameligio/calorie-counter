<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    private const RESULT_LIMIT = 20;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $query = Food::visibleTo($request->user()->id);

        if ($search = $validated['search'] ?? null) {
            // Leading wildcard can't use the name index — the `reads` limiter
            // is what keeps this from being a cheap way to burn CPU.
            $query->where('name', 'like', '%'.$search.'%');
        }

        // Explicit ordering: without it the 20 rows returned are whatever the
        // storage engine hands back, which differs between engines and runs.
        $foods = $query->orderBy('name')->take(self::RESULT_LIMIT)->get();

        return response()->json(['data' => $foods]);
    }

    public function show(Request $request, Food $food)
    {
        if ($food->source === 'user' && $food->created_by !== $request->user()->id) {
            abort(403, 'Unauthorized action. This food does not belong to you.');
        }

        return response()->json($food);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'calories_per_100g' => 'required|integer|min:0|max:1000',
            'protein' => 'nullable|numeric|min:0|max:100',
            'carbs' => 'nullable|numeric|min:0|max:100',
            'fat' => 'nullable|numeric|min:0|max:100',
        ]);

        $food = Food::create([
            ...$validated,
            'protein' => $validated['protein'] ?? 0,
            'carbs' => $validated['carbs'] ?? 0,
            'fat' => $validated['fat'] ?? 0,
            'source' => 'user',
            'is_verified' => false,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($food, 201);
    }
}
