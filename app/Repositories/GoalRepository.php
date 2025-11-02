<?php

namespace App\Repositories;

use App\Models\Goal;
use App\Models\User;
use App\Repositories\Contracts\GoalRepositoryInterface;
use Illuminate\Support\Collection;

class GoalRepository extends BaseRepository implements GoalRepositoryInterface
{
    /**
     * Specify Model class name.
     */
    protected function model(): string
    {
        return Goal::class;
    }

    /**
     * Get goals for a user with optional filters.
     */
    public function getForUser(User $user, array $filters = []): Collection
    {
        $query = $this->newQuery()->where('user_id', $user->id);

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Find a goal by ID for a specific user.
     */
    public function findForUser(User $user, int $goalId): ?Goal
    {
        return $this->newQuery()
            ->where('user_id', $user->id)
            ->where('id', $goalId)
            ->first();
    }

    /**
     * Find a goal by ID for a specific user or fail.
     */
    public function findOrFailForUser(User $user, int $goalId): Goal
    {
        return $this->newQuery()
            ->where('user_id', $user->id)
            ->where('id', $goalId)
            ->firstOrFail();
    }

    /**
     * Reset all goals for a specific user.
     * Returns the number of affected rows.
     */
    public function resetAllForUser(User $user): int
    {
        return $this->newQuery()
            ->where('user_id', $user->id)
            ->update([
                'current_value' => 0,
                'status' => 'not-started',
            ]);
    }
}
