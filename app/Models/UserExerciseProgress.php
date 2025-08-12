<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserExerciseProgress extends Model
{
    use HasFactory;

    protected $table = 'user_exercise_progress';

    protected $fillable = [
        'user_id',
        'exercise_id',
        'max_weight',
        'max_reps',
        'max_duration_seconds',
        'total_sessions',
        'total_sets',
        'total_reps',
        'total_duration_seconds',
        'total_calories_burned',
        'current_weight',
        'current_reps',
        'current_duration_seconds',
        'last_performed',
        'streak_days',
        'average_effort_level',
        'current_difficulty_level',
        'is_favorite'
    ];

    protected $casts = [
        'max_weight' => 'decimal:2',
        'max_reps' => 'integer',
        'max_duration_seconds' => 'integer',
        'total_sessions' => 'integer',
        'total_sets' => 'integer',
        'total_reps' => 'integer',
        'total_duration_seconds' => 'integer',
        'total_calories_burned' => 'integer',
        'current_weight' => 'decimal:2',
        'current_reps' => 'integer',
        'current_duration_seconds' => 'integer',
        'last_performed' => 'date',
        'streak_days' => 'integer',
        'average_effort_level' => 'decimal:1',
        'is_favorite' => 'boolean'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

    // Scopes
    public function scopeForUser($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('user_id', $userId);
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeRecentlyPerformed($query, $days = 30)
    {
        return $query->where('last_performed', '>=', Carbon::now()->subDays($days));
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('current_difficulty_level', $difficulty);
    }

    // Methods
    public function updateFromSession(WorkoutSessionExercise $sessionExercise): void
    {
        if (!$sessionExercise->is_completed) {
            return;
        }

        // Update personal records
        if ($sessionExercise->weight_used && 
            (!$this->max_weight || $sessionExercise->weight_used > $this->max_weight)) {
            $this->max_weight = $sessionExercise->weight_used;
        }

        if ($sessionExercise->completed_reps && 
            (!$this->max_reps || $sessionExercise->completed_reps > $this->max_reps)) {
            $this->max_reps = $sessionExercise->completed_reps;
        }

        if ($sessionExercise->actual_duration_seconds && 
            (!$this->max_duration_seconds || $sessionExercise->actual_duration_seconds > $this->max_duration_seconds)) {
            $this->max_duration_seconds = $sessionExercise->actual_duration_seconds;
        }

        // Update totals
        $this->total_sessions++;
        $this->total_sets += $sessionExercise->completed_sets ?? 0;
        $this->total_reps += $sessionExercise->completed_reps ?? 0;
        $this->total_duration_seconds += $sessionExercise->actual_duration_seconds ?? 0;
        $this->total_calories_burned += $sessionExercise->calories_burned ?? 0;

        // Update current values
        $this->current_weight = $sessionExercise->weight_used ?? $this->current_weight;
        $this->current_reps = $sessionExercise->completed_reps ?? $this->current_reps;
        $this->current_duration_seconds = $sessionExercise->actual_duration_seconds ?? $this->current_duration_seconds;

        // Update effort level average
        if ($sessionExercise->effort_level) {
            $totalEffort = ($this->average_effort_level * ($this->total_sessions - 1)) + $sessionExercise->effort_level;
            $this->average_effort_level = round($totalEffort / $this->total_sessions, 1);
        }

        // Update streak
        $this->updateStreak();
        $this->last_performed = now()->toDateString();

        $this->save();
    }

    protected function updateStreak(): void
    {
        if (!$this->last_performed) {
            $this->streak_days = 1;
            return;
        }

        $lastPerformed = Carbon::parse($this->last_performed);
        $daysSinceLastPerformed = $lastPerformed->diffInDays(now());

        if ($daysSinceLastPerformed === 0) {
            // Same day, no change to streak
            return;
        } elseif ($daysSinceLastPerformed === 1) {
            // Consecutive day, increment streak
            $this->streak_days++;
        } else {
            // Gap in performance, reset streak
            $this->streak_days = 1;
        }
    }

    public function getProgressTrend(): array
    {
        $sessions = WorkoutSessionExercise::forUser($this->user_id)
                                         ->forExercise($this->exercise_id)
                                         ->completed()
                                         ->orderBy('created_at')
                                         ->take(20)
                                         ->get();

        return [
            'weight_trend' => $sessions->pluck('weight_used', 'created_at')->filter()->toArray(),
            'reps_trend' => $sessions->pluck('completed_reps', 'created_at')->filter()->toArray(),
            'duration_trend' => $sessions->pluck('actual_duration_seconds', 'created_at')->filter()->toArray()
        ];
    }

    public function getAveragePerformance(): array
    {
        return [
            'average_weight' => $this->total_sessions > 0 ? 
                round($this->current_weight ?? 0, 2) : 0,
            'average_reps' => $this->total_sessions > 0 ? 
                round($this->total_reps / $this->total_sessions, 1) : 0,
            'average_duration' => $this->total_sessions > 0 ? 
                round($this->total_duration_seconds / $this->total_sessions, 0) : 0,
            'average_calories' => $this->total_sessions > 0 ? 
                round($this->total_calories_burned / $this->total_sessions, 1) : 0
        ];
    }

    public function getDaysSinceLastPerformed(): ?int
    {
        return $this->last_performed ? 
            Carbon::parse($this->last_performed)->diffInDays(now()) : null;
    }

    public function getConsistencyScore(): float
    {
        if ($this->total_sessions === 0) {
            return 0;
        }

        // Base score on sessions in last 30 days
        $recentSessions = WorkoutSessionExercise::forUser($this->user_id)
                                               ->forExercise($this->exercise_id)
                                               ->completed()
                                               ->where('created_at', '>=', now()->subDays(30))
                                               ->count();

        $maxPossibleSessions = 30; // One per day would be perfect
        $consistencyScore = min(($recentSessions / $maxPossibleSessions) * 100, 100);

        // Bonus for current streak
        $streakBonus = min($this->streak_days * 2, 20); // Max 20% bonus

        return min($consistencyScore + $streakBonus, 100);
    }

    // Static methods
    public static function updateOrCreateFromSession(WorkoutSessionExercise $sessionExercise): self
    {
        $progress = static::firstOrCreate([
            'user_id' => $sessionExercise->user_id,
            'exercise_id' => $sessionExercise->exercise_id
        ]);

        $progress->updateFromSession($sessionExercise);
        
        return $progress;
    }

    // Serialization
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add computed properties
        $array['average_performance'] = $this->getAveragePerformance();
        $array['days_since_last_performed'] = $this->getDaysSinceLastPerformed();
        $array['consistency_score'] = $this->getConsistencyScore();
        $array['max_duration_minutes'] = round(($this->max_duration_seconds ?? 0) / 60, 2);
        $array['total_duration_minutes'] = round(($this->total_duration_seconds ?? 0) / 60, 2);
        
        return $array;
    }
}