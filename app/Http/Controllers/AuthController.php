<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;

class AuthController extends BaseController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();
            $authResult = $this->authService->loginUser($credentials);

            if (!$authResult) {
                return $this->errorResponse('Invalid credentials', 401);
            }

            $response = [
                'user' => $authResult['user'],
                'token' => $authResult['token'],
                'token_type' => 'Bearer'
            ];

            if (isset($authResult['refresh_token'])) {
                $response['refresh_token'] = $authResult['refresh_token'];
            }

            return $this->successResponse($response, 'Login successful');

        } catch (\Exception $e) {
            Log::error('Login failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Login failed',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Register user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $result = $this->authService->registerUser($request->validated());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                    'token_type' => 'Bearer'
                ],
                'message' => 'User registered successfully'
            ], 201);
        }, 'User registration', false);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            Log::info('AuthController: Logout attempt', [
                'user_id' => $request->user()->id ?? 'unknown'
            ]);

            $this->authService->logoutUser($request->user());

            Log::info('AuthController: Logout successful');

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);
        } catch (\Exception $e) {
            Log::error('AuthController: Logout failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                Log::warning('AuthController: No authenticated user found');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            Log::info('AuthController: User data retrieved', [
                'user_id' => $user->id
            ]);

            $user->refresh();
            $userData = $user->toArray();

            return response()->json([
                'success' => true,
                'data' => $userData,
                'message' => 'User data retrieved'
            ]);
        } catch (\Exception $e) {
            Log::error('AuthController: me() failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user data'
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $refreshToken = $request->input('refresh_token');

            if (!$user || !$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated or refresh token not provided'
                ], 401);
            }

            $tokens = $this->authService->refreshToken($user, $refreshToken);
            if (!$tokens) {
                return $this->errorResponse('Failed to refresh token', 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'token' => $tokens['token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'token_type' => 'Bearer'
                ],
                'message' => 'Token refreshed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('AuthController: Token refresh failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed'
            ], 500);
        }
    }

    /**
     * Revoke all tokens for the user
     */
    public function revokeAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $this->authService->revokeAllTokens($user);
            
            return response()->json([
                'success' => true,
                'message' => 'All tokens revoked successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('AuthController: Token revocation failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token revocation failed'
            ], 500);
        }
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->sendPasswordResetLink($request->validated());
            return $this->successResponse(null, 'Password reset link sent successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to send password reset link: ' . $e->getMessage(),
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Reset the given user's password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->resetUserPassword($request->validated());
            return $this->successResponse(null, 'Password has been reset successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to reset password: ' . $e->getMessage(),
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Direct password reset without token (for UI-based reset).
     */
    public function directResetPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|confirmed|min:8',
            ]);

            $this->authService->directResetPassword($validated['email'], $validated['password']);
            return $this->successResponse(null, 'Password has been reset successfully.');
        } catch (\Exception $e) {
            Log::error('Direct password reset failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to reset password: ' . $e->getMessage(),
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }
}