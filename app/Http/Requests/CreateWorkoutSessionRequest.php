<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkoutSessionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'workout_plan_id' => 'nullable|exists:workout_plans,id',
            'title' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'calories_burned' => 'nullable|integer|min:0',
            'completed_exercises' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:in_progress,completed,paused',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ];
    }
}
