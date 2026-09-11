<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $table = 'coupons';

    protected $fillable = [
        'user_id',
        'discount_value',
        'commission_rate',
        'is_used',
        'expiry_date',
        'used_at',
        'booking_id',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'commission_rate' => 'float',
        'is_used' => 'boolean',
        'expiry_date' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
