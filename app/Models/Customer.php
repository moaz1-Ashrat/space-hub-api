<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'favorite'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'customer_id');
    }
}
