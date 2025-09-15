<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CamelCaseSerializationTrait;

class WaterIntake extends Model
{
    use HasFactory, CamelCaseSerializationTrait;

    protected $fillable = [
        'user_id',
        'amount',
        'date',
        'timestamp',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'float',
        'timestamp' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}