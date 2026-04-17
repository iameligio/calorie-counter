<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Food;

class FoodController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        
        $query = Food::visibleTo($request->user()->id);
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $foods = $query->take(20)->get();
        return response()->json(['data' => $foods]);
    }
    
    public function show(Food $food)
    {
        return response()->json($food);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'calories_per_100g' => 'required|integer|min:0',
            'protein' => 'nullable|numeric|min:0',
            'carbs' => 'nullable|numeric|min:0',
            'fat' => 'nullable|numeric|min:0',
        ]);
        
        $validated['source'] = 'user';
        $validated['is_verified'] = false;
        $validated['created_by'] = $request->user()->id;
        
        $food = Food::create($validated);
        
        return response()->json($food, 201);
    }
}
