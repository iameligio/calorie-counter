<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'age' => 'nullable|integer|min:1|max:120',
            'weight_kg' => 'nullable|numeric|min:20|max:500',
            'target_weight_kg' => 'nullable|numeric|min:20|max:500',
            'height_cm' => 'nullable|numeric|min:50|max:250',
            'activity_level' => ['nullable', 'string', Rule::in(['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extra_active'])],
            'goal' => ['nullable', 'string', Rule::in(['lose', 'maintain', 'gain'])],
        ]);

        $user->fill($validated);

        // Recalculate calorie target if all required fields are present
        if ($user->gender && $user->age && $user->weight_kg && $user->height_cm && $user->activity_level) {
            $user->calorie_target = $this->calculateTarget($user);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    private function calculateTarget($user)
    {
        // Mifflin-St Jeor Equation
        if ($user->gender === 'male') {
            $bmr = (10 * $user->weight_kg) + (6.25 * $user->height_cm) - (5 * $user->age) + 5;
        } else {
            // Female or other (using female as safer baseline for 'other' in calorie math, or can be refined)
            $bmr = (10 * $user->weight_kg) + (6.25 * $user->height_cm) - (5 * $user->age) - 161;
        }

        $multipliers = [
            'sedentary' => 1.2,
            'lightly_active' => 1.375,
            'moderately_active' => 1.55,
            'very_active' => 1.725,
            'extra_active' => 1.9,
        ];

        $tdee = $bmr * ($multipliers[$user->activity_level] ?? 1.2);

        // Adjust for goal
        if ($user->goal === 'lose') {
            $tdee -= 500;
        } elseif ($user->goal === 'gain') {
            $tdee += 500;
        }

        // Safety minimum (1200 for women, 1500 for men usually, but let's keep it simple)
        return (int) max(1200, round($tdee));
    }
}
