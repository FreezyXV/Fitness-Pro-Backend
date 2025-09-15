<?php
// app/Models/CalendarTask.php - COMPLETE MODEL
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUserTrait;
use Carbon\Carbon;

class CalendarTask extends Model
{
    use HasFactory, BelongsToUserTrait;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'task_date',
        'task_type',
        'is_completed',
        'workout_plan_id',
        'reminder_time',
        'priority',
        'duration',
        'tags',
        'recurring',
        'recurring_type',
        'recurring_end_date'
    ];

    protected $casts = [
        'task_date' => 'date',
        'is_completed' => 'boolean',
        'reminder_time' => 'datetime',
        'tags' => 'array',
        'recurring' => 'boolean',
        'recurring_end_date' => 'date',
        'duration' => 'integer'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }

    // Scopes
    public function scopeForUser($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('task_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('task_date', Carbon::today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('task_date', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('task_date', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ]);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('task_date', $year)
                    ->whereMonth('task_date', $month);
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_completed', false)
                    ->where('task_date', '<', Carbon::today());
    }

    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // Methods
    public function markAsCompleted()
    {
        $this->update(['is_completed' => true]);
        return $this;
    }

    public function markAsPending()
    {
        $this->update(['is_completed' => false]);
        return $this;
    }

    public function isOverdue()
    {
        return !$this->is_completed && $this->task_date < Carbon::today();
    }

    public function isToday()
    {
        return $this->task_date->isToday();
    }

    public function isFuture()
    {
        return $this->task_date > Carbon::today();
    }

    public function getDaysUntil()
    {
        return $this->task_date->diffInDays(Carbon::now(), false);
    }

    public function getFormattedDate()
    {
        return $this->task_date->format('d/m/Y');
    }

    public function getFormattedTime()
    {
        return $this->reminder_time ? $this->reminder_time->format('H:i') : null;
    }

    public function getPriorityLabel()
    {
        return match($this->priority) {
            'high' => 'Haute',
            'medium' => 'Moyenne',
            'low' => 'Basse',
            default => 'Moyenne'
        };
    }

    public function getTypeLabel()
    {
        return match($this->task_type) {
            'workout' => 'Entraînement',
            'goal' => 'Objectif',
            'rest' => 'Repos',
            'nutrition' => 'Nutrition',
            'reminder' => 'Rappel',
            default => 'Autre'
        };
    }

    // Frontend compatibility
    public function toArray()
    {
        $array = parent::toArray();
        
        $array['is_today'] = $this->isToday();
        $array['is_overdue'] = $this->isOverdue();
        $array['is_future'] = $this->isFuture();
        $array['days_until'] = $this->getDaysUntil();
        $array['formatted_date'] = $this->getFormattedDate();
        $array['formatted_time'] = $this->getFormattedTime();
        $array['priority_label'] = $this->getPriorityLabel();
        $array['type_label'] = $this->getTypeLabel();
        
        return $array;
    }
}