<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
    'user_id',
    'space_id',
    'payment_id',
    'booking_date',
    'start_time',
    'end_time',
    'total_amount',
    'commission_rate',
    'commission_amount',
    'customer_paid',
    'owner_payout',
    'booking_status',
    'attendance_status',
    'historical_booking',
];
   protected $casts = [
    'booking_date' => 'date',
    'total_amount' => 'decimal:2',
    'commission_rate' => 'decimal:4',
    'commission_amount' => 'decimal:2',
    'customer_paid' => 'decimal:2',
    'owner_payout' => 'decimal:2',
    'historical_booking' => 'boolean',
      ];





    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function space()
    {
        return $this->belongsTo(Space::class, 'space_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
