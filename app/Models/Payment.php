<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'payment_methode',
        'payment_date_time',
        'payment_status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date_time' => 'datetime',
    ];

    public function booking()
    {
        return $this->hasOne(Booking::class, 'payment_id');
    }
}
