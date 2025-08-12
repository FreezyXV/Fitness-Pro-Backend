<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'first_name' => 'required|string|min:2|max:255',
            'last_name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
            ],
            'acceptTerms' => 'required|accepted'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'first_name.min' => 'Le prénom doit contenir au moins 2 caractères.',
            'last_name.required' => 'Le nom est obligatoire.',
            'last_name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'acceptTerms.accepted' => 'Vous devez accepter les conditions d\'utilisation.'
        ];
    }
}
