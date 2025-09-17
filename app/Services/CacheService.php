<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache duration constants (in minutes)
     */
    const SHORT_CACHE = 5;      // 5 minutes
    const MEDIUM_CACHE = 30;    // 30 minutes
    const LONG_CACHE = 1440;    // 24 hours
    const EXTENDED_CACHE = 10080; // 7 days

    /**
     * Cache user statistics with smart invalidation
     */
    public function getUserStats(int $userId, \Closure $callback): array
    {
        $key = "user_stats_{$userId}";

        // Don't cache in CLI to avoid memory issues
        if (PHP_SAPI === 'cli') {
            return $callback();
        }

        return Cache::remember($key, self::MEDIUM_CACHE, function() use ($callback, $userId) {
            Log::debug('Cache miss: calculating user stats', ['user_id' => $userId]);
            return $callback();
        });
    }

    /**
     * Cache workout templates with category filtering
     */
    public function getWorkoutTemplates(int $userId, array $filters, \Closure $callback): array
    {
        $filterHash = md5(serialize($filters));
        $key = "workout_templates_{$userId}_{$filterHash}";

        if (PHP_SAPI === 'cli') {
            return $callback();
        }

        return Cache::remember($key, self::MEDIUM_CACHE, function() use ($callback) {
            Log::debug('Cache miss: fetching workout templates');
            return $callback();
        });
    }

    /**
     * Cache exercise list with search and filters
     */
    public function getExercises(array $filters, \Closure $callback): array
    {
        $filterHash = md5(serialize($filters));
        $key = "exercises_list_{$filterHash}";

        if (PHP_SAPI === 'cli') {
            return $callback();
        }

        return Cache::remember($key, self::LONG_CACHE, function() use ($callback) {
            Log::debug('Cache miss: fetching exercises');
            return $callback();
        });
    }

    /**
     * Cache dashboard data
     */
    public function getDashboardData(int $userId, \Closure $callback): array
    {
        $key = "dashboard_data_{$userId}";

        if (PHP_SAPI === 'cli') {
            return $callback();
        }

        return Cache::remember($key, self::SHORT_CACHE, function() use ($callback, $userId) {
            Log::debug('Cache miss: calculating dashboard data', ['user_id' => $userId]);
            return $callback();
        });
    }

    /**
     * Cache nutrition data by date
     */
    public function getNutritionData(int $userId, string $date, \Closure $callback): array
    {
        $key = "nutrition_data_{$userId}_{$date}";

        if (PHP_SAPI === 'cli') {
            return $callback();
        }

        return Cache::remember($key, self::MEDIUM_CACHE, function() use ($callback) {
            Log::debug('Cache miss: fetching nutrition data');
            return $callback();
        });
    }

    /**
     * Cache calendar tasks by date range
     */
    public function getCalendarTasks(int $userId, string $dateRange, \Closure $callback): array
    {
        $key = "calendar_tasks_{$userId}_{$dateRange}";

        if (PHP_SAPI === 'cli') {
            return $callback();
        }

        return Cache::remember($key, self::MEDIUM_CACHE, function() use ($callback) {
            Log::debug('Cache miss: fetching calendar tasks');
            return $callback();
        });
    }

    /**
     * Invalidate user-specific cache
     */
    public function invalidateUserCache(int $userId): void
    {
        try {
            $patterns = [
                "user_stats_{$userId}",
                "dashboard_data_{$userId}",
                "nutrition_data_{$userId}_*",
                "calendar_tasks_{$userId}_*",
                "workout_templates_{$userId}_*"
            ];

            foreach ($patterns as $pattern) {
                if (strpos($pattern, '*') !== false) {
                    $this->invalidateByPattern($pattern);
                } else {
                    Cache::forget($pattern);
                }
            }

            Log::info('User cache invalidated', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate user cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate exercise cache when exercises are updated
     */
    public function invalidateExerciseCache(): void
    {
        try {
            $this->invalidateByPattern('exercises_list_*');
            Log::info('Exercise cache invalidated');
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate exercise cache', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Invalidate workout template cache
     */
    public function invalidateWorkoutTemplateCache(?int $userId = null): void
    {
        try {
            if ($userId) {
                $this->invalidateByPattern("workout_templates_{$userId}_*");
            } else {
                $this->invalidateByPattern('workout_templates_*');
            }
            Log::info('Workout template cache invalidated', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate workout template cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $stats = [
                'hits' => 0,
                'misses' => 0,
                'memory_usage' => 0,
                'keys_count' => 0
            ];

            // For Redis cache
            if (config('cache.default') === 'redis') {
                $redis = Cache::getStore()->getRedis();
                $info = $redis->info('stats');

                $stats['hits'] = $info['keyspace_hits'] ?? 0;
                $stats['misses'] = $info['keyspace_misses'] ?? 0;
                $stats['memory_usage'] = $info['used_memory'] ?? 0;

                // Count keys with our app prefix
                $keys = $redis->keys(config('cache.prefix', 'laravel_cache') . ':*');
                $stats['keys_count'] = count($keys);
            }

            return $stats;
        } catch (\Exception $e) {
            Log::warning('Failed to get cache stats', ['error' => $e->getMessage()]);
            return [
                'hits' => 0,
                'misses' => 0,
                'memory_usage' => 0,
                'keys_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear all application cache
     */
    public function clearAllCache(): bool
    {
        try {
            Cache::flush();
            Log::info('All cache cleared');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear all cache', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Warm up critical cache entries
     */
    public function warmUpCache(): void
    {
        try {
            Log::info('Starting cache warm-up');

            // Warm up exercise cache
            $this->getExercises([], function() {
                return \App\Models\Exercise::select(['id', 'name', 'category', 'body_part', 'difficulty_level'])
                    ->where('is_public', true)
                    ->get()
                    ->toArray();
            });

            // Warm up public workout templates
            $this->getWorkoutTemplates(0, [], function() {
                return \App\Models\Workout::where('is_template', true)
                    ->where('is_public', true)
                    ->select(['id', 'name', 'category', 'difficulty_level', 'estimated_duration'])
                    ->withCount('exercises')
                    ->get()
                    ->toArray();
            });

            Log::info('Cache warm-up completed');
        } catch (\Exception $e) {
            Log::error('Cache warm-up failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Pattern-based cache invalidation (Redis only)
     */
    private function invalidateByPattern(string $pattern): void
    {
        if (config('cache.default') !== 'redis') {
            return;
        }

        try {
            $redis = Cache::getStore()->getRedis();
            $prefix = config('cache.prefix', 'laravel_cache');
            $fullPattern = "{$prefix}:{$pattern}";

            $keys = $redis->keys($fullPattern);

            if (!empty($keys)) {
                $redis->del($keys);
                Log::debug('Invalidated cache pattern', [
                    'pattern' => $pattern,
                    'keys_deleted' => count($keys)
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Pattern-based cache invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache key for debugging
     */
    public function getCacheKey(string $baseKey, ...$params): string
    {
        $key = $baseKey;
        foreach ($params as $param) {
            $key .= '_' . (is_array($param) ? md5(serialize($param)) : $param);
        }
        return $key;
    }

    /**
     * Check if caching is enabled
     */
    public function isCachingEnabled(): bool
    {
        return PHP_SAPI !== 'cli' && config('cache.default') !== 'array';
    }
}