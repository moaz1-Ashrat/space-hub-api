<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'special_date',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'special_date' => 'date',
    ];

    public function space()
    {
        return $this->belongsTo(Space::class);
    }
}
