<?php
// app/Models/User.php - FIXED VERSION WITHOUT PROBLEMATIC APPENDS
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'age',
        'height',
        'weight',
        'gender',
        'phone',
        'profile_photo_url',
        'date_of_birth',
        'location',
        'bio',
        'activity_level',
        'goals',
        'blood_group',
        'preferences'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'goals' => 'array',
        'preferences' => 'array',
        'height' => 'integer',
        'weight' => 'float',
        'age' => 'integer',
    ];

    // REMOVED: Problematic appends that cause recursion
    // protected $appends = ['stats', 'bmi_info'];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function workouts()
    {
        return $this->hasMany(Workout::class);
    }

    public function templates()
    {
        return $this->workouts()->where('is_template', true);
    }

    public function sessions()
    {
        return $this->workouts()->where('is_template', false);
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function calendarTasks()
    {
        return $this->hasMany(CalendarTask::class);
    }

    public function favoriteExercises()
    {
        return $this->belongsToMany(Exercise::class, 'user_favorite_exercises')->withTimestamps();
    }

    public function exerciseProgress()
    {
        return $this->hasMany(UserExerciseProgress::class);
    }

    public function sessionExercises()
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    // =============================================
    // SAFE STATISTICS METHODS
    // =============================================

    /**
     * Get comprehensive user statistics with enhanced error handling
     */
    public function getStats(): array
    {
        $cacheKey = "user_stats_{$this->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function() {
            try {
                Log::info('Calculating stats for user', ['user_id' => $this->id]);

                // Initialize default stats
                $stats = [
                    'total_workouts' => 0,
                    'total_minutes' => 0,
                    'total_calories' => 0,
                    'avg_duration' => 0,
                    'avg_calories' => 0,
                    'current_streak' => 0,
                    'weekly_workouts' => 0,
                    'monthly_workouts' => 0,
                    'total_goals' => 0,
                    'active_goals' => 0,
                    'completed_goals' => 0,
                    'has_completed_today' => false,
                    'profile_completion' => 0,
                    'fitness_level' => 'beginner',
                    'calories_today' => 0,
                ];

                // Get workout statistics safely
                try {
                    $workoutStats = $this->workoutSessions()
                        ->where('status', 'completed')
                        ->selectRaw('
                            COUNT(*) as total_workouts,
                            COALESCE(SUM(duration_minutes), 0) as total_minutes,
                            COALESCE(SUM(calories_burned), 0) as total_calories,
                            COALESCE(AVG(duration_minutes), 0) as avg_duration,
                            COALESCE(AVG(calories_burned), 0) as avg_calories
                        ')
                        ->first();

                    if ($workoutStats) {
                        $stats['total_workouts'] = (int) $workoutStats->total_workouts;
                        $stats['total_minutes'] = (int) $workoutStats->total_minutes;
                        $stats['total_calories'] = (int) $workoutStats->total_calories;
                        $stats['avg_duration'] = round((float) $workoutStats->avg_duration, 1);
                        $stats['avg_calories'] = round((float) $workoutStats->avg_calories, 1);
                    }
                } catch (\Exception $e) {
                    Log::warning('Workout stats calculation failed', [
                        'user_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Get goal statistics safely
                try {
                    $goalStats = $this->goals()
                        ->selectRaw('
                            COUNT(*) as total_goals,
                            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_goals,
                            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_goals
                        ')
                        ->first();

                    if ($goalStats) {
                        $stats['total_goals'] = (int) $goalStats->total_goals;
                        $stats['active_goals'] = (int) $goalStats->active_goals;
                        $stats['completed_goals'] = (int) $goalStats->completed_goals;
                    }
                } catch (\Exception $e) {
                    Log::warning('Goal stats calculation failed', [
                        'user_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Get time-based statistics safely
                try {
                    $stats['weekly_workouts'] = $this->getWeeklyWorkoutCount();
                    $stats['monthly_workouts'] = $this->getMonthlyWorkoutCount();
                    $stats['has_completed_today'] = $this->hasCompletedWorkoutToday();
                    $stats['current_streak'] = $this->calculateCurrentStreak();
                    $stats['calories_today'] = $this->getCaloriesToday();
                    $stats['profile_completion'] = $this->getProfileCompletionPercentage();
                    $stats['fitness_level'] = $this->getFitnessLevel($stats['total_workouts']);
                } catch (\Exception $e) {
                    Log::warning('Time-based stats calculation failed', [
                        'user_id' => $this->id,
                        'error' => $e->getMessage()
                    ]);
                }

                Log::info('Stats calculated successfully', [
                    'user_id' => $this->id,
                    'stats' => $stats
                ]);

                return $stats;

            } catch (\Exception $e) {
                Log::error('Complete stats calculation failed', [
                    'user_id' => $this->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->getDefaultStats();
            }
        });
    }

    /**
     * Get BMI information safely
     */
    public function getBmiInfo(): array
    {
        try {
            // Use raw attribute access to prevent recursion
            $height = $this->getAttribute('height');
            $weight = $this->getAttribute('weight');
            
            if (!$height || !$weight || $height <= 0 || $weight <= 0) {
                return [
                    'bmi' => null,
                    'status' => 'unknown',
                    'category' => 'Données manquantes',
                    'color' => '#6b7280',
                    'recommendation' => 'Veuillez renseigner votre taille et poids pour calculer votre IMC.'
                ];
            }
            
            $heightInMeters = $height / 100;
            $bmi = round($weight / ($heightInMeters * $heightInMeters), 1);
            
            $status = match (true) {
                $bmi < 18.5 => 'underweight',
                $bmi < 25   => 'normal',
                $bmi < 30   => 'overweight',
                default     => 'obese',
            };
            
            $categories = [
                'underweight' => 'Insuffisance pondérale',
                'normal' => 'Poids normal',
                'overweight' => 'Surpoids',
                'obese' => 'Obésité'
            ];

            $recommendations = [
                'underweight' => 'Votre IMC indique un sous-poids. Consultez un professionnel de santé.',
                'normal' => 'Votre IMC est dans la normale. Maintenez vos bonnes habitudes !',
                'overweight' => 'Votre IMC indique un surpoids. Une activité physique régulière est recommandée.',
                'obese' => 'Votre IMC indique une obésité. Consultez un professionnel de santé.'
            ];
            
            return [
                'bmi' => $bmi,
                'status' => $status,
                'category' => $categories[$status],
                'color' => $this->getBmiColor($status),
                'recommendation' => $recommendations[$status]
            ];
        } catch (\Exception $e) {
            Log::error('BMI calculation error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'bmi' => null,
                'status' => 'error',
                'category' => 'Erreur de calcul',
                'color' => '#6b7280',
                'recommendation' => 'Erreur lors du calcul de l\'IMC.'
            ];
        }
    }

    // =============================================
    // SAFE HELPER METHODS
    // =============================================

    /**
     * Calculate current streak safely
     */
    public function calculateCurrentStreak(): int
    {
        try {
            $workoutDates = $this->workoutSessions()
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->selectRaw('DATE(completed_at) as workout_date')
                ->distinct()
                ->orderBy('workout_date', 'desc')
                ->limit(100) // Limit for performance
                ->pluck('workout_date')
                ->filter()
                ->values()
                ->toArray();

            if (empty($workoutDates)) {
                return 0;
            }

            $streak = 0;
            $currentDate = Carbon::now()->startOfDay();
            
            // Check if workout was done today or yesterday
            $lastWorkoutDate = Carbon::parse($workoutDates[0]);
            $daysSinceLastWorkout = $currentDate->diffInDays($lastWorkoutDate);
            
            if ($daysSinceLastWorkout > 1) {
                return 0; // Streak is broken
            }
            
            // Start counting from the appropriate date
            $checkDate = $daysSinceLastWorkout === 0 ? $currentDate : $currentDate->copy()->subDay();
            
            foreach ($workoutDates as $workoutDate) {
                $workoutCarbon = Carbon::parse($workoutDate);
                
                if ($workoutCarbon->isSameDay($checkDate)) {
                    $streak++;
                    $checkDate = $checkDate->subDay();
                } else {
                    break; // Streak is broken
                }
            }

            return $streak;

        } catch (\Exception $e) {
            Log::warning('Streak calculation error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Check if user completed workout today
     */
    public function hasCompletedWorkoutToday(): bool
    {
        try {
            return $this->workoutSessions()
                ->where('status', 'completed')
                ->whereDate('completed_at', Carbon::today())
                ->exists();
        } catch (\Exception $e) {
            Log::warning('Today workout check error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get weekly workout count
     */
    public function getWeeklyWorkoutCount(): int
    {
        try {
            return $this->workoutSessions()
                ->where('status', 'completed')
                ->whereBetween('completed_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->count();
        } catch (\Exception $e) {
            Log::warning('Weekly workout count error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get monthly workout count
     */
    public function getMonthlyWorkoutCount(): int
    {
        try {
            return $this->workoutSessions()
                ->where('status', 'completed')
                ->whereBetween('completed_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])
                ->count();
        } catch (\Exception $e) {
            Log::warning('Monthly workout count error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get today's calories burned
     */
    public function getCaloriesToday(): int
    {
        try {
            return (int) $this->workoutSessions()
                ->where('status', 'completed')
                ->whereDate('completed_at', Carbon::today())
                ->sum('calories_burned');
        } catch (\Exception $e) {
            Log::warning('Today calories calculation error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get fitness level based on total workouts
     */
    public function getFitnessLevel(?int $totalWorkouts = null): string
    {
        try {
            $totalWorkouts = $totalWorkouts ?? $this->workoutSessions()->where('status', 'completed')->count();
            
            return match (true) {
                $totalWorkouts < 10 => 'beginner',
                $totalWorkouts < 50 => 'intermediate',
                $totalWorkouts >= 50 => 'advanced',
                default => 'beginner'
            };
        } catch (\Exception $e) {
            return 'beginner';
        }
    }

    /**
     * Calculate profile completion percentage
     */
    public function getProfileCompletionPercentage(): int
    {
        try {
            $fields = ['name', 'email', 'age', 'height', 'weight', 'gender', 'location', 'bio'];
            $completed = 0;
            
            foreach ($fields as $field) {
                if (!empty($this->getAttribute($field))) {
                    $completed++;
                }
            }
            
            return round(($completed / count($fields)) * 100);
        } catch (\Exception $e) {
            Log::warning('Profile completion calculation error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Count active goals safely
     */
    public function getActiveGoalsCount(): int
    {
        try {
            return $this->goals()->where('status', 'active')->count();
        } catch (\Exception $e) {
            Log::warning('Active goals count error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Count completed goals safely
     */
    public function getCompletedGoalsCount(): int
    {
        try {
            return $this->goals()->where('status', 'completed')->count();
        } catch (\Exception $e) {
            Log::warning('Completed goals count error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    // =============================================
    // PRIVATE HELPER METHODS
    // =============================================

    /**
     * Get BMI color based on status
     */
    private function getBmiColor(string $status): string
    {
        return match ($status) {
            'underweight' => '#3b82f6', // Blue
            'normal' => '#22c55e',       // Green
            'overweight' => '#f59e0b',   // Orange
            'obese' => '#ef4444',        // Red
            default => '#6b7280'         // Gray
        };
    }

    /**
     * Get default stats when calculation fails
     */
    private function getDefaultStats(): array
    {
        return [
            'total_workouts' => 0,
            'total_minutes' => 0,
            'total_calories' => 0,
            'avg_duration' => 0,
            'avg_calories' => 0,
            'current_streak' => 0,
            'weekly_workouts' => 0,
            'monthly_workouts' => 0,
            'total_goals' => 0,
            'active_goals' => 0,
            'completed_goals' => 0,
            'has_completed_today' => false,
            'profile_completion' => 0,
            'fitness_level' => 'beginner',
            'calories_today' => 0,
        ];
    }

    // =============================================
    // CACHE MANAGEMENT
    // =============================================

    /**
     * Clear all user-related cache
     */
    public function clearCache(): void
    {
        try {
            $keys = [
                "user_stats_{$this->id}",
                "user_streak_{$this->id}",
                "user_bmi_{$this->id}",
            ];
            
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } catch (\Exception $e) {
            Log::warning('Cache clear error', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Refresh user statistics cache
     */
    public function refreshStats(): array
    {
        $this->clearCache();
        return $this->getStats();
    }

    // =============================================
    // MODEL EVENTS
    // =============================================

    protected static function booted(): void
    {
        // Clear cache when user data changes
        static::saved(function ($user) {
            $user->clearCache();
        });
        
        // Clear cache when user is deleted
        static::deleted(function ($user) {
            $user->clearCache();
        });
    }

    // =============================================
    // SCOPES FOR OPTIMIZED QUERIES
    // =============================================

    public function scopeActive($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeWithRecentActivity($query, int $days = 30)
    {
        return $query->whereHas('sessions', function ($q) use ($days) {
            $q->where('completed_at', '>=', Carbon::now()->subDays($days));
        });
    }
}