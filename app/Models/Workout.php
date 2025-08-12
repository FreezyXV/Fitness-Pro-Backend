<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Workout extends Model
{
    use HasFactory;

    protected $table = 'workouts';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category',
        'difficulty_level',
        'is_template',
        'template_id',
        'status', // planned, in_progress, completed, cancelled
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
    ];

    // Add computed attributes for frontend compatibility
    protected $appends = [
        'difficulty_label',
        'category_label',
        'exercise_count',
        'formatted_duration',
        'difficulty_info',
        'category_info',
        'is_custom',
        'is_active'
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
        return $this->exercises()->count();
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
        return $this->duration_minutes ?? 0;
    }

    public function getEstimatedCaloriesAttribute(): int
    {
        return $this->calories_burned ?? 0;
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
        $array = parent::toArray();
        
        // Load exercises with pivot data if not already loaded
        if (!$this->relationLoaded('exercises')) {
            $this->load('exercises');
        }

        // Transform exercises to include flattened pivot data
        if (isset($array['exercises']) && is_array($array['exercises'])) {
            $array['exercises'] = collect($array['exercises'])->map(function ($exercise) {
                // Flatten pivot data to exercise level
                if (isset($exercise['pivot'])) {
                    $exercise['order_index'] = $exercise['pivot']['order_index'] ?? 0;
                    $exercise['sets'] = $exercise['pivot']['sets'] ?? null;
                    $exercise['reps'] = $exercise['pivot']['reps'] ?? null;
                    $exercise['duration_seconds'] = $exercise['pivot']['duration_seconds'] ?? null;
                    $exercise['rest_time_seconds'] = $exercise['pivot']['rest_time_seconds'] ?? null;
                    $exercise['target_weight'] = $exercise['pivot']['target_weight'] ?? null;
                    $exercise['notes'] = $exercise['pivot']['notes'] ?? null;
                    $exercise['completed_sets'] = $exercise['pivot']['completed_sets'] ?? null;
                    $exercise['completed_reps'] = $exercise['pivot']['completed_reps'] ?? null;
                    $exercise['actual_duration_seconds'] = $exercise['pivot']['actual_duration_seconds'] ?? null;
                    $exercise['weight_used'] = $exercise['pivot']['weight_used'] ?? null;
                    $exercise['is_completed'] = $exercise['pivot']['is_completed'] ?? false;
                }
                return $exercise;
            })->sortBy('order_index')->values()->toArray();
        }

        // Add additional computed properties for frontend
        $array['ordered_exercises'] = $array['exercises'] ?? [];
        
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
}