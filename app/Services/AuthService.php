<?php
// app/Services/AuthService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function registerUser(array $data): array
    {
        $userData = [
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
        ];

        $user = User::create($userData);
        $token = $user->createToken('FitnessPro')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function loginUser(array $credentials): ?array
    {
        $user = User::where('email', strtolower($credentials['email']))->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        if (!($credentials['rememberMe'] ?? false)) {
            $user->tokens()->delete();
        }

        $token = $user->createToken('FitnessPro')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
