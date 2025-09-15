<?php
// app/Http/Controllers/BaseController.php - OPTIMIZED VERSION WITH ENHANCED ERROR HANDLING
namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class BaseController extends Controller
{
    use AuthorizesRequests, ApiResponseTrait;

    /**
     * Get current authenticated user ID (optimized)
     */
    protected function getUserId(): ?int
    {
        return auth('sanctum')->id();
    }

    /**
     * Get current authenticated user
     */
    protected function getAuthenticatedUser()
    {
        return auth('sanctum')->user();
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return auth('sanctum')->check();
    }



    /**
     * Require authentication with standardized response
     */
    protected function requireAuth()
    {
        if (!auth('sanctum')->check()) {
            return $this->unauthorizedResponse('Authentication required');
        }
        return null;
    }

    /**
     * Standardized execution wrapper with exception handling
     */
    protected function execute(\Closure $action, string $context, bool $requireAuth = true)
    {
        try {
            if ($requireAuth) {
                $authCheck = $this->requireAuth();
                if ($authCheck) {
                    return $authCheck;
                }
            }
            
            return $action();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Resource not found');
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return $this->unauthorizedResponse('Authentication required');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->forbiddenResponse('Access denied');
        } catch (\Exception $e) {
            return $this->handleException($e, $context);
        }
    }

    /**
     * Enhanced exception handling with better logging and user-friendly messages
     */
    protected function handleException(\Exception $e, string $context = 'Operation'): \Illuminate\Http\JsonResponse
    {
        $errorId = uniqid('error_');
        $userId = $this->getUserId();
        $requestId = request()->header('X-Request-ID', uniqid('req_'));
        
        // Determine error type and appropriate response
        $statusCode = 500;
        $userMessage = "{$context} failed. Please try again later.";
        
        if ($e instanceof \Illuminate\Database\QueryException) {
            $statusCode = 503;
            $userMessage = "Database temporarily unavailable. Please try again.";
        } elseif ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            $statusCode = 503;
            $userMessage = "Service temporarily unavailable. Please try again.";
        } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $statusCode = $e->getStatusCode();
            $userMessage = $e->getMessage();
        }
        
        // Log error with comprehensive context
        Log::error("{$context} failed", [
            'error_id' => $errorId,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => $userId,
            'request_id' => $requestId,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);

        // Prepare response data
        $responseData = [
            'error_id' => $errorId,
            'timestamp' => now()->toISOString(),
        ];

        if (config('app.debug')) {
            $responseData['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'context' => $context
            ];
        }

        return $this->errorResponse($userMessage, $statusCode, $responseData);
    }

    /**
     * Check if user owns resource (optimized)
     */
    protected function userOwnsResource($resource, string $userIdField = 'user_id'): bool
    {
        if (!$resource || !$this->isAuthenticated()) {
            return false;
        }

        return $resource->{$userIdField} === $this->getUserId();
    }

    /**
     * Optimized pagination helper
     */
    protected function paginate($query, Request $request, int $defaultLimit = 20, int $maxLimit = 100)
    {
        $limit = min((int) $request->get('limit', $request->get('per_page', $defaultLimit)), $maxLimit);
        
        // Add performance optimization for large datasets
        if ($request->has('cursor')) {
            return $query->cursorPaginate($limit);
        }
        
        return $query->paginate($limit);
    }

    /**
     * Apply common filters to query
     */
    protected function applyFilters($query, Request $request, array $allowedFilters = []): void
    {
        foreach ($allowedFilters as $filter => $column) {
            if ($request->has($filter) && $request->get($filter) !== null) {
                $value = $request->get($filter);
                
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting($query, Request $request, array $allowedSorts = ['created_at'], string $defaultSort = 'created_at'): void
    {
        $sortBy = $request->get('sort_by', $request->get('sortBy', $defaultSort));
        $direction = $request->get('sort_direction', $request->get('sortDirection', 'desc'));
        
        // Validate direction
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'desc';
        
        // Validate sort field
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->orderBy($defaultSort, 'desc');
        }
    }

    /**
     * Validate resource ownership
     */
    protected function validateOwnership($resource, string $resourceName = 'Resource'): ?\Illuminate\Http\JsonResponse
    {
        if (!$resource) {
            return $this->notFoundResponse($resourceName);
        }

        if (!$this->userOwnsResource($resource)) {
            return $this->forbiddenResponse("Access denied to this {$resourceName}");
        }

        return null;
    }

    /**
     * Cache helper for controller methods with memory optimization
     */
    protected function cacheResponse(string $key, \Closure $callback, int $ttl = 600)
    {
        // Don't cache in CLI mode to avoid memory issues
        if (PHP_SAPI === 'cli') {
            return $callback();
        }

        return cache()->remember($key, $ttl, $callback);
    }

    /**
     * Clear user-specific cache
     */
    protected function clearUserCache(?string $pattern = null): void
    {
        $userId = $this->getUserId();
        if (!$userId) return;

        $pattern = $pattern ?? "user_{$userId}_*";
        
        // This would require Redis or a cache implementation that supports pattern deletion
        // For file cache, you might need a different approach
        if (config('cache.default') === 'redis') {
            cache()->getStore()->getRedis()->eval(
                "return redis.call('del', unpack(redis.call('keys', ARGV[1])))",
                0,
                $pattern
            );
        }
    }

    /**
     * Rate limiting helper
     */
    protected function checkRateLimit(string $key, int $maxAttempts = 60, int $decayMinutes = 1): ?\Illuminate\Http\JsonResponse
    {
        $rateLimiter = app(\Illuminate\Cache\RateLimiter::class);
        
        if ($rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $rateLimiter->availableIn($key);
            
            return $this->errorResponse(
                'Too many requests. Please try again later.',
                429,
                ['retry_after' => $seconds]
            );
        }
        
        $rateLimiter->hit($key, $decayMinutes * 60);
        
        return null;
    }

    /**
     * Check if the application is in a healthy state
     */
    protected function checkApplicationHealth(): bool
    {
        try {
            // Check database connection
            \DB::connection()->getPdo();
            
            // Check cache
            cache()->put('health_check', true, 1);
            $cacheWorking = cache()->get('health_check', false);
            
            return $cacheWorking === true;
        } catch (\Exception $e) {
            Log::warning('Application health check failed', [
                'error' => $e->getMessage(),
                'user_id' => $this->getUserId()
            ]);
            return false;
        }
    }

    /**
     * Get application status information
     */
    protected function getApplicationStatus(): array
    {
        try {
            $dbStatus = 'connected';
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        try {
            cache()->put('status_check', true, 1);
            $cacheStatus = cache()->get('status_check', false) ? 'working' : 'error';
        } catch (\Exception $e) {
            $cacheStatus = 'error: ' . $e->getMessage();
        }

        return [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'database' => $dbStatus,
            'cache' => $cacheStatus,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Bulk operation helper
     */
    protected function processBulkOperation(array $items, \Closure $operation, int $batchSize = 100): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        $batches = array_chunk($items, $batchSize);
        
        foreach ($batches as $batch) {
            foreach ($batch as $item) {
                try {
                    $operation($item);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'item' => $item,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        
        return $results;
    }
}