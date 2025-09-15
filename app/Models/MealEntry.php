<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CamelCaseSerializationTrait;

class MealEntry extends Model
{
    use HasFactory, CamelCaseSerializationTrait;

    protected $fillable = [
        'user_id',
        'food_id',
        'quantity',
        'meal_type',
        'date',
        'calories',
        'protein',
        'carbs',
        'fat',
        'fiber',
        'sodium',
        'potassium',
        'vitamin_c',
        'name',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'float',
        'calories' => 'float',
        'protein' => 'float',
        'carbs' => 'float',
        'fat' => 'float',
        'fiber' => 'float',
        'sodium' => 'float',
        'potassium' => 'float',
        'vitamin_c' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}