<?php
// app/Models/Goal.php - FIXED VERSION - Remove problematic $appends
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUserTrait;
use Carbon\Carbon;

class Goal extends Model
{
    use HasFactory, BelongsToUserTrait;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'target_value',
        'current_value',
        'unit',
        'target_date',
        'status',
        'category',
        'priority'
    ];

    protected $casts = [
        'target_date' => 'date',
        'target_value' => 'float',
        'current_value' => 'float',
    ];

    // REMOVED: problematic $appends - these were causing 500 errors
    // protected $appends = ['progress_percentage', 'is_achieved', 'days_remaining'];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not-started');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Direct access methods (call these when needed instead of automatic appends)
    public function getProgressPercentage()
    {
        if ($this->target_value == 0) {
            return 0;
        }
        
        return min(100, round(($this->current_value / $this->target_value) * 100, 1));
    }

    public function getIsAchieved()
    {
        return $this->current_value >= $this->target_value;
    }

    public function getDaysRemaining()
    {
        if (!$this->target_date) {
            return null;
        }

        $days = Carbon::now()->diffInDays($this->target_date, false);
        return $days > 0 ? $days : 0;
    }

    // Methods
    public function updateProgress($newValue)
    {
        $this->update(['current_value' => $newValue]);
        
        // Auto-complete if target reached
        if ($this->getIsAchieved() && $this->status !== 'completed') {
            $this->markAsCompleted();
        }
        
        return $this;
    }

    public function incrementProgress($amount = 1)
    {
        return $this->updateProgress($this->current_value + $amount);
    }

    public function markAsCompleted()
    {
        $wasCompleted = $this->status === 'completed';
        
        $this->update([
            'status' => 'completed',
            'current_value' => $this->target_value
        ]);

        // Award points and update streak if goal wasn't already completed
        if (!$wasCompleted) {
            $userScore = $this->user->userScore ?? \App\Models\UserScore::createOrUpdateForUser($this->user);
            $userScore->incrementGoalsCompleted();
            
            // Check for new achievements if Achievement model exists
            if (class_exists('\App\Models\Achievement')) {
                \App\Models\Achievement::checkAllForUser($this->user);
            }
        }
        
        return $this;
    }

    public function markAsActive()
    {
        $this->update(['status' => 'active']);
        return $this;
    }

    public function pause()
    {
        $this->update(['status' => 'paused']);
        return $this;
    }

    public function isOverdue()
    {
        return $this->target_date && 
               $this->target_date < Carbon::today() && 
               !$this->getIsAchieved();
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'active' => '#22c55e',
            'completed' => '#3b82f6', 
            'paused' => '#f59e0b',
            'not-started' => '#9ca3af',
            default => '#6b7280'
        };
    }

    public function getProgressColor()
    {
        $progress = $this->getProgressPercentage();
        
        if ($progress >= 80) return '#22c55e'; // Green
        if ($progress >= 50) return '#3b82f6'; // Blue
        if ($progress >= 25) return '#f59e0b'; // Orange
        return '#ef4444'; // Red
    }

    // Enhanced toArray for frontend compatibility
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add computed fields when explicitly requested
        $array['progress_percentage'] = $this->getProgressPercentage();
        $array['is_achieved'] = $this->getIsAchieved();
        $array['days_remaining'] = $this->getDaysRemaining();
        
        return $array;
    }
}