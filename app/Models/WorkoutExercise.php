<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutExercise extends Model
{
    use HasFactory;

    protected $table = 'workout_exercises';

    protected $fillable = [
        'workout_id',
        'exercise_id',
        'order_index',
        // Planned values
        'sets',
        'reps',
        'duration_seconds',
        'rest_time_seconds',
        'target_weight',
        'notes',
        // Actual values
        'completed_sets',
        'completed_reps',
        'actual_duration_seconds',
        'weight_used',
        'calories_burned',
        'rest_time_used',
        'difficulty_felt',
        'effort_level',
        'is_completed',
        'completion_percentage',
        'is_personal_record',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'sets' => 'integer',
        'reps' => 'integer',
        'duration_seconds' => 'integer',
        'rest_time_seconds' => 'integer',
        'target_weight' => 'decimal:2',
        'completed_sets' => 'integer',
        'completed_reps' => 'integer',
        'actual_duration_seconds' => 'integer',
        'weight_used' => 'decimal:2',
        'calories_burned' => 'integer',
        'rest_time_used' => 'integer',
        'effort_level' => 'integer',
        'completion_percentage' => 'integer',
        'is_completed' => 'boolean',
        'is_personal_record' => 'boolean',
    ];

    // =============================================
    // RELATIONSHIPS
    // =============================================

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('is_completed', false);
    }

    // =============================================
    // ACCESSORS & MUTATORS
    // =============================================

    public function getCompletionPercentageAttribute(): float
    {
        if (!$this->sets || $this->sets == 0) {
            return $this->is_completed ? 100.0 : 0.0;
        }

        $completedSets = $this->completed_sets ?? 0;
        return min(100.0, ($completedSets / $this->sets) * 100);
    }

    public function getIsPartiallyCompletedAttribute(): bool
    {
        return $this->completed_sets > 0 && $this->completed_sets < $this->sets;
    }

    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration_seconds ?? 0;
        
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($remainingSeconds > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }
        
        return "{$minutes}m";
    }

    public function getFormattedRestTimeAttribute(): string
    {
        $seconds = $this->rest_time_seconds ?? 0;
        
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($remainingSeconds > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }
        
        return "{$minutes}m";
    }

    // =============================================
    // HELPER METHODS
    // =============================================

    public function markCompleted(array $completionData = []): void
    {
        $this->update([
            'is_completed' => true,
            'completed_sets' => $completionData['completed_sets'] ?? $this->sets,
            'completed_reps' => $completionData['completed_reps'] ?? $this->reps,
            'actual_duration_seconds' => $completionData['actual_duration_seconds'] ?? $this->duration_seconds,
            'weight_used' => $completionData['weight_used'] ?? $this->target_weight,
        ]);
    }

    public function markIncomplete(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_sets' => 0,
            'completed_reps' => 0,
            'actual_duration_seconds' => 0,
            'weight_used' => null,
        ]);
    }

    public function updateProgress(array $progressData): void
    {
        $this->update([
            'completed_sets' => $progressData['completed_sets'] ?? $this->completed_sets,
            'completed_reps' => $progressData['completed_reps'] ?? $this->completed_reps,
            'actual_duration_seconds' => $progressData['actual_duration_seconds'] ?? $this->actual_duration_seconds,
            'weight_used' => $progressData['weight_used'] ?? $this->weight_used,
            'is_completed' => $this->shouldBeMarkedCompleted(),
        ]);
    }

    private function shouldBeMarkedCompleted(): bool
    {
        // Mark as completed if all sets are done
        if ($this->sets && $this->completed_sets >= $this->sets) {
            return true;
        }

        // Mark as completed if duration is reached
        if ($this->duration_seconds && $this->actual_duration_seconds >= $this->duration_seconds) {
            return true;
        }

        return false;
    }

    // =============================================
    // ARRAY CONVERSION
    // =============================================

    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed properties
        $array['completion_percentage'] = $this->completion_percentage;
        $array['is_partially_completed'] = $this->is_partially_completed;
        $array['formatted_duration'] = $this->formatted_duration;
        $array['formatted_rest_time'] = $this->formatted_rest_time;
        
        // Include exercise data if loaded
        if ($this->relationLoaded('exercise')) {
            $array['exercise_name'] = $this->exercise->name;
            $array['exercise_body_part'] = $this->exercise->body_part;
            $array['exercise_difficulty'] = $this->exercise->difficulty;
        }
        
        return $array;
    }

    // =============================================
    // VALIDATION HELPERS
    // =============================================

    public function validateCompletionData(array $data): array
    {
        $errors = [];

        if (isset($data['completed_sets']) && $data['completed_sets'] < 0) {
            $errors[] = 'Completed sets cannot be negative';
        }

        if (isset($data['completed_sets']) && $this->sets && $data['completed_sets'] > $this->sets) {
            $errors[] = 'Completed sets cannot exceed planned sets';
        }

        if (isset($data['completed_reps']) && $data['completed_reps'] < 0) {
            $errors[] = 'Completed reps cannot be negative';
        }

        if (isset($data['actual_duration_seconds']) && $data['actual_duration_seconds'] < 0) {
            $errors[] = 'Duration cannot be negative';
        }

        if (isset($data['weight_used']) && $data['weight_used'] < 0) {
            $errors[] = 'Weight cannot be negative';
        }

        return $errors;
    }
}