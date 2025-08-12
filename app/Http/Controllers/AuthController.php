<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class AuthController extends BaseController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $result = $this->authService->registerUser($request->validated());
            
            return $this->createdResponse([
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'Bearer'
            ], 'User registered successfully');
        }, 'User registration', false);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $result = $this->authService->loginUser($request->validated());
            
            if (!$result) {
                return $this->errorResponse('Invalid credentials', 401);
            }
            
            return $this->successResponse([
                'user' => $result['user'],
                'token' => $result['token'],
                'token_type' => 'Bearer'
            ], 'Login successful');
        }, 'User login', false);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $request->user()->currentAccessToken()->delete();
            return $this->successResponse(null, 'Logout successful');
        }, 'User logout');
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $user = $request->user();
            
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            $user->refresh();
            $userData = $user->toArray();
            
            // Vérifier si la méthode existe avant de l'appeler
            if (method_exists($user, 'getComprehensiveStats')) {
                $userData['stats'] = $user->getComprehensiveStats();
            }

            return $this->successResponse($userData, 'User data retrieved');
        }, 'Get user profile');
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $user = $request->user();
            
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            $request->user()->currentAccessToken()->delete();
            $token = $user->createToken('FitnessPro')->plainTextToken;
            
            return $this->successResponse([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 'Token refreshed successfully');
        }, 'Token refresh');
    }

    /**
     * Revoke all tokens for the user
     */
    public function revokeAll(Request $request): JsonResponse
    {
        return $this->execute(function () use ($request) {
            $user = $request->user();
            
            if (!$user) {
                return $this->errorResponse('User not authenticated', 401);
            }

            $user->tokens()->delete();
            return $this->successResponse(null, 'All tokens revoked successfully');
        }, 'Revoke all tokens');
    }

    
}