<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMealEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'food_id' => 'nullable|string|max:255',
            'name' => 'sometimes|required|string|max:255',
            'quantity' => 'sometimes|required|numeric|min:0.1',
            'meal_type' => 'sometimes|required|string|in:breakfast,lunch,dinner,snack',
            'date' => 'sometimes|required|date',
            'calories' => 'sometimes|required|numeric|min:0',
            'protein' => 'sometimes|required|numeric|min:0',
            'carbs' => 'sometimes|required|numeric|min:0',
            'fat' => 'sometimes|required|numeric|min:0',
            'fiber' => 'nullable|numeric|min:0',
            'sodium' => 'nullable|numeric|min:0',
            'potassium' => 'nullable|numeric|min:0',
            'vitamin_c' => 'nullable|numeric|min:0',
        ];
    }
}
