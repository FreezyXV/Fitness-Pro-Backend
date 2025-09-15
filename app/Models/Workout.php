<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use App\Traits\CamelCaseSerializationTrait;

class Workout extends Model
{
    use HasFactory, CamelCaseSerializationTrait;

    protected $table = 'workouts';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category',
        'difficulty_level',
        'body_focus',
        'type',
        'intensity',
        'equipment',
        'goal',
        'frequency',
        'is_template',
        'template_id',
        'status',
        'started_at',
        'completed_at',
        'duration_minutes',
        'calories_burned',
        'notes',
        'is_public',
    ];

    protected $casts = [
        'is_template' => 'boolean',
        'is_public' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
        'calories_burned' => 'integer',
        'body_focus' => 'string',
        'type' => 'string',
        'intensity' => 'string',
        'equipment' => 'string',
        'goal' => 'string',
        'frequency' => 'string',
    ];

    // Add computed attributes for frontend compatibility - OPTIMIZED
    // Removed 'exercise_count' to prevent N+1 queries and memory issues
    protected $appends = [
        'difficulty_label',
        'category_label',
        'formatted_duration',
        'difficulty_info',
        'category_info',
        'is_custom',
        'is_active',
        'estimated_duration',
        'estimated_calories',
        'creator_name',
        'usage_count',
        'completion_rate'
    ];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Workout::class, 'template_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Workout::class, 'template_id')->where('is_template', false);
    }

    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'workout_exercises')
                    ->withPivot([
                        'order_index',
                        'sets',
                        'reps',
                        'duration_seconds',
                        'rest_time_seconds',
                        'target_weight',
                        'notes',
                        'completed_sets',
                        'completed_reps',
                        'actual_duration_seconds',
                        'weight_used',
                        'is_completed'
                    ])
                    ->withTimestamps()
                    ->orderBy('workout_exercises.order_index');
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('order_index');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeSessions($query)
    {
        return $query->where('is_template', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
    
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCategory($query, $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        if ($difficulty && $difficulty !== 'all') {
            return $query->where('difficulty_level', $difficulty);
        }
        return $query;
    }

    // =============================================
    // ACCESSORS - For Frontend Compatibility
    // =============================================

    public function getDifficultyLabelAttribute(): string
    {
        $labels = [
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé'
        ];
        
        return $labels[$this->difficulty_level] ?? ucfirst($this->difficulty_level ?? 'Non défini');
    }

    public function getCategoryLabelAttribute(): string
    {
        $labels = [
            'strength' => 'Force',
            'cardio' => 'Cardio',
            'flexibility' => 'Flexibilité',
            'hiit' => 'HIIT'
        ];
        
        return $labels[$this->category] ?? ucfirst($this->category ?? 'Non défini');
    }

    public function getExerciseCountAttribute(): int
    {
        // Use loaded relationship if available to avoid additional query
        if ($this->relationLoaded('exercises')) {
            return $this->exercises->count();
        }

        // Use exercises_count from withCount() if available
        if (isset($this->attributes['exercises_count'])) {
            return (int) $this->attributes['exercises_count'];
        }

        // Only cache in web context, not CLI
        if (PHP_SAPI === 'cli') {
            return $this->exercises()->count();
        }

        // Cache the count to avoid repeated queries
        return cache()->remember(
            "workout_exercise_count_{$this->id}",
            600, // 10 minutes
            fn() => $this->exercises()->count()
        );
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_minutes) {
            return '0 min';
        }

        $hours = intval($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}min";
        }

        return "{$this->duration_minutes} min";
    }

    public function getDifficultyInfoAttribute(): array
    {
        return [
            'value' => $this->difficulty_level,
            'label' => $this->difficulty_label,
            'color' => $this->getDifficultyColor(),
            'icon' => $this->getDifficultyIcon(),
        ];
    }

    public function getCategoryInfoAttribute(): array
    {
        return [
            'value' => $this->category,
            'label' => $this->category_label,
            'icon' => $this->getCategoryIcon(),
            'color' => $this->getCategoryColor(),
        ];
    }

    public function getIsCustomAttribute(): bool
    {
        return $this->is_template && $this->user_id !== null;
    }

    public function getIsActiveAttribute(): bool
    {
        // For templates, they're considered active if they're public or custom
        if ($this->is_template) {
            return true;
        }
        // For sessions, active means in progress
        return $this->status === 'in_progress';
    }

    // Additional computed properties for frontend
    public function getEstimatedDurationAttribute(): int
    {
        // If duration is already set, return it
        if ($this->duration_minutes) {
            return $this->duration_minutes;
        }

        // Otherwise, calculate from exercises
        return $this->calculateDurationFromExercises();
    }

    public function getEstimatedCaloriesAttribute(): int
    {
        // If calories are already set, return them
        if ($this->calories_burned) {
            return $this->calories_burned;
        }

        // Otherwise, calculate based on estimated duration and intensity
        return $this->calculateCaloriesFromWorkout();
    }

    public function getCreatorNameAttribute(): ?string
    {
        return $this->user ? $this->user->name : null;
    }

    public function getUsageCountAttribute(): int
    {
        // Count how many sessions were created from this template
        if ($this->is_template) {
            return $this->sessions()->count();
        }
        return 0;
    }

    public function getCompletionRateAttribute(): int
    {
        if ($this->is_template) {
            $total = $this->sessions()->count();
            if ($total === 0) return 0;

            $completed = $this->sessions()->where('status', 'completed')->count();
            return round(($completed / $total) * 100);
        }
        return 0;
    }

    // =============================================
    // HELPER METHODS
    // =============================================

    public function getDifficultyColor(): string
    {
        return match($this->difficulty_level) {
            'beginner' => '#48bb78',
            'intermediate' => '#ed8936', 
            'advanced' => '#f56565',
            default => '#718096'
        };
    }

    public function getDifficultyIcon(): string
    {
        return match($this->difficulty_level) {
            'beginner' => '🟢',
            'intermediate' => '🟡',
            'advanced' => '🔴',
            default => '⚪'
        };
    }

    public function getCategoryIcon(): string
    {
        return match($this->category) {
            'strength' => '💪',
            'cardio' => '❤️',
            'flexibility' => '🧘',
            'hiit' => '🔥',
            default => '💪'
        };
    }

    public function getCategoryColor(): string
    {
        return match($this->category) {
            'strength' => '#4CAF50',
            'cardio' => '#FF5722',
            'hiit' => '#FF9800',
            'flexibility' => '#9C27B0',
            default => '#718096'
        };
    }

    public function isOwnedBy($user): bool
    {
        return $this->user_id === $user->id;
    }

    public function canBeEditedBy($user): bool
    {
        return $this->isOwnedBy($user) && $this->is_template;
    }

    public function getExercisesByOrder(): array
    {
        return $this->exercises()
            ->orderBy('workout_exercises.order_index')
            ->get()
            ->map(function ($exercise) {
                $exerciseArray = $exercise->toArray();
                
                // Flatten pivot data to exercise level for frontend compatibility
                if (isset($exerciseArray['pivot'])) {
                    $exerciseArray['order_index'] = $exerciseArray['pivot']['order_index'] ?? 0;
                    $exerciseArray['sets'] = $exerciseArray['pivot']['sets'] ?? null;
                    $exerciseArray['reps'] = $exerciseArray['pivot']['reps'] ?? null;
                    $exerciseArray['duration_seconds'] = $exerciseArray['pivot']['duration_seconds'] ?? null;
                    $exerciseArray['rest_time_seconds'] = $exerciseArray['pivot']['rest_time_seconds'] ?? null;
                    $exerciseArray['target_weight'] = $exerciseArray['pivot']['target_weight'] ?? null;
                    $exerciseArray['notes'] = $exerciseArray['pivot']['notes'] ?? null;
                    $exerciseArray['completed_sets'] = $exerciseArray['pivot']['completed_sets'] ?? null;
                    $exerciseArray['completed_reps'] = $exerciseArray['pivot']['completed_reps'] ?? null;
                    $exerciseArray['actual_duration_seconds'] = $exerciseArray['pivot']['actual_duration_seconds'] ?? null;
                    $exerciseArray['weight_used'] = $exerciseArray['pivot']['weight_used'] ?? null;
                    $exerciseArray['is_completed'] = $exerciseArray['pivot']['is_completed'] ?? false;
                }
                
                return $exerciseArray;
            })
            ->toArray();
    }

    // =============================================
    // ARRAY CONVERSION - Enhanced for Frontend
    // =============================================

    public function toArray(): array
    {
        // Get parent array first
        $array = parent::toArray();

        // Manual conversion to camelCase for specific fields that matter
        $camelCaseMapping = [
            'duration_minutes' => 'durationMinutes',
            'calories_burned' => 'caloriesBurned',
            'user_id' => 'userId',
            'template_id' => 'templateId',
            'difficulty_level' => 'difficultyLevel',
            'body_focus' => 'bodyFocus',
            'is_template' => 'isTemplate',
            'is_public' => 'isPublic',
            'is_custom' => 'isCustom',
            'is_active' => 'isActive',
            'estimated_duration' => 'estimatedDuration',
            'estimated_calories' => 'estimatedCalories',
            'creator_name' => 'creatorName',
            'usage_count' => 'usageCount',
            'completion_rate' => 'completionRate',
            'difficulty_label' => 'difficultyLabel',
            'category_label' => 'categoryLabel',
            'formatted_duration' => 'formattedDuration',
            'difficulty_info' => 'difficultyInfo',
            'category_info' => 'categoryInfo',
            'started_at' => 'startedAt',
            'completed_at' => 'completedAt',
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt'
        ];

        // Apply camelCase mapping
        foreach ($camelCaseMapping as $snakeCase => $camelCase) {
            if (isset($array[$snakeCase])) {
                $array[$camelCase] = $array[$snakeCase];
                unset($array[$snakeCase]);
            }
        }

        // Handle exercises with pivot data
        if (isset($array['exercises']) && is_array($array['exercises'])) {
            $array['exercises'] = collect($array['exercises'])->map(function ($exercise) {
                if (isset($exercise['pivot'])) {
                    // Map pivot data to exercise level
                    $pivotMapping = [
                        'order_index' => 'orderIndex',
                        'duration_seconds' => 'durationSeconds',
                        'rest_time_seconds' => 'restTimeSeconds',
                        'target_weight' => 'targetWeight',
                        'completed_sets' => 'completedSets',
                        'completed_reps' => 'completedReps',
                        'actual_duration_seconds' => 'actualDurationSeconds',
                        'weight_used' => 'weightUsed',
                        'is_completed' => 'isCompleted'
                    ];

                    foreach ($pivotMapping as $snakeCase => $camelCase) {
                        if (isset($exercise['pivot'][$snakeCase])) {
                            $exercise[$camelCase] = $exercise['pivot'][$snakeCase];
                        }
                    }

                    // Direct copy for simple fields
                    $exercise['sets'] = $exercise['pivot']['sets'] ?? null;
                    $exercise['reps'] = $exercise['pivot']['reps'] ?? null;
                    $exercise['notes'] = $exercise['pivot']['notes'] ?? null;
                    $exercise['orderIndex'] = $exercise['pivot']['order_index'] ?? 0;

                    // Clean up pivot
                    unset($exercise['pivot']);
                }
                return $exercise;
            })->sortBy('orderIndex')->values()->toArray();
        }

        // Add additional computed properties for frontend
        $array['orderedExercises'] = $array['exercises'] ?? [];

        // Add inferred workout properties if not already set
        $inferredProperties = $this->inferWorkoutProperties();
        foreach ($inferredProperties as $key => $value) {
            $camelKey = str_replace('_', '', ucwords($key, '_'));
            $camelKey = lcfirst($camelKey);

            // Only add if not already set
            if (empty($array[$camelKey])) {
                $array[$camelKey] = $value;
            }
        }

        return $array;
    }

    // Debug helper
    public function toDebugArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'is_template' => $this->is_template,
            'category' => $this->category,
            'difficulty_level' => $this->difficulty_level,
            'exercise_count' => $this->exercises()->count(),
            'is_public' => $this->is_public,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    // =============================================
    // VALIDATION HELPERS
    // =============================================

    public static function getValidCategories(): array
    {
        return ['strength', 'cardio', 'hiit', 'flexibility'];
    }

    public static function getValidDifficulties(): array
    {
        return ['beginner', 'intermediate', 'advanced'];
    }

    public static function getValidStatuses(): array
    {
        return ['planned', 'in_progress', 'completed', 'cancelled'];
    }

    // =============================================
    // CALCULATION METHODS FOR MISSING DATA
    // =============================================

    public function calculateDurationFromExercises(): int
    {
        if (!$this->relationLoaded('exercises') && $this->id) {
            $this->load('exercises');
        }

        $totalDuration = 0;

        foreach ($this->exercises ?? [] as $exercise) {
            // Duration from exercise duration in seconds (pivot takes priority)
            if ($exercise->pivot && $exercise->pivot->duration_seconds) {
                $totalDuration += $exercise->pivot->duration_seconds / 60; // Convert to minutes
            } elseif ($exercise->duration) {
                $totalDuration += $exercise->duration; // Exercise default duration in minutes
            } else {
                // Estimate based on sets and reps
                $sets = $exercise->pivot ? ($exercise->pivot->sets ?? 3) : 3;
                $reps = $exercise->pivot ? ($exercise->pivot->reps ?? 12) : 12;

                // Different time per rep based on exercise type/body part
                $estimatedTimePerRep = $this->getTimePerRepForExercise($exercise);

                // Rest time varies by exercise intensity and sets
                $restTime = $exercise->pivot ? ($exercise->pivot->rest_time_seconds ?? $this->getDefaultRestTime($exercise)) : $this->getDefaultRestTime($exercise);

                // Total time = (sets * reps * time_per_rep) + (sets * rest_time)
                $exerciseWorkTime = $sets * $reps * $estimatedTimePerRep;
                $exerciseRestTime = ($sets - 1) * $restTime; // Rest between sets only, not after last set

                $totalDuration += ($exerciseWorkTime + $exerciseRestTime) / 60; // Convert to minutes
            }
        }

        // Add warm-up and cool-down time (5-10 minutes total)
        $warmupCooldownTime = min(10, max(5, $totalDuration * 0.15)); // 15% of workout time, min 5, max 10
        $totalDuration += $warmupCooldownTime;

        return round($totalDuration);
    }

    public function getTimePerRepForExercise($exercise): int
    {
        // Time per rep in seconds based on exercise characteristics
        $baseTimePerRep = match($exercise->bodyPart ?? 'unknown') {
            'chest', 'back', 'legs' => 4, // Compound movements take longer
            'shoulders', 'arms' => 3, // Smaller muscle groups
            'abs', 'cardio' => 2, // Quick movements
            default => 3
        };

        // Adjust based on difficulty
        $difficultyMultiplier = match($this->difficulty_level) {
            'beginner' => 1.2, // Beginners take more time
            'intermediate' => 1.0,
            'advanced' => 0.9, // Advanced users are more efficient
            default => 1.0
        };

        return round($baseTimePerRep * $difficultyMultiplier);
    }

    public function getDefaultRestTime($exercise): int
    {
        // Rest time in seconds based on exercise and workout type
        $baseRestTime = match($this->category) {
            'strength' => 90, // Longer rest for strength
            'hiit' => 30, // Short rest for HIIT
            'cardio' => 45, // Medium rest for cardio circuits
            'flexibility' => 20, // Minimal rest for flexibility
            default => 60
        };

        // Adjust based on exercise body part
        $bodyPartMultiplier = match($exercise->bodyPart ?? 'unknown') {
            'chest', 'back', 'legs' => 1.2, // Compound movements need more rest
            'shoulders', 'arms' => 1.0,
            'abs', 'cardio' => 0.7, // Less rest needed
            default => 1.0
        };

        return round($baseRestTime * $bodyPartMultiplier);
    }

    public function calculateCaloriesFromWorkout(): int
    {
        $duration = $this->getEstimatedDurationAttribute();
        if ($duration <= 0) return 0;

        // Default user weight (should ideally come from user profile)
        $userWeight = 70; // kg - fallback weight

        // Try to get user's actual weight
        if ($this->user && $this->user->weight) {
            $userWeight = $this->user->weight;
        }

        // Calculate MET based on workout characteristics
        $met = $this->calculateMETValue();

        // Formula: Calories = duration (min) * MET * 3.5 * weight (kg) / 200
        return round(($duration * $met * 3.5 * $userWeight) / 200);
    }

    public function calculateMETValue(): float
    {
        // Base MET values by category
        $categoryMET = [
            'strength' => 5.0,
            'cardio' => 7.0,
            'hiit' => 8.0,
            'flexibility' => 2.5
        ];

        $baseMET = $categoryMET[$this->category] ?? 5.0;

        // Adjust by difficulty
        $difficultyMultiplier = [
            'beginner' => 0.8,
            'intermediate' => 1.0,
            'advanced' => 1.3
        ];

        $multiplier = $difficultyMultiplier[$this->difficulty_level] ?? 1.0;

        // Adjust by intensity if available
        if ($this->intensity) {
            $intensityMultiplier = [
                'low' => 0.8,
                'medium' => 1.0,
                'high' => 1.4
            ];
            $multiplier *= $intensityMultiplier[$this->intensity] ?? 1.0;
        }

        return $baseMET * $multiplier;
    }

    public function inferWorkoutProperties(): array
    {
        if (!$this->relationLoaded('exercises') && $this->id) {
            $this->load('exercises');
        }

        $exercises = $this->exercises ?? collect();

        // Infer body focus from exercises
        $bodyFocus = $this->inferBodyFocus($exercises);

        // Infer workout type from category and exercises
        $type = $this->inferWorkoutType($exercises);

        // Infer intensity from difficulty and exercises
        $intensity = $this->inferIntensity($exercises);

        // Infer equipment from exercises
        $equipment = $this->inferEquipment($exercises);

        // Infer goal from category and type
        $goal = $this->inferGoal();

        // Infer frequency from difficulty and goal
        $frequency = $this->inferFrequency();

        return [
            'body_focus' => $bodyFocus,
            'type' => $type,
            'intensity' => $intensity,
            'equipment' => $equipment,
            'goal' => $goal,
            'frequency' => $frequency
        ];
    }

    private function inferBodyFocus($exercises): string
    {
        if ($exercises->isEmpty()) return 'full_body';

        $bodyParts = $exercises->pluck('bodyPart')->filter()->unique();

        if ($bodyParts->count() >= 4) return 'full_body';

        $upperBodyParts = ['chest', 'back', 'shoulders', 'arms'];
        $lowerBodyParts = ['legs'];
        $coreParts = ['abs'];

        $hasUpperBody = $bodyParts->intersect($upperBodyParts)->isNotEmpty();
        $hasLowerBody = $bodyParts->intersect($lowerBodyParts)->isNotEmpty();
        $hasCore = $bodyParts->intersect($coreParts)->isNotEmpty();

        if ($hasUpperBody && $hasLowerBody) return 'full_body';
        if ($hasUpperBody) return 'upper_body';
        if ($hasLowerBody) return 'lower_body';
        if ($hasCore) return 'core';

        return 'full_body';
    }

    private function inferWorkoutType($exercises): string
    {
        if ($this->category) {
            $categoryToType = [
                'strength' => 'strength',
                'cardio' => 'cardio',
                'hiit' => 'hiit',
                'flexibility' => 'flexibility'
            ];
            return $categoryToType[$this->category] ?? 'custom';
        }

        return 'custom';
    }

    private function inferIntensity($exercises): string
    {
        if ($this->intensity) return $this->intensity;

        // Infer from difficulty level
        $difficultyToIntensity = [
            'beginner' => 'low',
            'intermediate' => 'medium',
            'advanced' => 'high'
        ];

        return $difficultyToIntensity[$this->difficulty_level] ?? 'medium';
    }

    private function inferEquipment($exercises): string
    {
        if ($exercises->isEmpty()) return 'none';

        $equipmentNeeded = $exercises->pluck('equipmentNeeded')->filter()->unique();

        if ($equipmentNeeded->isEmpty()) return 'none';

        // Check for gym equipment
        $gymEquipment = ['barbell', 'dumbbell', 'machine', 'cable'];
        if ($equipmentNeeded->intersect($gymEquipment)->isNotEmpty()) {
            return 'full_gym';
        }

        // Check for specific equipment
        if ($equipmentNeeded->contains('dumbbell')) return 'dumbbells';
        if ($equipmentNeeded->contains('kettlebell')) return 'kettlebell';
        if ($equipmentNeeded->contains('resistance_band')) return 'resistance_bands';

        return 'none';
    }

    private function inferGoal(): string
    {
        if ($this->goal) return $this->goal;

        // Infer from category
        $categoryToGoal = [
            'strength' => 'strength_gain',
            'cardio' => 'fat_loss',
            'hiit' => 'fat_loss',
            'flexibility' => 'maintenance'
        ];

        return $categoryToGoal[$this->category] ?? 'maintenance';
    }

    private function inferFrequency(): string
    {
        if ($this->frequency) return $this->frequency;

        // Infer from difficulty and type
        $difficultyToFrequency = [
            'beginner' => 'twice',
            'intermediate' => 'thrice',
            'advanced' => 'four_times'
        ];

        return $difficultyToFrequency[$this->difficulty_level] ?? 'thrice';
    }
}