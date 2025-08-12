<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutSessionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|string|max:255',
            'duration_minutes' => 'sometimes|integer|min:1|max:480',
            'calories_burned' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:in_progress,completed,paused'
        ];
    }
}
