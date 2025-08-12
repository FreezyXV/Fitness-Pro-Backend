<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkoutTemplateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|integer|exists:exercises,id',
            'exercises.*.sets' => 'integer|min:1|max:10',
            'exercises.*.reps' => 'nullable|integer|min:1|max:100',
            'exercises.*.duration_seconds' => 'nullable|integer|min:1|max:3600',
            'exercises.*.target_weight' => 'nullable|numeric|min:0|max:1000',
            'exercises.*.rest_time_seconds' => 'nullable|integer|min:0|max:600',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'category' => 'nullable|in:strength,cardio,flexibility,hiit',
        ];
    }
}
