<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Space extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'location',
        'description',
        'space_size',
        'capacity_people',
        'price_per_hour',
        'space_type',
        'device_type',
        'approval_status',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'space_feature', 'space_id', 'feature_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'space_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'space_id');
    }
    public function availabilities()
    {
    return $this->hasMany(Availability::class);
    }
}
