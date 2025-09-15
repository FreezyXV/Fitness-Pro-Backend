<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetNutritionGoalsRequest extends FormRequest
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
            'calories' => 'required|numeric|min:0',
            'protein' => 'required|numeric|min:0',
            'carbs' => 'required|numeric|min:0',
            'fat' => 'required|numeric|min:0',
            'water' => 'required|numeric|min:0',
            'fiber' => 'nullable|numeric|min:0',
            'sodium' => 'nullable|numeric|min:0',
            'potassium' => 'nullable|numeric|min:0',
            'vitaminC' => 'nullable|numeric|min:0',
        ];
    }
}
