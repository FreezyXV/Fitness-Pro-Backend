<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_points',
        'current_streak',
        'best_streak',
        'goals_completed',
        'goals_created',
        'achievements_unlocked',
        'level',
        'level_progress',
        'weekly_goals_completed',
        'monthly_goals_completed',
        'streak_last_updated',
        'milestone_data'
    ];

    protected $casts = [
        'streak_last_updated' => 'date',
        'milestone_data' => 'array',
        'total_points' => 'integer',
        'current_streak' => 'integer',
        'best_streak' => 'integer',
        'goals_completed' => 'integer',
        'goals_created' => 'integer',
        'achievements_unlocked' => 'integer',
        'level' => 'integer',
        'level_progress' => 'integer',
        'weekly_goals_completed' => 'integer',
        'monthly_goals_completed' => 'integer',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Level system
    public function getNextLevelPoints(): int
    {
        return $this->level * 100; // Each level requires 100 more points than the previous
    }

    public function getPointsToNextLevel(): int
    {
        return $this->getNextLevelPoints() - $this->level_progress;
    }

    public function addPoints(int $points, string $reason = 'Goal completion'): self
    {
        $this->total_points += $points;
        $this->level_progress += $points;
        
        // Check for level up
        while ($this->level_progress >= $this->getNextLevelPoints()) {
            $this->level_progress -= $this->getNextLevelPoints();
            $this->level++;
            
            // Award bonus points for leveling up
            $levelBonus = $this->level * 50;
            $this->total_points += $levelBonus;
            
            // event(new \App\Events\UserLeveledUp($this->user, $this->level, $levelBonus));
        }
        
        $this->save();
        return $this;
    }

    public function incrementGoalsCompleted(): self
    {
        $this->goals_completed++;
        
        // Update weekly/monthly counters
        $now = Carbon::now();
        $weekStart = $now->startOfWeek();
        $monthStart = $now->startOfMonth();
        
        // This is simplified - in a real app you'd track these with separate date fields
        $this->weekly_goals_completed++;
        $this->monthly_goals_completed++;
        
        // Update streak
        $this->updateStreak();
        
        // Award points for goal completion
        $points = $this->calculateGoalCompletionPoints();
        $this->addPoints($points, 'Goal completed');
        
        return $this;
    }

    public function incrementGoalsCreated(): self
    {
        $this->goals_created++;
        $this->addPoints(5, 'Goal created'); // 5 points for creating a goal
        $this->save();
        return $this;
    }

    public function updateStreak(): self
    {
        $today = Carbon::today();
        
        if (!$this->streak_last_updated) {
            // First goal completion
            $this->current_streak = 1;
        } elseif ($this->streak_last_updated->equalTo($today)) {
            // Already updated today, no change to streak
            return $this;
        } elseif ($this->streak_last_updated->equalTo($today->subDay())) {
            // Consecutive day
            $this->current_streak++;
        } else {
            // Streak broken
            $this->current_streak = 1;
        }
        
        // Update best streak
        if ($this->current_streak > $this->best_streak) {
            $this->best_streak = $this->current_streak;
        }
        
        $this->streak_last_updated = $today;
        $this->save();
        
        return $this;
    }

    private function calculateGoalCompletionPoints(): int
    {
        $basePoints = 20;
        
        // Bonus points for streak
        $streakBonus = min($this->current_streak * 2, 50); // Max 50 bonus points
        
        // Bonus points for completing multiple goals
        $completionBonus = 0;
        if ($this->goals_completed > 0) {
            if ($this->goals_completed >= 50) $completionBonus = 30;
            elseif ($this->goals_completed >= 25) $completionBonus = 20;
            elseif ($this->goals_completed >= 10) $completionBonus = 10;
            elseif ($this->goals_completed >= 5) $completionBonus = 5;
        }
        
        return $basePoints + $streakBonus + $completionBonus;
    }

    // Static methods for leaderboard
    public static function getTopUsers(int $limit = 10)
    {
        return static::with('user')
            ->orderBy('total_points', 'desc')
            ->orderBy('level', 'desc')
            ->orderBy('current_streak', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getUserRanking(int $userId): int
    {
        $userScore = static::where('user_id', $userId)->first();
        if (!$userScore) return 0;
        
        return static::where('total_points', '>', $userScore->total_points)->count() + 1;
    }

    // Create or update user score
    public static function createOrUpdateForUser(User $user): self
    {
        return static::updateOrCreate(
            ['user_id' => $user->id],
            [
                'total_points' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
                'goals_completed' => 0,
                'goals_created' => 0,
                'achievements_unlocked' => 0,
                'level' => 1,
                'level_progress' => 0,
                'weekly_goals_completed' => 0,
                'monthly_goals_completed' => 0,
            ]
        );
    }
}