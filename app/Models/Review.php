<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'space_id',
        'rating',
        'comment',
        'review_date',
    ];

    protected $casts = [
        'review_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
    }
}
