<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CamelCaseSerializationTrait;

class NutritionGoal extends Model
{
    use HasFactory, CamelCaseSerializationTrait;

    protected $fillable = [
        'user_id',
        'calories',
        'protein',
        'carbs',
        'fat',
        'water',
        'fiber',
        'sodium',
        'potassium',
        'vitaminC',
    ];

    protected $casts = [
        'water' => 'float',
        'calories' => 'float',
        'protein' => 'float',
        'carbs' => 'float',
        'fat' => 'float',
        'fiber' => 'float',
        'sodium' => 'float',
        'potassium' => 'float',
        'vitaminC' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}