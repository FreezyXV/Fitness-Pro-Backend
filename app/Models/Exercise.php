<?php
// app/Models/Exercise.php - FIXED VERSION
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'body_part',
        'description',
        'muscle_groups',
        'equipment_needed',
        'video_url',
        'duration',
        'difficulty',
        'instructions',
        'tips',
        'category',
        'estimated_calories_per_minute',
    ];

    protected $casts = [
        'muscle_groups' => 'array',
        'instructions' => 'array',
        'tips' => 'array',
        'estimated_calories_per_minute' => 'integer',
        'duration' => 'integer',
    ];

    protected $appends = [
        'body_part_label',
        'difficulty_label',
        'difficulty_color',
        'body_part_info',
        'difficulty_info'
    ];

    // Relationships
    public function workouts()
    {
        return $this->belongsToMany(Workout::class, 'workout_exercises')
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
                        'calories_burned',
                        'rest_time_used',
                        'difficulty_felt',
                        'effort_level',
                        'is_completed',
                        'completion_percentage',
                        'is_personal_record'
                    ])
                    ->withTimestamps()
                    ->orderBy('workout_exercises.order_index');
    }

    public function userProgress()
    {
        return $this->hasMany(UserExerciseProgress::class);
    }

    public function userFavorites()
    {
        return $this->belongsToMany(User::class, 'user_favorite_exercises')
                    ->withTimestamps();
    }

    

    // Scopes
    public function scopeByBodyPart($query, $bodyPart)
    {
        return $query->where('body_part', $bodyPart);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByMuscleGroup($query, $muscleGroup)
    {
        return $query->whereJsonContains('muscle_groups', $muscleGroup);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getVideoUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // If it's already a full URL, return as is
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // If it starts with /assets, return as is
        if (str_starts_with($value, '/assets')) {
            return $value;
        }

        // Otherwise, prepend the assets path
        return '/assets/' . ltrim($value, '/');
    }

    public function getDifficultyColorAttribute()
    {
        return match($this->difficulty) {
            'beginner' => '#48bb78',
            'intermediate' => '#ed8936', 
            'advanced' => '#f56565',
            default => '#718096'
        };
    }

    public function getBodyPartLabelAttribute()
    {
        $labels = [
            'chest' => 'Poitrine',
            'back' => 'Dos',
            'arms' => 'Bras',
            'legs' => 'Jambes',
            'shoulders' => 'Épaules',
            'abs' => 'Abdominaux',
            'cardio' => 'Cardio',
            'mobility' => 'Mobilité',
            'flexibility' => 'Flexibilité'
        ];
        
        return $labels[$this->body_part] ?? ucfirst($this->body_part);
    }

    public function getDifficultyLabelAttribute()
    {
        $labels = [
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé'
        ];
        
        return $labels[$this->difficulty] ?? ucfirst($this->difficulty);
    }

    public function getDifficultyInfoAttribute()
    {
        return [
            'value' => $this->difficulty,
            'label' => $this->difficulty_label,
            'color' => $this->difficulty_color
        ];
    }

    public function getBodyPartInfoAttribute()
    {
        $bodyParts = [
            'chest' => ['label' => 'Poitrine', 'icon' => 'fas fa-hand-paper'],
            'back' => ['label' => 'Dos', 'icon' => 'fas fa-user'],
            'legs' => ['label' => 'Jambes', 'icon' => 'fas fa-walking'],
            'shoulders' => ['label' => 'Épaules', 'icon' => 'fas fa-arrows-alt-h'],
            'arms' => ['label' => 'Bras', 'icon' => 'fas fa-dumbbell'],
            'abs' => ['label' => 'Abdominaux', 'icon' => 'fas fa-circle'],
            'cardio' => ['label' => 'Cardio', 'icon' => 'fas fa-heart'],
            'mobility' => ['label' => 'Mobilité', 'icon' => 'fas fa-leaf'],
            'flexibility' => ['label' => 'Flexibilité', 'icon' => 'fas fa-leaf']
        ];

        return $bodyParts[$this->body_part] ?? [
            'label' => ucfirst($this->body_part), 
            'icon' => 'fas fa-dumbbell'
        ];
    }

    // API Response formatting
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add camelCase versions for frontend compatibility
        $array['bodyPart'] = $this->body_part;
        $array['videoUrl'] = $this->video_url;
        $array['muscleGroups'] = $this->muscle_groups;
        $array['equipmentNeeded'] = $this->equipment_needed;
        $array['estimatedCaloriesPerMinute'] = $this->estimated_calories_per_minute;
        
        return $array;
    }
}