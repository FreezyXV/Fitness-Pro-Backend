<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aliment extends Model
{
    protected $fillable = [
        'name',
        'category',
        'calories',
        'proteins',
        'carbohydrates',
        'fats',
        'image_url',
        'unit',
    ];
}
