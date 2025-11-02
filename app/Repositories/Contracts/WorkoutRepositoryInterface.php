<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Collection;

interface WorkoutRepositoryInterface extends RepositoryInterface
{
    /**
     * Get workout templates for a user
     */
    public function getTemplatesForUser(User $user, array $filters = []): Collection;

    /**
     * Get workout sessions for a user
     */
    public function getSessionsForUser(User $user, array $filters = []): Collection;

    /**
     * Get public templates (seeded workouts)
     */
    public function getPublicTemplates(array $filters = []): Collection;

    /**
     * Get user's own templates
     */
    public function getUserTemplates(User $user, array $filters = []): Collection;

    /**
     * Get recent workout sessions
     */
    public function getRecentSessions(User $user, int $limit = 10): Collection;

    /**
     * Get workout by ID for user (ensures ownership)
     */
    public function findForUser(int $workoutId, User $user): ?Workout;

    /**
     * Get workouts by category
     */
    public function getByCategory(string $category, User $user): Collection;

    /**
     * Get completed workouts count for user
     */
    public function getCompletedCount(User $user): int;
}
