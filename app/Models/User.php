<?php
// app/Models/User.php - FIXED VERSION FOR AUTHENTICATION
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Traits\CamelCaseSerializationTrait;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CamelCaseSerializationTrait;

    protected $fillable = [
        'name',
        'first_name',    // ADDED: Required for registration
        'last_name',     // ADDED: Required for registration
        'email',
        'password',
        'age',
        'height',
        'weight',
        'gender',
        'phone',
        'avatar',
        'profile_photo_url',
        'location',
        'birth_date',
        'date_of_birth',
        'bio',
        'activity_level',
        'goals',
        'blood_type',
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
        'birth_date' => 'date',
        'date_of_birth' => 'date',
        'goals' => 'array',
        'preferences' => 'array',
        'height' => 'integer',
        'weight' => 'float',
        'age' => 'integer',
    ];

    // REMOVED: Problematic appends that cause recursion during registration
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

    // =============================================
    // SCORING & ACHIEVEMENTS RELATIONSHIPS
    // =============================================

    public function userScore()
    {
        return $this->hasOne(UserScore::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot(['unlocked_at', 'progress_data', 'points_earned'])
            ->withTimestamps()
            ->orderBy('user_achievements.unlocked_at', 'desc');
    }

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
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
    // NUTRITION RELATIONSHIPS
    // =============================================

    public function nutritionGoal()
    {
        return $this->hasOne(NutritionGoal::class);
    }

    public function nutritionGoals()
    {
        return $this->hasMany(NutritionGoal::class);
    }

    public function mealEntries()
    {
        return $this->hasMany(MealEntry::class);
    }

    public function waterIntakes()
    {
        return $this->hasMany(WaterIntake::class);
    }

    public function userDiets()
    {
        return $this->hasMany(UserDiet::class);
    }

    // =============================================
    // SAFE STATISTICS METHODS
    // =============================================

    /**
     * FIXED: workoutSessions method - now properly references sessions
     */
    public function workoutSessions()
    {
        return $this->sessions(); // Use the existing sessions relationship
    }

    /**
     * Get comprehensive user statistics with enhanced error handling
     */
    public function getStats(): array
    {
        // SAFE: Return default stats during registration or if user doesn't exist
        if (!$this->exists) {
            return $this->getDefaultStats();
        }

        $cacheKey = "user_stats_{$this->id}";

        // Don't cache in CLI mode to avoid memory issues
        if (PHP_SAPI === 'cli') {
            return $this->calculateStatsDirectly();
        }

        return Cache::remember($cacheKey, now()->addMinutes(30), function() {
            try {
                Log::info('Calculating stats for user', ['user_id' => $this->id]);

                $stats = $this->getWorkoutStats();
                $stats = array_merge($stats, $this->getGoalStats());
                $stats = array_merge($stats, $this->getTimeBasedStats());

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

    private function getWorkoutStats(): array
    {
        $stats = [
            'total_workouts' => 0,
            'total_minutes' => 0,
            'total_calories' => 0,
            'avg_duration' => 0,
            'avg_calories' => 0,
        ];

        if (!class_exists('App\Models\Workout')) {
            return $stats;
        }

        try {
            $workoutStats = $this->workoutSessions()
                ->where('status', 'completed')
                ->selectRaw('
                    COUNT(*) as total_workouts,
                    COALESCE(SUM(actual_duration), 0) as total_minutes,
                    COALESCE(SUM(actual_calories), 0) as total_calories,
                    COALESCE(AVG(actual_duration), 0) as avg_duration,
                    COALESCE(AVG(actual_calories), 0) as avg_calories
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

        return $stats;
    }

    private function getGoalStats(): array
    {
        $stats = [
            'total_goals' => 0,
            'active_goals' => 0,
            'completed_goals' => 0,
        ];

        if (!class_exists('App\Models\Goal')) {
            return $stats;
        }

        try {
            // Check if goals table exists and has the required columns
            if (Schema::hasTable('goals')) {
                if (Schema::hasColumn('goals', 'active')) {
                    // Use the active column if it exists
                    $goalStats = $this->goals()
                        ->selectRaw('
                            COUNT(*) as total_goals,
                            SUM(CASE WHEN active = true THEN 1 ELSE 0 END) as active_goals,
                            SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed_goals
                        ')
                        ->first();
                } else {
                    // Fallback to status-based query
                    $goalStats = $this->goals()
                        ->selectRaw('
                            COUNT(*) as total_goals,
                            SUM(CASE WHEN status = \'active\' OR status = \'in_progress\' THEN 1 ELSE 0 END) as active_goals,
                            SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed_goals
                        ')
                        ->first();
                }

                if ($goalStats) {
                    $stats['total_goals'] = (int) $goalStats->total_goals;
                    $stats['active_goals'] = (int) $goalStats->active_goals;
                    $stats['completed_goals'] = (int) $goalStats->completed_goals;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Goal stats calculation failed', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }

        return $stats;
    }

    private function getTimeBasedStats(): array
    {
        $stats = [
            'current_streak' => 0,
            'weekly_workouts' => 0,
            'monthly_workouts' => 0,
            'has_completed_today' => false,
            'calories_today' => 0,
            'profile_completion' => 0,
            'fitness_level' => 'beginner',
        ];

        try {
            $stats['weekly_workouts'] = $this->getWeeklyWorkoutCount();
            $stats['monthly_workouts'] = $this->getMonthlyWorkoutCount();
            $stats['has_completed_today'] = $this->hasCompletedWorkoutToday();
            $stats['current_streak'] = $this->calculateCurrentStreak();
            $stats['calories_today'] = $this->getCaloriesToday();
            $stats['profile_completion'] = $this->getProfileCompletionPercentage();
            $stats['fitness_level'] = $this->getFitnessLevel($this->getWorkoutStats()['total_workouts']);
        } catch (\Exception $e) {
            Log::warning('Time-based stats calculation failed', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }

        return $stats;
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
                    'category' => __('Missing data'),
                    'color' => '#6b7280',
                    'recommendation' => __('Please enter your height and weight to calculate your BMI.')
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
                'underweight' => __('Underweight'),
                'normal' => __('Normal weight'),
                'overweight' => __('Overweight'),
                'obese' => __('Obesity')
            ];

            $recommendations = [
                'underweight' => __('Your BMI indicates that you are underweight. Consult a healthcare professional.'),
                'normal' => __('Your BMI is within the normal range. Keep up the good habits!'),
                'overweight' => __('Your BMI indicates that you are overweight. Regular physical activity is recommended.'),
                'obese' => __('Your BMI indicates that you are obese. Consult a healthcare professional.')
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
                'category' => __('Calculation error'),
                'color' => '#6b7280',
                'recommendation' => __('Error calculating BMI.')
            ];
        }
    }

    // =============================================
    // AGE CALCULATION
    // =============================================

    /**
     * Calculate age dynamically from date_of_birth
     */
    public function getCalculatedAge(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        try {
            $birthDate = Carbon::parse($this->date_of_birth);
            $today = Carbon::today();
            
            // Calculate age correctly
            $age = $birthDate->diffInYears($today);
            
            return (int) $age;
        } catch (\Exception $e) {
            Log::warning('Age calculation error', [
                'user_id' => $this->id ?? 'unknown',
                'date_of_birth' => $this->date_of_birth,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get age - prioritizes calculated age over static field
     */
    public function getAgeAttribute($value): ?int
    {
        // If we have date_of_birth, calculate dynamically
        if ($this->date_of_birth) {
            return $this->getCalculatedAge();
        }
        
        // Fall back to stored static value
        return $value;
    }

    // =============================================
    // SAFE HELPER METHODS
    // =============================================

    /**
     * Calculate current streak safely
     */
    public function calculateCurrentStreak(): int
    {
        // SAFE: Return 0 if user doesn't exist or workout model doesn't exist
        if (!$this->exists || !class_exists('App\Models\Workout')) {
            return 0;
        }

        try {
            $workoutDates = $this->workoutSessions()
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->selectRaw('DATE(completed_at) as workout_date')
                ->distinct()
                ->orderBy('workout_date', 'desc')
                ->pluck('workout_date')
                ->map(fn ($date) => Carbon::parse($date));

            if ($workoutDates->isEmpty()) {
                return 0;
            }

            $streak = 0;
            $today = Carbon::today();
            $lastWorkoutDate = $workoutDates->first();

            if ($lastWorkoutDate->isToday() || $lastWorkoutDate->isYesterday()) {
                $streak = 1;
                $previousDate = $lastWorkoutDate;

                for ($i = 1; $i < $workoutDates->count(); $i++) {
                    $currentDate = $workoutDates[$i];
                    if ($previousDate->diffInDays($currentDate) == 1) {
                        $streak++;
                        $previousDate = $currentDate;
                    } else {
                        break;
                    }
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
        if (!$this->exists || !class_exists('App\Models\Workout')) {
            return false;
        }

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
        if (!$this->exists || !class_exists('App\Models\Workout')) {
            return 0;
        }

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
        if (!$this->exists || !class_exists('App\Models\Workout')) {
            return 0;
        }

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
        if (!$this->exists || !class_exists('App\Models\Workout')) {
            return 0;
        }

        try {
            return (int) $this->workoutSessions()
                ->where('status', 'completed')
                ->whereDate('completed_at', Carbon::today())
                ->sum('actual_calories');
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
            if (!$this->exists || !class_exists('App\Models\Workout')) {
                return 'beginner';
            }

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
                'user_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return 25; // Basic completion for new user with name and email
        }
    }

    /**
     * Count active goals safely
     */
    public function getActiveGoalsCount(): int
    {
        if (!$this->exists || !class_exists('App\Models\Goal')) {
            return 0;
        }

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
        if (!$this->exists || !class_exists('App\Models\Goal')) {
            return 0;
        }

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
     * Calculate stats directly without caching (CLI mode)
     */
    private function calculateStatsDirectly(): array
    {
        try {
            $stats = $this->getWorkoutStats();
            $stats = array_merge($stats, $this->getGoalStats());
            $stats = array_merge($stats, $this->getTimeBasedStats());
            return $stats;
        } catch (\Exception $e) {
            Log::error('Direct stats calculation failed', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultStats();
        }
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
            'profile_completion' => $this->getProfileCompletionPercentage(),
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
            if ($this->exists) {
                $keys = [
                    "user_stats_{$this->id}",
                    "user_streak_{$this->id}",
                    "user_bmi_{$this->id}",
                ];
                
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Cache clear error', [
                'user_id' => $this->id ?? 'unknown',
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
    // MODEL EVENTS - SAFE VERSION
    // =============================================

    protected static function booted(): void
    {
        // SAFE: Only clear cache for existing users, not during creation
        static::updated(function ($user) {
            if ($user->exists) {
                $user->clearCache();
            }
        });
        
        // Clear cache when user is deleted
        static::deleted(function ($user) {
            if ($user->exists) {
                $user->clearCache();
            }
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

    // =============================================
    // PASSWORD RESET NOTIFICATION
    // =============================================

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}