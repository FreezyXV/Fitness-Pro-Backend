<?php
// app/Traits/BelongsToUserTrait.php
namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUserTrait
{
    /**
     * Relation vers l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour filtrer par utilisateur
     */
    public function scopeForUser($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour l'utilisateur actuel
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }

    /**
     * Vérifier si le modèle appartient à l'utilisateur donné
     */
    public function belongsToUser($userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        return $this->user_id === $userId;
    }

    /**
     * Boot trait pour automatiquement assigner l'utilisateur
     */
    public static function bootBelongsToUserTrait()
    {
        static::creating(function ($model) {
            if (!$model->user_id && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }
}