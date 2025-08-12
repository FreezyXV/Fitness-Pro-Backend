<?php
// Models/WorkoutPlan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'exercises',
        'estimated_duration',
        'estimated_calories',
        'difficulty_level',
        'category',
        'image',
        'is_active',
        'is_custom',
    ];

    protected $casts = [
        'exercises' => 'array',
        'estimated_duration' => 'integer',
        'estimated_calories' => 'integer',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
    ];

    protected $appends = [
        'difficulty_label',
        'category_label',
        'exercise_count',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workoutSessions()
    {
        return $this->hasMany(WorkoutSession::class);
    }

    public function calendarTasks()
    {
        return $this->hasMany(CalendarTask::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    public function scopeTemplates($query)
    {
        return $query->where('is_custom', false);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getDifficultyLabelAttribute()
    {
        $labels = [
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé'
        ];
        
        return $labels[$this->difficulty_level] ?? ucfirst($this->difficulty_level);
    }

    public function getCategoryLabelAttribute()
    {
        $labels = [
            'strength' => 'Force',
            'cardio' => 'Cardio',
            'flexibility' => 'Flexibilité',
            'hiit' => 'HIIT'
        ];
        
        return $labels[$this->category] ?? ucfirst($this->category);
    }

    public function getExerciseCountAttribute()
    {
        return is_array($this->exercises) ? count($this->exercises) : 0;
    }

    // Helper methods
    public function getExercisesByOrder()
    {
        if (!is_array($this->exercises)) {
            return [];
        }

        $exercises = $this->exercises;
        usort($exercises, function($a, $b) {
            return ($a['order_index'] ?? 0) <=> ($b['order_index'] ?? 0);
        });

        return $exercises;
    }

    public function getTotalSets()
    {
        if (!is_array($this->exercises)) {
            return 0;
        }

        return array_sum(array_column($this->exercises, 'sets'));
    }

    public function getEstimatedDurationFormatted()
    {
        $minutes = $this->estimated_duration;
        
        if ($minutes < 60) {
            return "{$minutes}min";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}min";
        }
        
        return "{$hours}h";
    }

    public function getDifficultyColor()
    {
        return match($this->difficulty_level) {
            'beginner' => '#48bb78',
            'intermediate' => '#ed8936', 
            'advanced' => '#f56565',
            default => '#718096'
        };
    }

    public function getCategoryIcon()
    {
        return match($this->category) {
            'strength' => 'fas fa-dumbbell',
            'cardio' => 'fas fa-heart',
            'flexibility' => 'fas fa-leaf',
            'hiit' => 'fas fa-fire',
            default => 'fas fa-dumbbell'
        };
    }

    // API Response formatting
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add formatted data for frontend
        $array['difficulty_info'] = [
            'value' => $this->difficulty_level,
            'label' => $this->difficulty_label,
            'color' => $this->getDifficultyColor(),
        ];
        
        $array['category_info'] = [
            'value' => $this->category,
            'label' => $this->category_label,
            'icon' => $this->getCategoryIcon(),
        ];
        
        $array['formatted_duration'] = $this->getEstimatedDurationFormatted();
        $array['ordered_exercises'] = $this->getExercisesByOrder();
        
        return $array;
    }
}