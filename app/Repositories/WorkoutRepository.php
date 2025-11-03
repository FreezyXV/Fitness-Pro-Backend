<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Workout;
use App\Repositories\Contracts\WorkoutRepositoryInterface;
use Illuminate\Support\Collection;
use App\Support\SystemUserResolver;

class WorkoutRepository extends BaseRepository implements WorkoutRepositoryInterface
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Workout::class;
    }

    /**
     * Get workout templates for a user (both owned and public)
     */
    public function getTemplatesForUser(User $user, array $filters = []): Collection
    {
        $systemUserIds = SystemUserResolver::ids();

        $query = $this->newQuery()
            ->where(function($q) {
                // Include templates (true) and seeded workouts (null)
                $q->where('is_template', true)->orWhereNull('is_template');
            })
            ->where(function($q) use ($user, $systemUserIds) {
                // Show user's own templates OR public templates
                $q->where('user_id', $user->id)
                  ->orWhereIn('user_id', $systemUserIds);
            })
            ->select([
                'id', 'name', 'description', 'type', 'category', 'difficulty', 'difficulty_level',
                'estimated_duration', 'estimated_calories', 'actual_duration', 'actual_calories',
                'user_id', 'is_template', 'created_at', 'updated_at'
            ])
            ->with(['user:id,name,first_name'])
            ->withCount('exercises');

        return $this->applyFilters($query, $filters)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get workout sessions for a user
     */
    public function getSessionsForUser(User $user, array $filters = []): Collection
    {
        $query = $this->newQuery()
            ->where('user_id', $user->id)
            ->where('is_template', false)
            ->with(['user:id,name,first_name'])
            ->withCount('exercises');

        return $this->applyFilters($query, $filters)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get public templates (seeded workouts)
     */
    public function getPublicTemplates(array $filters = []): Collection
    {
        $systemUserIds = SystemUserResolver::ids();

        $query = $this->newQuery()
            ->whereIn('user_id', $systemUserIds)
            ->where(function($q) {
                $q->where('is_template', true)->orWhereNull('is_template');
            })
            ->with(['user:id,name,first_name'])
            ->withCount('exercises');

        return $this->applyFilters($query, $filters)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user's own templates
     */
    public function getUserTemplates(User $user, array $filters = []): Collection
    {
        $query = $this->newQuery()
            ->where('user_id', $user->id)
            ->where('is_template', true)
            ->with(['user:id,name,first_name'])
            ->withCount('exercises');

        return $this->applyFilters($query, $filters)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent workout sessions
     */
    public function getRecentSessions(User $user, int $limit = 10): Collection
    {
        return $this->newQuery()
            ->where('user_id', $user->id)
            ->where('is_template', false)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get workout by ID for user (ensures ownership or public access)
     */
    public function findForUser(int $workoutId, User $user): ?Workout
    {
        $systemUserIds = SystemUserResolver::ids();

        return $this->newQuery()
            ->where('id', $workoutId)
            ->where(function($q) use ($user, $systemUserIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('user_id', $systemUserIds);
            })
            ->first();
    }

    /**
     * Get workouts by category
     */
    public function getByCategory(string $category, User $user): Collection
    {
        $systemUserIds = SystemUserResolver::ids();

        return $this->newQuery()
            ->where(function($q) use ($user, $systemUserIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('user_id', $systemUserIds);
            })
            ->where(function($q) use ($category) {
                $q->where('type', $category)
                  ->orWhere('category', $category);
            })
            ->get();
    }

    /**
     * Get completed workouts count for user
     */
    public function getCompletedCount(User $user): int
    {
        return $this->newQuery()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where(function($q) use ($filters) {
                $q->where('type', $filters['category'])
                  ->orWhere('category', $filters['category']);
            });
        }

        if (!empty($filters['difficulty']) && $filters['difficulty'] !== 'all') {
            $query->where(function($q) use ($filters) {
                $q->where('difficulty', $filters['difficulty'])
                  ->orWhere('difficulty_level', $filters['difficulty']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
