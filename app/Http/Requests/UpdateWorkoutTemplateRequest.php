<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutTemplateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'exercises' => 'sometimes|array|min:1',
            'exercises.*.exercise_id' => 'sometimes|required|integer|exists:exercises,id',
            'difficulty_level' => 'sometimes|in:beginner,intermediate,advanced',
            'category' => 'nullable|in:strength,cardio,flexibility,hiit',
        ];
    }
}
