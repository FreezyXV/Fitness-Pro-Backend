<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Register a new user with comprehensive error handling
     */
    public function registerUser(array $data): array
    {
        // try {
            Log::info('AuthService: Starting user registration', [
                'email' => $data['email'] ?? 'unknown'
            ]);

            // DB::beginTransaction();

            // try {
                // Create user with safe data
                $userData = [
                    'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                    'first_name' => strtolower(trim($data['first_name'])),
                    'last_name' => strtolower(trim($data['last_name'])),
                    'email' => strtolower(trim($data['email'])),
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(), // Auto-verify for development
                ];

                Log::info('AuthService: Creating user with data', [
                    'email' => $userData['email'],
                    'name' => $userData['name']
                ]);

                $user = User::create($userData);

                if (!$user) {
                    throw new \Exception('Failed to create user');
                }

                Log::info('AuthService: User created successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                // Create token
                // $tokenName = 'FitnessPro_' . now()->timestamp;
                // $token = $user->createToken($tokenName)->plainTextToken;

                // if (!$token) {
                //     throw new \Exception('Failed to create authentication token');
                // }

                // DB::commit();

                Log::info('AuthService: Registration completed successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                // Return fresh user data
                return [
                    'user' => $user->fresh(),
                    'token' => 'dummy-token' // $token
                ];

            // } catch (\Exception $e) {
            //     DB::rollBack();
            //     throw $e;
            // }

        // } catch (ValidationException $e) {
        //     Log::warning('AuthService: Registration validation failed', [
        //         'email' => $data['email'] ?? 'unknown',
        //         'errors' => $e->errors()
        //     ]);
        //     throw $e;

        // } catch (\Illuminate\Database\QueryException $e) {
        //     Log::error('AuthService: Database error during registration', [
        //         'email' => $data['email'] ?? 'unknown',
        //         'error' => $e->getMessage(),
        //         'code' => $e->getCode(),
        //         'sql' => $e->getSql() ?? 'N/A',
        //         'bindings' => $e->getBindings() ?? []
        //     ]);

        //     // Handle specific database errors
        //     if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'unique')) {
        //         throw new \Exception('This email address is already registered.');
        //     }

        //     // Re-throw with more detailed error message in development
        //     if (app()->environment('local', 'development')) {
        //         throw new \Exception('Database error: ' . $e->getMessage());
        //     }

        //     throw new \Exception('Database error occurred. Please try again later.');

        // } catch (\Exception $e) {
        //     Log::error('AuthService: Registration failed', [
        //         'email' => $data['email'] ?? 'unknown',
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString()
        //     ]);

        //     // Re-throw with user-friendly message
        //     throw new \Exception('Registration failed: ' . $e->getMessage());
        // }
    }

    /**
     * Login user with improved error handling
     */
    public function loginUser(array $credentials): ?array
    {
        try {
            Log::info('AuthService: Login attempt', [
                'email' => $credentials['email'] ?? 'unknown'
            ]);

            $email = strtolower(trim($credentials['email']));
            $password = $credentials['password'];
            $rememberMe = $credentials['rememberMe'] ?? false;

            // Find user
            $user = User::where('email', $email)->first();

            if (!$user || !Hash::check($password, $user->password)) {
                Log::warning('AuthService: Invalid credentials', [
                    'email' => $email
                ]);
                return null;
            }

            // Revoke all previous tokens
            $user->tokens()->delete();

            $tokens = $this->generateTokens($user, $rememberMe);

            Log::info('AuthService: Login successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return array_merge(['user' => $user->fresh()], $tokens);

        } catch (\Exception $e) {
            Log::error('AuthService: Login failed', [
                'email' => $credentials['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    private function generateTokens(User $user, bool $rememberMe = false): array
    {
        $tokenName = 'FitnessPro_' . now()->timestamp;

        // Ensure config values are integers
        $expirationMinutes = (int) config('sanctum.expiration', 1440);
        $accessToken = $user->createToken($tokenName, ['*'], now()->addMinutes($expirationMinutes))->plainTextToken;

        $result = [
            'token' => $accessToken,
        ];

        if ($rememberMe) {
            $refreshExpirationMinutes = (int) config('sanctum.refresh_expiration', 10080);
            $refreshToken = $user->createToken('refresh-token', ['refresh'], now()->addMinutes($refreshExpirationMinutes))->plainTextToken;
            $result['refresh_token'] = $refreshToken;
        }

        return $result;
    }

    /**
     * Logout user
     */
    public function logoutUser(User $user): bool
    {
        try {
            Log::info('AuthService: Logout attempt', [
                'user_id' => $user->id
            ]);

            // Delete current token (if it exists)
            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            Log::info('AuthService: Logout successful', [
                'user_id' => $user->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('AuthService: Logout failed', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Revoke all tokens for user
     */
    public function revokeAllTokens(User $user): bool
    {
        try {
            Log::info('AuthService: Revoking all tokens', [
                'user_id' => $user->id
            ]);

            $user->tokens()->delete();

            Log::info('AuthService: All tokens revoked', [
                'user_id' => $user->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('AuthService: Token revocation failed', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Refresh token for the user
     */
    public function refreshToken(User $user, string $refreshToken): ?array
    {
        try {
            Log::info('AuthService: Refreshing token', [
                'user_id' => $user->id
            ]);

            $token = DB::table('personal_access_tokens')->where('token', hash('sha256', $refreshToken))->first();

            if (!$token || $token->tokenable_id != $user->id) {
                Log::warning('AuthService: Invalid refresh token', [
                    'user_id' => $user->id
                ]);
                return null;
            }

            // Delete the old refresh token
            DB::table('personal_access_tokens')->where('id', $token->id)->delete();

            $tokens = $this->generateTokens($user, true);

            Log::info('AuthService: Token refreshed successfully', [
                'user_id' => $user->id
            ]);

            return $tokens;
        } catch (\Exception $e) {
            Log::error('AuthService: Token refresh failed', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Send a password reset link to the given user.
     */
    public function sendPasswordResetLink(array $data): string
    {
        Log::info('AuthService: Sending password reset link', [
            'email' => $data['email']
        ]);
        
        $response = Password::sendResetLink(
            $data
        );
        
        if ($response !== Password::RESET_LINK_SENT) {
            Log::error('AuthService: Failed to send password reset link', [
                'email' => $data['email'],
                'response' => $response
            ]);
            throw new \Exception('Failed to send password reset link.');
        }
        
        Log::info('AuthService: Password reset link sent successfully', [
            'email' => $data['email']
        ]);
        
        return $response;
    }
    
    /**
     * Reset the given user's password.
     */
    public function resetUserPassword(array $data): string
    {
        Log::info('AuthService: Resetting user password', [
            'email' => $data['email']
        ]);

        $response = Password::reset(
            $data,
            function ($user, $password) {
                if (!$user) {
                    Log::error('AuthService: User is null in password reset callback');
                    throw new \Exception('User not found');
                }

                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($response !== Password::PASSWORD_RESET) {
            Log::error('AuthService: Failed to reset password', [
                'email' => $data['email'],
                'response' => $response
            ]);

            // More descriptive error messages
            $errorMessage = match($response) {
                Password::INVALID_USER => 'Aucun utilisateur trouvé avec cet email.',
                Password::INVALID_TOKEN => 'Le lien de réinitialisation est invalide ou a expiré.',
                Password::RESET_THROTTLED => 'Trop de tentatives. Veuillez réessayer plus tard.',
                default => 'Échec de la réinitialisation du mot de passe.'
            };

            throw new \Exception($errorMessage);
        }

        Log::info('AuthService: Password reset successfully', [
            'email' => $data['email']
        ]);

        return $response;
    }

    /**
     * Direct password reset without token (for UI-based reset).
     */
    public function directResetPassword(string $email, string $password): bool
    {
        Log::info('AuthService: Direct password reset', [
            'email' => $email
        ]);

        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                throw new \Exception('User not found');
            }

            $user->password = Hash::make($password);
            $user->setRememberToken(Str::random(60));
            $user->save();

            Log::info('AuthService: Direct password reset successful', [
                'email' => $email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('AuthService: Direct password reset failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}